<?php

namespace App\Jobs;

use App\Models\DgAnomaly;
use App\Models\DgContractSchedule;
use App\Models\DgReportCache;
use App\Models\DgSiteAssignment;
use App\Models\DgWorkSession;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class GenerateReportsCache implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected ?string $periodStart;
    protected ?string $periodEnd;

    public function __construct(?string $periodStart = null, ?string $periodEnd = null)
    {
        $this->periodStart = $periodStart;
        $this->periodEnd   = $periodEnd;
    }

    public function handle(): void
    {
        $start = $this->periodStart
            ? Carbon::parse($this->periodStart)->startOfDay()
            : null;

        $end = $this->periodEnd
            ? Carbon::parse($this->periodEnd)->endOfDay()
            : null;

        if (!$start || !$end) {
            $anchor = now()->subMonthNoOverflow();
            $start ??= $anchor->copy()->startOfMonth();
            $end   ??= $anchor->copy()->endOfMonth();
        }

        // Prendiamo solo i campi necessari per non sprecare RAM
        $sessions = DgWorkSession::query()
            ->whereBetween('session_date', [$start->toDateString(), $end->toDateString()])
            ->get(['id','user_id','site_id','resolved_site_id','session_date','worked_minutes','overtime_minutes']);

        // Group: user_id -> site_resolved (coalesce resolved_site_id, site_id)
        $grouped = $sessions->groupBy([
            'user_id',
            function ($s) {
                return $s->resolved_site_id ?? $s->site_id;
            }
        ]);

        if ($grouped->isEmpty()) {
            return;
        }

        $userIds = $grouped->keys()->all();

        $contracts = User::query()
            ->with('contractSchedule')
            ->whereIn('id', $userIds)
            ->get()
            ->mapWithKeys(fn ($user) => [$user->id => $user->contractSchedule]);

        $contractWorkingDays = [];
        foreach ($contracts as $id => $contract) {
            $contractWorkingDays[$id] = $contract
                ? $this->countContractWorkingDays($start->copy(), $end->copy(), $contract)
                : 0;
        }

        $assignments = $this->loadAssignments($userIds, $start, $end);

        foreach ($grouped as $userId => $bySite) {
            foreach ($bySite as $siteId => $records) {
                if ($siteId === null) {
                    // Se proprio non abbiamo un site, salta: non ha senso mettere un report senza cantiere
                    continue;
                }

                // Chiavi delle sessioni di questo gruppo, per filtrare le anomalie coerenti con il sito
                $sessionIds = $records->pluck('id')->all();

                // Aggregati base
                $workedMinutes   = max(0, (int) $records->sum('worked_minutes'));
                $overtimeMinutes = max(0, (int) $records->sum('overtime_minutes'));
                $workedHours     = round($workedMinutes / 60, 2);
                $daysPresent     = $records->where('worked_minutes', '>', 0)
                    ->pluck('session_date')
                    ->unique()
                    ->count();

                $contractSchedule = $contracts[$userId] ?? null;
                $sitesForUser = $bySite->keys()->count();
                $assignmentSet = $assignments[$userId][$siteId] ?? collect();
                $expectedDays = $this->calculateExpectedDaysForSite(
                    $start->copy(),
                    $end->copy(),
                    $contractSchedule,
                    $assignmentSet,
                    $sitesForUser,
                    $daysPresent,
                    $contractWorkingDays[$userId] ?? 0
                );

                // Anomalie dal data store normalizzato, filtrate per le sessioni del gruppo
                // NB: filtri per periodo + session_id IN (questo gruppo) per avere conteggi per-sito
                $anomaliesQuery = DgAnomaly::query()
                    ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                    ->where('user_id', $userId);

                if (!empty($sessionIds)) {
                    $anomaliesQuery->whereIn('session_id', $sessionIds);
                } else {
                    // Fallback paranoico: se non ci sono sessioni, non ci sono anomalie del gruppo
                    $anomaliesQuery->whereRaw('1 = 0');
                }

                $lateEntries = (clone $anomaliesQuery)->where('type', 'late_entry')->count();
                $earlyExits  = (clone $anomaliesQuery)->where('type', 'early_exit')->count();
                $absencesAnomalies = (clone $anomaliesQuery)->where('type', 'absence')->count();

                $contractAbsences = max(0, $expectedDays - $daysPresent);
                $daysAbsent = max($absencesAnomalies, $contractAbsences);

                DgReportCache::updateOrCreate(
                    [
                        'user_id'      => $userId,
                        'site_id'      => $siteId,
                        'period_start' => $start->toDateString(),
                        'period_end'   => $end->toDateString(),
                    ],
                    [
                        'resolved_site_id' => $siteId, // ✅ salva anche il resolved
                        'worked_hours'     => $workedHours,
                        'days_present'     => $daysPresent,
                        'days_absent'      => $daysAbsent,
                        'late_entries'     => $lateEntries,
                        'early_exits'      => $earlyExits,
                        'overtime_minutes' => $overtimeMinutes ?? 0,
                        'generated_at'     => now(),
                        'is_final'         => false,
                    ]
                );

            }
        }
    }

    private function loadAssignments(array $userIds, Carbon $start, Carbon $end): Collection
    {
        if (empty($userIds)) {
            return collect();
        }

        return DgSiteAssignment::query()
            ->whereIn('user_id', $userIds)
            ->where(function ($query) use ($end) {
                $query->whereNull('assigned_from')
                    ->orWhereDate('assigned_from', '<=', $end->toDateString());
            })
            ->where(function ($query) use ($start) {
                $query->whereNull('assigned_to')
                    ->orWhereDate('assigned_to', '>=', $start->toDateString());
            })
            ->get()
            ->groupBy(['user_id', 'site_id']);
    }

    private function countContractWorkingDays(Carbon $start, Carbon $end, DgContractSchedule $schedule): int
    {
        $days = 0;
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $weekday = $cursor->dayOfWeekIso - 1;

            if ($this->isWorkingDay($schedule, $weekday)) {
                $days++;
            }

            $cursor->addDay();
        }

        return $days;
    }

    private function calculateExpectedDaysForSite(
        Carbon $start,
        Carbon $end,
        ?DgContractSchedule $schedule,
        Collection $assignments,
        int $siteCount,
        int $daysPresent,
        int $fallbackTotal
    ): int {
        if (!$schedule) {
            return max($daysPresent, 0);
        }

        if ($assignments->isNotEmpty()) {
            return $this->calculateDaysWithAssignments($start->copy(), $end->copy(), $schedule, $assignments);
        }

        if ($siteCount > 1) {
            return max($daysPresent, 0);
        }

        return $fallbackTotal;
    }

    private function calculateDaysWithAssignments(
        Carbon $start,
        Carbon $end,
        DgContractSchedule $schedule,
        Collection $assignments
    ): int {
        $ranges = $assignments->map(function ($assignment) {
            return [
                'from' => $assignment->assigned_from
                    ? Carbon::parse($assignment->assigned_from)->startOfDay()
                    : null,
                'to' => $assignment->assigned_to
                    ? Carbon::parse($assignment->assigned_to)->endOfDay()
                    : null,
            ];
        });

        $expected = 0;
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $weekday = $cursor->dayOfWeekIso - 1;

            if (!$this->isWorkingDay($schedule, $weekday)) {
                $cursor->addDay();
                continue;
            }

            $isAssigned = $ranges->isEmpty() || $ranges->contains(function ($range) use ($cursor) {
                $from = $range['from'];
                $to   = $range['to'];

                if ($from && $cursor->lt($from)) {
                    return false;
                }

                if ($to && $cursor->gt($to)) {
                    return false;
                }

                return true;
            });

            if ($isAssigned) {
                $expected++;
            }

            $cursor->addDay();
        }

        return $expected;
    }

    private function isWorkingDay(DgContractSchedule $schedule, int $weekday): bool
    {
        $map = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
        $dayKey = $map[$weekday] ?? 'mon';

        $rules = $schedule->rules ?? [];
        if (array_key_exists($dayKey, $rules)) {
            $rule = $rules[$dayKey] ?? [];

            if (($rule['enabled'] ?? true) === false) {
                return false;
            }

            $hasStart = !empty($rule['start']);
            $hasEnd   = !empty($rule['end']);
            $hours    = (float) ($rule['hours'] ?? 0);

            if ($hasStart || $hasEnd || $hours > 0) {
                return true;
            }
        }

        $value = (float) ($schedule->{$dayKey} ?? 0);

        return $value > 0;
    }
}

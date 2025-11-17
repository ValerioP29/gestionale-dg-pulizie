<?php

namespace App\Jobs;

use App\Models\DgAnomaly;
use App\Models\DgContractSchedule;
use App\Models\DgReportCache;
use App\Models\DgSiteAssignment;
use App\Models\DgWorkSession;
use App\Models\User;
use App\Services\Justifications\JustificationCalendar;
use App\Support\ReportsCacheStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class GenerateReportsCache implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected ?string $periodStart = null,
        protected ?string $periodEnd = null,
    ) {
    }

    public function handle(): void
    {
        [$start, $end] = $this->resolvePeriod();

        ReportsCacheStatus::refresh([
            'period_start' => $start->toDateString(),
            'period_end' => $end->toDateString(),
            'source' => $this->periodStart && $this->periodEnd ? 'manual' : 'auto',
            'records' => 0,
        ]);

        $calendar = new JustificationCalendar();

        try {
            $sessions = DgWorkSession::query()
                ->select([
                    'id',
                    'user_id',
                    'site_id',
                    'resolved_site_id',
                    'session_date',
                    'worked_minutes',
                    'overtime_minutes',
                ])
                ->whereBetween('session_date', [$start->toDateString(), $end->toDateString()])
                ->whereNotNull('user_id')
                ->get();

            $grouped = $sessions->groupBy([
                fn (DgWorkSession $session) => $session->user_id,
                fn (DgWorkSession $session) => $session->resolved_site_id ?? $session->site_id,
            ], preserveKeys: true);

            if ($grouped->isEmpty()) {
                Log::info('ReportsCache: nessuna sessione da processare', [
                    'period_start' => $start->toDateString(),
                    'period_end' => $end->toDateString(),
                ]);

                ReportsCacheStatus::refresh([
                    'period_start' => $start->toDateString(),
                    'period_end' => $end->toDateString(),
                    'records' => 0,
                ]);

                return;
            }

            $userIds = $grouped->keys()->all();
            $contracts = $this->loadContracts($userIds);
            $contractWorkingDays = $this->calculateContractWorkingDays($contracts, $start, $end);
            $assignments = $this->loadAssignments($userIds, $start, $end);

            $processed = 0;

            foreach ($grouped as $userId => $bySite) {
                foreach ($bySite as $siteId => $records) {
                    if ($siteId === null || $records->isEmpty()) {
                        continue;
                    }

                    $sessionIds = $records->pluck('id')->all();
                    $workedMinutes = max(0, (int) $records->sum('worked_minutes'));
                    $overtimeMinutes = max(0, (int) $records->sum('overtime_minutes'));
                    $workedHours = round($workedMinutes / 60, 2);
                    $daysPresent = $records->where('worked_minutes', '>', 0)
                        ->pluck('session_date')
                        ->unique()
                        ->count();

                    $contractSchedule = $contracts[$userId] ?? null;
                    $sitesForUser = $bySite->keys()->count();
                    $assignmentSet = $assignments->get($userId, collect())->get($siteId, collect());
                    $expectedDays = $this->calculateExpectedDaysForSite(
                        $start->copy(),
                        $end->copy(),
                        $contractSchedule,
                        $assignmentSet,
                        $sitesForUser,
                        $daysPresent,
                        $contractWorkingDays[$userId] ?? 0,
                    );

                    $anomaliesQuery = DgAnomaly::query()
                        ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                        ->where('user_id', $userId);

                    if (! empty($sessionIds)) {
                        $anomaliesQuery->whereIn('session_id', $sessionIds);
                    } else {
                        $anomaliesQuery->whereRaw('1 = 0');
                    }

                    $lateEntries = (clone $anomaliesQuery)->where('type', 'late_entry')->count();
                    $earlyExits = (clone $anomaliesQuery)->where('type', 'early_exit')->count();
                    $absencesAnomalies = (clone $anomaliesQuery)->where('type', 'absence')->count();

                    $justifiedDays = $this->countJustifiedDaysForSite(
                        $calendar,
                        $userId,
                        $start->copy(),
                        $end->copy(),
                        $assignments->get($siteId, collect()),
                        $sitesForUser
                    );

                    $contractAbsences = max(0, $expectedDays - $daysPresent - $justifiedDays);
                    $daysAbsent = max($absencesAnomalies, $contractAbsences);

                    DgReportCache::updateOrCreate(
                        [
                            'user_id' => $userId,
                            'site_id' => $siteId,
                            'period_start' => $start->toDateString(),
                            'period_end' => $end->toDateString(),
                        ],
                        [
                            'resolved_site_id' => $siteId,
                            'worked_hours' => $workedHours,
                            'days_present' => $daysPresent,
                            'days_absent' => $daysAbsent,
                            'late_entries' => $lateEntries,
                            'early_exits' => $earlyExits,
                            'overtime_minutes' => $overtimeMinutes,
                            'generated_at' => now(),
                            'is_final' => false,
                        ],
                    );

                    $processed++;

                    if ($processed % 25 === 0) {
                        ReportsCacheStatus::refresh([
                            'period_start' => $start->toDateString(),
                            'period_end' => $end->toDateString(),
                            'records' => $processed,
                        ]);
                    }
                }
            }

            ReportsCacheStatus::refresh([
                'period_start' => $start->toDateString(),
                'period_end' => $end->toDateString(),
                'records' => $processed,
            ]);

            Log::info('ReportsCache: rigenerati report', [
                'period_start' => $start->toDateString(),
                'period_end' => $end->toDateString(),
                'records' => $processed,
            ]);
        } finally {
            ReportsCacheStatus::clear();
        }
    }

    private function resolvePeriod(): array
    {
        $start = $this->periodStart
            ? Carbon::parse($this->periodStart)->startOfDay()
            : null;

        $end = $this->periodEnd
            ? Carbon::parse($this->periodEnd)->endOfDay()
            : null;

        if (! $start || ! $end) {
            $anchor = now()->subMonthNoOverflow();
            $start ??= $anchor->copy()->startOfMonth();
            $end ??= $anchor->copy()->endOfMonth();
        }

        return [$start, $end];
    }

    private function loadContracts(array $userIds): Collection
    {
        if (empty($userIds)) {
            return collect();
        }

        return User::query()
            ->with('contractSchedule')
            ->whereIn('id', $userIds)
            ->get()
            ->mapWithKeys(fn ($user) => [$user->id => $user->contractSchedule]);
    }

    private function calculateContractWorkingDays(Collection $contracts, Carbon $start, Carbon $end): array
    {
        $result = [];

        foreach ($contracts as $id => $contract) {
            $result[$id] = $contract
                ? $this->countContractWorkingDays($start->copy(), $end->copy(), $contract)
                : 0;
        }

        return $result;
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
        int $fallbackTotal,
    ): int {
        if (! $schedule) {
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
        Collection $assignments,
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

            if (! $this->isWorkingDay($schedule, $weekday)) {
                $cursor->addDay();
                continue;
            }

            $isAssigned = $ranges->isEmpty() || $ranges->contains(function ($range) use ($cursor) {
                $from = $range['from'];
                $to = $range['to'];

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

            $hasStart = ! empty($rule['start']);
            $hasEnd = ! empty($rule['end']);
            $hours = (float) ($rule['hours'] ?? 0);

            if ($hasStart || $hasEnd || $hours > 0) {
                return true;
            }
        }

        $value = (float) ($schedule->{$dayKey} ?? 0);

        return $value > 0;
    }

    private function countJustifiedDaysForSite(
        JustificationCalendar $calendar,
        int $userId,
        Carbon $start,
        Carbon $end,
        Collection $assignments,
        int $sitesForUser,
    ): int {
        $covered = 0;
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $date = CarbonImmutable::parse($cursor->toDateString());

            if (! $calendar->hasFullDayCoverage($userId, $date)) {
                $cursor->addDay();
                continue;
            }

            if ($this->isAssignedToSiteOn($date, $assignments, $sitesForUser)) {
                $covered++;
            }

            $cursor->addDay();
        }

        return $covered;
    }

    private function isAssignedToSiteOn(
        CarbonImmutable $date,
        Collection $assignments,
        int $sitesForUser,
    ): bool {
        if ($assignments->isEmpty()) {
            return $sitesForUser <= 1;
        }

        return $assignments->contains(function ($assignment) use ($date) {
            $from = $assignment->assigned_from
                ? CarbonImmutable::parse($assignment->assigned_from)->startOfDay()
                : null;
            $to = $assignment->assigned_to
                ? CarbonImmutable::parse($assignment->assigned_to)->endOfDay()
                : null;

            if ($from && $date->lt($from)) {
                return false;
            }

            if ($to && $date->gt($to)) {
                return false;
            }

            return true;
        });
    }
}

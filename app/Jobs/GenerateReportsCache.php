<?php

namespace App\Jobs;

use App\Models\DgAnomaly;
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
        $startedAt = microtime(true);
        [$start, $end] = $this->resolvePeriod();

        Log::info('ReportsCache: avvio rigenerazione', [
            'period_start' => $start->toDateString(),
            'period_end' => $end->toDateString(),
            'source' => $this->periodStart && $this->periodEnd ? 'manual' : 'auto',
        ]);

        ReportsCacheStatus::refresh([
            'period_start' => $start->toDateString(),
            'period_end' => $end->toDateString(),
            'source' => $this->periodStart && $this->periodEnd ? 'manual' : 'auto',
            'records' => 0,
        ]);

        $calendar = new JustificationCalendar();

        try {
            $query = DgWorkSession::query()
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
                ->orderBy('id');

            $aggregates = [];
            $userIds = [];

            $query->chunkById(1000, function (Collection $sessions) use (&$aggregates, &$userIds) {
                foreach ($sessions as $session) {
                    $siteId = $session->resolved_site_id ?? $session->site_id;

                    if ($siteId === null) {
                        continue;
                    }

                    $key = $session->user_id.'|'.$siteId;

                    if (! isset($aggregates[$key])) {
                        $aggregates[$key] = [
                            'user_id' => $session->user_id,
                            'site_id' => $siteId,
                            'worked_minutes' => 0,
                            'overtime_minutes' => 0,
                            'session_ids' => [],
                            'dates' => [],
                        ];
                    }

                    $aggregates[$key]['worked_minutes'] += (int) $session->worked_minutes;
                    $aggregates[$key]['overtime_minutes'] += (int) $session->overtime_minutes;

                    if ((int) $session->worked_minutes > 0) {
                        $aggregates[$key]['dates'][$session->session_date] = true;
                    }

                    $aggregates[$key]['session_ids'][] = $session->id;
                    $userIds[$session->user_id] = true;
                }

                return true;
            });

            if (empty($aggregates)) {
                Log::info('ReportsCache: nessuna sessione da processare', [
                    'period_start' => $start->toDateString(),
                    'period_end' => $end->toDateString(),
                    'duration_ms' => round((microtime(true) - $startedAt) * 1000, 2),
                ]);

                ReportsCacheStatus::refresh([
                    'period_start' => $start->toDateString(),
                    'period_end' => $end->toDateString(),
                    'records' => 0,
                ]);

                return;
            }

            $userIds = array_keys($userIds);
            $users = $this->loadUsers($userIds);
            $contractWorkingDays = $this->calculateUserWorkingDays($users, $start, $end);
            $assignments = $this->loadAssignments($userIds, $start, $end);

            $sitesPerUser = [];

            foreach ($aggregates as $aggregate) {
                $sitesPerUser[$aggregate['user_id']] ??= [];
                $sitesPerUser[$aggregate['user_id']][$aggregate['site_id']] = true;
            }

            $processed = 0;
            $justificationsProcessed = 0;
            $anomaliesProcessed = 0;

            foreach ($aggregates as $aggregate) {
                $userId = $aggregate['user_id'];
                $siteId = $aggregate['site_id'];

                $sessionIds = $aggregate['session_ids'];
                $workedMinutes = max(0, (int) $aggregate['worked_minutes']);
                $overtimeMinutes = max(0, (int) $aggregate['overtime_minutes']);
                $workedHours = round($workedMinutes / 60, 2);
                $daysPresent = count($aggregate['dates']);

                $user = $users[$userId] ?? null;
                $sitesForUser = isset($sitesPerUser[$userId]) ? count($sitesPerUser[$userId]) : 0;
                $assignmentSet = $assignments->get($userId, collect())->get($siteId, collect());
                $expectedDays = $this->calculateExpectedDaysForSite(
                    $start->copy(),
                    $end->copy(),
                    $user,
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

                $anomaliesProcessed += $lateEntries + $earlyExits + $absencesAnomalies;

                $justifiedDays = $this->countJustifiedDaysForSite(
                    $calendar,
                    $userId,
                    $start->copy(),
                    $end->copy(),
                    $assignmentSet,
                    $sitesForUser
                );

                $justificationsProcessed += $justifiedDays;

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

            ReportsCacheStatus::refresh([
                'period_start' => $start->toDateString(),
                'period_end' => $end->toDateString(),
                'records' => $processed,
            ]);

            Log::info('ReportsCache: rigenerati report', [
                'period_start' => $start->toDateString(),
                'period_end' => $end->toDateString(),
                'records' => $processed,
                'justified_days' => $justificationsProcessed,
                'anomalies_processed' => $anomaliesProcessed,
                'duration_ms' => round((microtime(true) - $startedAt) * 1000, 2),
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

    private function loadUsers(array $userIds): Collection
    {
        if (empty($userIds)) {
            return collect();
        }

        return User::query()
            ->with('contractSchedule')
            ->whereIn('id', $userIds)
            ->get()
            ->mapWithKeys(fn ($user) => [$user->id => $user]);
    }

    private function calculateUserWorkingDays(Collection $users, Carbon $start, Carbon $end): array
    {
        $result = [];

        foreach ($users as $id => $user) {
            $result[$id] = $user
                ? $this->countUserWorkingDays($start->copy(), $end->copy(), $user)
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

    private function countUserWorkingDays(Carbon $start, Carbon $end, User $user): int
    {
        $days = 0;
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $weekday = $cursor->dayOfWeekIso - 1;

            if ($this->isWorkingDay($user, $weekday)) {
                $days++;
            }

            $cursor->addDay();
        }

        return $days;
    }

    private function calculateExpectedDaysForSite(
        Carbon $start,
        Carbon $end,
        ?User $user,
        Collection $assignments,
        int $siteCount,
        int $daysPresent,
        int $fallbackTotal,
    ): int {
        if (! $user) {
            return max($daysPresent, 0);
        }

        if ($assignments->isNotEmpty()) {
            return $this->calculateDaysWithAssignments($start->copy(), $end->copy(), $user, $assignments);
        }

        if ($siteCount > 1) {
            return max($daysPresent, 0);
        }

        return $fallbackTotal;
    }

    private function calculateDaysWithAssignments(
        Carbon $start,
        Carbon $end,
        User $user,
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

            if (! $this->isWorkingDay($user, $weekday)) {
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

    private function isWorkingDay(User $user, int $weekday): bool
    {
        $map = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
        $dayKey = $map[$weekday] ?? 'mon';

        $rules = $user->rules ?? [];

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

        $value = (float) ($user->{$dayKey} ?? 0);

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

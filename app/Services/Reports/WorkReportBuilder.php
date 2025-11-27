<?php

namespace App\Services\Reports;

use App\Models\DgAnomaly;
use App\Models\DgClient;
use App\Models\DgSite;
use App\Models\DgWorkSession;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WorkReportBuilder
{
    public function buildEmployeeReport(int $userId, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $user = User::with(['mainSite', 'mainSite.client'])->findOrFail($userId);

        $sessions = DgWorkSession::query()
            ->select([
                'id',
                'session_date',
                'resolved_site_id',
                'site_id',
                'worked_minutes',
                'overtime_minutes',
                'approval_status',
            ])
            ->with([
                'resolvedSite:id,name',
                'site:id,name',
            ])
            ->where('user_id', $userId)
            ->whereBetween('session_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('session_date')
            ->get();

        $sessionIds      = $sessions->pluck('id')->all();
        $anomaliesBySession = $this->anomaliesForSessions($sessionIds, $from, $to);
        $standalone         = $this->standaloneAnomalies($userId, $from, $to);

        $rows = $sessions->map(function (DgWorkSession $session) use ($anomaliesBySession) {
            $flags = $anomaliesBySession->get($session->id, collect());

            return [
                'date'      => CarbonImmutable::parse($session->session_date),
                'site'      => $session->resolvedSite?->name
                    ?? $session->site?->name
                    ?? '—',
                'hours'     => round(($session->worked_minutes ?? 0) / 60, 2),
                'overtime'  => round(($session->overtime_minutes ?? 0) / 60, 2),
                'status'    => $session->approval_status,
                'anomalies' => $flags
                    ->map(fn (string $type) => $this->labelForAnomaly($type))
                    ->values()
                    ->all(),
            ];
        });

        foreach ($standalone as $anomaly) {
            $rows->push([
                'date'      => CarbonImmutable::parse($anomaly->date),
                'site'      => '—',
                'hours'     => 0.0,
                'overtime'  => 0.0,
                'status'    => 'assenza',
                'anomalies' => [$this->labelForAnomaly($anomaly->type)],
            ]);
        }

        $rows           = $rows->sortBy('date')->values();
        $summaryTotals  = $this->employeeSummaryTotals($userId, $from, $to);
        $anomaliesCount = $this->anomaliesCountForSessions($sessionIds, $from, $to) + $standalone->count();

        return [
            'user'    => $user,
            'summary' => [
                'total_hours'     => round($summaryTotals['total_hours'], 2),
                'overtime_hours'  => round($summaryTotals['overtime_hours'], 2),
                'days_worked'     => $summaryTotals['days_worked'],
                'anomalies'       => $anomaliesCount,
            ],
            'rows'    => $rows,
        ];
    }

    public function buildSiteReport($siteId, CarbonImmutable $from, CarbonImmutable $to): array
    {
        // Normalizzazione difensiva: accetto int, Model, Collection
        if ($siteId instanceof Collection) {
            $siteId = $siteId->first()?->id ?? null;
        }

        if ($siteId instanceof DgSite) {
            $site = $siteId->load('client');
            $siteId = $site->id;
        } else {
            $site = DgSite::with('client')->findOrFail((int) $siteId);
        }

        $sessionScope = $this->sessionsForSite($siteId, $from, $to);

        $aggregates = (clone $sessionScope)
            ->select('user_id')
            ->selectRaw('COUNT(DISTINCT session_date) AS days')
            ->selectRaw('SUM(worked_minutes) AS worked_minutes')
            ->selectRaw('SUM(overtime_minutes) AS overtime_minutes')
            ->groupBy('user_id')
            ->get();

        $userIds = $aggregates->pluck('user_id')->all();
        $users   = User::query()
            ->select(['id', 'first_name', 'last_name', 'name'])
            ->whereIn('id', $userIds)
            ->get()
            ->keyBy('id');

        $anomaliesByUser = $this->anomaliesCountByUser($sessionScope, $from, $to);

        $byUser = $aggregates->map(function ($aggregate) use ($users, $anomaliesByUser) {
            $user     = $users->get($aggregate->user_id);
            $userName = $user?->full_name ?? $user?->name ?? '—';

            return [
                'user'      => $userName,
                'days'      => (int) $aggregate->days,
                'hours'     => round(((int) $aggregate->worked_minutes) / 60, 2),
                'overtime'  => round(((int) $aggregate->overtime_minutes) / 60, 2),
                'anomalies' => (int) ($anomaliesByUser[$aggregate->user_id] ?? 0),
            ];
        })->values();

        return [
            'site'    => $site,
            'summary' => [
                'total_hours'     => round($byUser->sum('hours'), 2),
                'overtime_hours'  => round($byUser->sum('overtime'), 2),
                'days_worked'     => (int) (clone $sessionScope)->distinct('session_date')->count('session_date'),
                'anomalies'       => $byUser->sum('anomalies'),
            ],
            'rows'    => $byUser,
        ];
    }

    public function buildClientReport($clientId, CarbonImmutable $from, CarbonImmutable $to): array
    {
        // Normalizzazione difensiva: int, Model, Collection
        if ($clientId instanceof Collection) {
            $clientId = $clientId->first()?->id ?? null;
        }

        if ($clientId instanceof DgClient) {
            $client   = $clientId->load('sites');
            $clientId = $client->id;
        } else {
            $client = DgClient::with('sites')->findOrFail((int) $clientId);
        }

        $siteIds = $client->sites->pluck('id')->all();

        if ($siteIds === []) {
            return [
                'client'  => $client,
                'summary' => [
                    'total_hours'     => 0,
                    'overtime_hours'  => 0,
                    'days_worked'     => 0,
                    'anomalies'       => 0,
                ],
                'rows'    => collect(),
            ];
        }

        $sessionScope = DgWorkSession::query()
            ->whereBetween('session_date', [$from->toDateString(), $to->toDateString()])
            ->where(function ($query) use ($siteIds) {
                $query->whereIn('resolved_site_id', $siteIds)
                    ->orWhere(function ($inner) use ($siteIds) {
                        $inner->whereNull('resolved_site_id')
                            ->whereIn('site_id', $siteIds);
                    });
            });

        $siteAggregates = (clone $sessionScope)
            ->selectRaw('COALESCE(resolved_site_id, site_id) AS effective_site_id')
            ->selectRaw('SUM(worked_minutes) AS worked_minutes')
            ->selectRaw('SUM(overtime_minutes) AS overtime_minutes')
            ->selectRaw('COUNT(DISTINCT session_date) AS days')
            ->groupBy('effective_site_id')
            ->get();

        $siteNames = DgSite::query()
            ->select(['id', 'name'])
            ->whereIn('id', $siteAggregates->pluck('effective_site_id')->filter()->all())
            ->get()
            ->keyBy('id');

        $anomaliesBySite = $this->anomaliesCountBySite($sessionScope, $from, $to);

        $bySite = $siteAggregates->map(function ($aggregate) use ($siteNames, $anomaliesBySite) {
            $siteName = $siteNames->get($aggregate->effective_site_id)?->name ?? 'Cantiere sprovvisto';

            return [
                'site'      => $siteName,
                'hours'     => round(((int) $aggregate->worked_minutes) / 60, 2),
                'overtime'  => round(((int) $aggregate->overtime_minutes) / 60, 2),
                'days'      => (int) $aggregate->days,
                'anomalies' => (int) ($anomaliesBySite[$aggregate->effective_site_id] ?? 0),
            ];
        })->values();

        return [
            'client'  => $client,
            'summary' => [
                'total_hours'     => round($bySite->sum('hours'), 2),
                'overtime_hours'  => round($bySite->sum('overtime'), 2),
                'days_worked'     => (int) (clone $sessionScope)->distinct('session_date')->count('session_date'),
                'anomalies'       => $bySite->sum('anomalies'),
            ],
            'rows'    => $bySite,
        ];
    }

    private function anomaliesForSessions(array $sessionIds, CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        if (empty($sessionIds)) {
            return collect();
        }

        return DgAnomaly::query()
            ->select(['session_id', 'type'])
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->whereIn('session_id', $sessionIds)
            ->orderBy('session_id')
            ->orderBy('type')
            ->get()
            ->groupBy('session_id')
            ->map(fn (Collection $items) => $items->pluck('type'));
    }

    private function standaloneAnomalies(int $userId, CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        return DgAnomaly::query()
            ->select(['date', 'type'])
            ->where('user_id', $userId)
            ->whereNull('session_id')
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->get();
    }

    private function employeeSummaryTotals(int $userId, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $totals = DgWorkSession::query()
            ->selectRaw('SUM(worked_minutes) AS worked_minutes')
            ->selectRaw('SUM(overtime_minutes) AS overtime_minutes')
            ->selectRaw('COUNT(DISTINCT session_date) AS days_worked')
            ->where('user_id', $userId)
            ->whereBetween('session_date', [$from->toDateString(), $to->toDateString()])
            ->first();

        return [
            'total_hours'    => ((int) ($totals?->worked_minutes ?? 0)) / 60,
            'overtime_hours' => ((int) ($totals?->overtime_minutes ?? 0)) / 60,
            'days_worked'    => (int) ($totals?->days_worked ?? 0),
        ];
    }

    private function anomaliesCountForSessions(array $sessionIds, CarbonImmutable $from, CarbonImmutable $to): int
    {
        if (empty($sessionIds)) {
            return 0;
        }

        return (int) DgAnomaly::query()
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->whereIn('session_id', $sessionIds)
            ->count();
    }

    private function sessionsForSite(int $siteId, CarbonImmutable $from, CarbonImmutable $to)
    {
        return DgWorkSession::query()
            ->whereBetween('session_date', [$from->toDateString(), $to->toDateString()])
            ->where(function ($query) use ($siteId) {
                $query->where('resolved_site_id', $siteId)
                    ->orWhere(function ($inner) use ($siteId) {
                        $inner->whereNull('resolved_site_id')
                            ->where('site_id', $siteId);
                    });
            });
    }

    private function anomaliesCountByUser($sessionScope, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $sessionIdsSubQuery = (clone $sessionScope)->select('id');

        return DgAnomaly::query()
            ->select('user_id')
            ->selectRaw('COUNT(*) AS total')
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->whereIn('session_id', $sessionIdsSubQuery)
            ->groupBy('user_id')
            ->pluck('total', 'user_id')
            ->toArray();
    }

    private function anomaliesCountBySite($sessionScope, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $sessionIdsSubQuery = (clone $sessionScope)->select('id', DB::raw('COALESCE(resolved_site_id, site_id) AS effective_site_id'));

        $anomalies = DB::table(DB::raw("({$sessionIdsSubQuery->toSql()}) AS site_sessions"))
            ->mergeBindings($sessionIdsSubQuery->getQuery())
            ->join('dg_anomalies', 'dg_anomalies.session_id', '=', 'site_sessions.id')
            ->whereBetween('dg_anomalies.date', [$from->toDateString(), $to->toDateString()])
            ->groupBy('site_sessions.effective_site_id')
            ->selectRaw('site_sessions.effective_site_id, COUNT(*) AS total')
            ->pluck('total', 'effective_site_id');

        return $anomalies->toArray();
    }

    private function labelForAnomaly(?string $type): string
    {
        return match ($type) {
            'missing_punch'     => 'Timbratura mancante',
            'absence'           => 'Assenza',
            'unplanned_day'     => 'Giorno non previsto',
            'late_entry'        => 'Ritardo',
            'early_exit'        => 'Uscita anticipata',
            'overtime'          => 'Straordinario',
            'irregular_session' => 'Sessione irregolare',
            'underwork'         => 'Ore insufficienti',
            default             => ucfirst((string) $type),
        };
    }
}

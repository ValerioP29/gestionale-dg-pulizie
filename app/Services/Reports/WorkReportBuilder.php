<?php

namespace App\Services\Reports;

use App\Models\DgAnomaly;
use App\Models\DgClient;
use App\Models\DgSite;
use App\Models\DgWorkSession;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class WorkReportBuilder
{
    public function buildEmployeeReport(int $userId, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $user = User::with(['mainSite', 'mainSite.client'])->findOrFail($userId);

        $sessions = DgWorkSession::query()
            ->with(['resolvedSite', 'site'])
            ->where('user_id', $userId)
            ->whereBetween('session_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('session_date')
            ->get();

        $anomalies  = $this->anomaliesForSessions($sessions->pluck('id')->all(), $from, $to);
        $standalone = $this->standaloneAnomalies($userId, $from, $to);

        $rows = $sessions->map(function (DgWorkSession $session) use ($anomalies) {
            $flags = $anomalies->get($session->id, collect());

            return [
                'date'      => CarbonImmutable::parse($session->session_date),
                'site'      => $session->resolvedSite?->name
                    ?? $session->site?->name
                    ?? '—',
                'hours'     => round(($session->worked_minutes ?? 0) / 60, 2),
                'overtime'  => round(($session->overtime_minutes ?? 0) / 60, 2),
                'status'    => $session->approval_status,
                'anomalies' => $flags
                    ->map(fn (DgAnomaly $anomaly) => $this->labelForAnomaly($anomaly->type))
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
        $totalHours     = $rows->sum('hours');
        $totalOvertime  = $rows->sum('overtime');
        $daysWorked     = $sessions->pluck('session_date')->unique()->count();
        $anomaliesCount = $anomalies->flatten()->count() + $standalone->count();

        return [
            'user'    => $user,
            'summary' => [
                'total_hours'     => round($totalHours, 2),
                'overtime_hours'  => round($totalOvertime, 2),
                'days_worked'     => $daysWorked,
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

        $sessions = DgWorkSession::query()
            ->with(['user'])
            ->whereBetween('session_date', [$from->toDateString(), $to->toDateString()])
            ->where(function ($query) use ($siteId) {
                $query->where('resolved_site_id', $siteId)
                    ->orWhere(function ($inner) use ($siteId) {
                        $inner->whereNull('resolved_site_id')
                            ->where('site_id', $siteId);
                    });
            })
            ->get();

        $anomalies = $this->anomaliesForSessions($sessions->pluck('id')->all(), $from, $to);

        $byUser = $sessions->groupBy('user_id')->map(function (Collection $items, $userId) use ($anomalies) {
            $user        = $items->first()->user;
            $sessionIds  = $items->pluck('id')->all();
            $anomalyCount = $anomalies->only($sessionIds)->flatten()->count();

            return [
                'user'      => $user?->full_name ?? $user?->name ?? '—',
                'days'      => $items->pluck('session_date')->unique()->count(),
                'hours'     => round($items->sum('worked_minutes') / 60, 2),
                'overtime'  => round($items->sum('overtime_minutes') / 60, 2),
                'anomalies' => $anomalyCount,
            ];
        })->values();

        return [
            'site'    => $site,
            'summary' => [
                'total_hours'     => round($byUser->sum('hours'), 2),
                'overtime_hours'  => round($byUser->sum('overtime'), 2),
                'days_worked'     => $sessions->pluck('session_date')->unique()->count(),
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

        $sessions = DgWorkSession::query()
            ->whereBetween('session_date', [$from->toDateString(), $to->toDateString()])
            ->where(function ($query) use ($siteIds) {
                $query->whereIn('resolved_site_id', $siteIds)
                    ->orWhere(function ($inner) use ($siteIds) {
                        $inner->whereNull('resolved_site_id')
                            ->whereIn('site_id', $siteIds);
                    });
            })
            ->with(['resolvedSite', 'site'])
            ->get();

        $anomalies = $this->anomaliesForSessions($sessions->pluck('id')->all(), $from, $to);

        $bySite = $sessions->groupBy(function (DgWorkSession $session) {
            return $session->resolved_site_id ?? $session->site_id;
        })->map(function (Collection $items, $siteId) use ($anomalies) {

            /** @var DgWorkSession $first */
            $first    = $items->first();
            $siteName = $first->resolvedSite?->name
                ?? $first->site?->name
                ?? 'Cantiere sprovvisto';

            $sessionIds   = $items->pluck('id')->all();
            $anomalyCount = $anomalies->only($sessionIds)->flatten()->count();

            return [
                'site'      => $siteName,
                'hours'     => round($items->sum('worked_minutes') / 60, 2),
                'overtime'  => round($items->sum('overtime_minutes') / 60, 2),
                'days'      => $items->pluck('session_date')->unique()->count(),
                'anomalies' => $anomalyCount,
            ];
        })->values();

        return [
            'client'  => $client,
            'summary' => [
                'total_hours'     => round($bySite->sum('hours'), 2),
                'overtime_hours'  => round($bySite->sum('overtime'), 2),
                'days_worked'     => $sessions->pluck('session_date')->unique()->count(),
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
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->whereIn('session_id', $sessionIds)
            ->get()
            ->groupBy('session_id');
    }

    private function standaloneAnomalies(int $userId, CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        return DgAnomaly::query()
            ->where('user_id', $userId)
            ->whereNull('session_id')
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->get();
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

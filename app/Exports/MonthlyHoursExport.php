<?php

namespace App\Exports;

use App\Exports\Sheets\MonthlyCalendarSheet;
use App\Exports\Sheets\MonthlyOverviewSheet;
use App\Exports\Sheets\MonthlySitesSheet;
use App\Models\DgAnomaly;
use App\Models\DgWorkSession;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MonthlyHoursExport implements WithMultipleSheets
{
    protected CarbonImmutable $start;
    protected CarbonImmutable $end;

    public function __construct(protected int $year, protected int $month)
    {
        $this->start = CarbonImmutable::create($year, $month, 1)->startOfMonth();
        $this->end = $this->start->endOfMonth();
    }

    public function sheets(): array
    {
        $dataset = $this->buildDataset();

        return [
            new MonthlyOverviewSheet($dataset),
            new MonthlyCalendarSheet($dataset),
            new MonthlySitesSheet($dataset),
        ];
    }

    private function buildDataset(): array
    {
        $sessions = DgWorkSession::query()
            ->with(['user.mainSite.client', 'resolvedSite', 'site'])
            ->whereBetween('session_date', [$this->start->toDateString(), $this->end->toDateString()])
            ->whereNotNull('user_id')
            ->get();

        $sessionsByUser = $sessions->groupBy('user_id');

        $users = User::query()
            ->whereIn('id', $sessionsByUser->keys()->all())
            ->with(['mainSite.client'])
            ->get();

        $anomalies = DgAnomaly::query()
            ->whereBetween('date', [$this->start->toDateString(), $this->end->toDateString()])
            ->with('session')
            ->get();

        $anomaliesByUser = $anomalies->groupBy('user_id');
        $anomaliesBySite = $anomalies->groupBy(function (DgAnomaly $anomaly) {
            return $anomaly->session?->resolved_site_id ?? $anomaly->session?->site_id;
        });

        $daysInMonth = $this->start->daysInMonth;

        $calendarRows = $users->map(function (User $user) use ($sessionsByUser, $daysInMonth, $anomaliesByUser) {
            $sessions = $sessionsByUser->get($user->id, collect());
            $byDate = $sessions->keyBy(fn ($session) => CarbonImmutable::parse($session->session_date)->toDateString());

            $giorni = [];
            $total = 0.0;
            $overtime = 0.0;

            for ($d = 1; $d <= $daysInMonth; $d++) {
                $date = $this->start->day($d)->toDateString();
                $session = $byDate->get($date);
                $hours = $session ? round(($session->worked_minutes ?? 0) / 60, 2) : '';
                $giorni[$d] = $hours === 0.0 ? '' : $hours;

                if ($session) {
                    $total += $hours !== '' ? (float) $hours : 0;
                    $overtime += round(($session->overtime_minutes ?? 0) / 60, 2);
                }
            }

            $notes = $anomaliesByUser->get($user->id, collect())
                ->map(fn (DgAnomaly $anomaly) => $this->labelForAnomaly($anomaly->type))
                ->unique()
                ->implode(', ');

            return [
                'user_id' => $user->id,
                'utente' => $user->full_name,
                'matricola' => $user->payroll_code,
                'cliente_cod' => $user->mainSite?->client?->payroll_client_code ?? '',
                'site_cod' => $user->mainSite?->payroll_site_code ?? '',
                'cliente_nome' => $user->mainSite?->client?->name ?? '',
                'cantiere' => $user->mainSite?->name ?? '',
                'hired_at' => $user->hired_at?->format('d/m/Y') ?? '',
                'end_at' => $user->contract_end_at?->format('d/m/Y') ?? '',
                'giorni' => $giorni,
                'total_hours' => round($total, 2),
                'overtime_hours' => round($overtime, 2),
                'notes' => $notes,
            ];
        })->values();

        $overview = $calendarRows->map(function (array $row) use ($anomaliesByUser) {
            $userId = $row['user_id'] ?? null;
            return [
                'name' => $row['utente'],
                'site' => $row['cantiere'],
                'client' => $row['cliente_nome'],
                'hours' => $row['total_hours'],
                'overtime' => $row['overtime_hours'],
                'notes' => $row['notes'],
                'days' => collect($row['giorni'])->filter(fn ($value) => $value !== '')->count(),
                'anomalies' => $userId ? $anomaliesByUser->get($userId, collect())->count() : 0,
            ];
        });

        $sites = $sessions->groupBy(fn ($session) => $session->resolved_site_id ?? $session->site_id)
            ->map(function (Collection $items, $siteId) use ($anomaliesBySite) {
                $first = $items->first();
                $siteName = $first->resolvedSite?->name ?? $first->site?->name ?? 'Cantiere';
                $clientName = $first->resolvedSite?->client?->name ?? $first->site?->client?->name ?? '—';

                return [
                    'site' => $siteName,
                    'client' => $clientName,
                    'hours' => round($items->sum('worked_minutes') / 60, 2),
                    'overtime' => round($items->sum('overtime_minutes') / 60, 2),
                    'days' => $items->pluck('session_date')->unique()->count(),
                    'employees' => $items->pluck('user_id')->unique()->count(),
                    'anomalies' => $anomaliesBySite->get($siteId, collect())->count(),
                ];
            })
            ->values();

        return [
            'start' => $this->start,
            'end' => $this->end,
            'calendar' => $calendarRows,
            'overview' => $overview,
            'sites' => $sites,
        ];
    }

    private function labelForAnomaly(?string $type): string
    {
        return match ($type) {
            'missing_punch' => 'Timbratura mancante',
            'absence' => 'Assenza',
            'unplanned_day' => 'Giorno non previsto',
            'late_entry' => 'Ritardo',
            'early_exit' => 'Uscita anticipata',
            'overtime' => 'Straordinario',
            'irregular_session' => 'Sessione irregolare',
            default => ucfirst((string) $type),
        };
    }
}

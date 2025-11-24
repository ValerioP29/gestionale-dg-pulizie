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
            new MonthlyCalendarSheet($dataset),
            new MonthlyOverviewSheet($dataset),
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

        // ✔️ giorni reali del mese
        $daysInMonth = $this->start->daysInMonth;

        // ==========================
        // CALENDAR (per Daily Sheet)
        // ==========================
        $calendarRows = $users->map(function (User $user) use ($sessionsByUser, $daysInMonth, $anomaliesByUser) {
            $sessions = $sessionsByUser->get($user->id, collect());
            $byDate = $sessions->keyBy(fn ($session) =>
                CarbonImmutable::parse($session->session_date)->toDateString()
            );

            $contractHours = $this->contractHoursFor($user);

            $giorni = [];
            $total = 0.0;
            $overtime = 0.0;

            // ✔️ usa solo i giorni veri del mese
            for ($d = 1; $d <= $daysInMonth; $d++) {

                $date = $this->start->day($d)->toDateString();
                $session = $byDate->get($date);

                if ($session) {
                    $hours = round(($session->worked_minutes ?? 0) / 60, 2);
                    $giorni[$d] = $hours > 0 ? $hours : '';
                    $total += $hours;
                    $overtime += round(($session->overtime_minutes ?? 0) / 60, 2);
                } else {
                    $giorni[$d] = '';
                }
            }

            $notes = $anomaliesByUser->get($user->id, collect())
                ->map(fn (DgAnomaly $anomaly) => $this->labelForAnomaly($anomaly->type))
                ->unique()
                ->implode(', ');

            $notesDetail = $anomaliesByUser->get($user->id, collect())
                ->map(function (DgAnomaly $anomaly) {
                    $label = $this->labelForAnomaly($anomaly->type);
                    $note = trim((string) ($anomaly->note ?? ''));

                    return $note !== ''
                        ? sprintf('%s - %s', $label, $note)
                        : $label;
                })
                ->filter()
                ->implode("\n");

            return [
                'tipologia' => 'Dipendente',
                'user_id' => $user->id,
                'utente' => $user->full_name,
                'first_name' => $user->first_name ?? '',
                'last_name' => $user->last_name ?? '',
                'matricola' => $user->payroll_code,
                'cliente_cod' => $user->mainSite?->client?->payroll_client_code ?? '',
                'site_cod' => $user->mainSite?->payroll_site_code ?? '',
                'cliente_nome' => $user->mainSite?->client?->name ?? '',
                'cantiere' => $user->mainSite?->name ?? '',
                'hired_at' => $user->hired_at?->format('d/m/Y') ?? '',
                'end_at' => $user->contract_end_at?->format('d/m/Y') ?? '',
                'contract_week' => $contractHours,
                'contract_week_total' => round(array_sum($contractHours), 2),
                'giorni' => $giorni, // ✔️ solo giorni reali
                'total_hours' => round($total, 2),
                'overtime_hours' => round($overtime, 2),
                'notes' => $notes,
                'notes_detail' => $notesDetail,
            ];
        })->values();

        // ==========================
        // OVERVIEW (per Overview Sheet)
        // ==========================
        $overview = $calendarRows->map(function (array $row) use ($anomaliesByUser) {
            $userId = $row['user_id'] ?? null;

            return [
                'name' => $row['utente'],
                'site' => $row['cantiere'],
                'client' => $row['cliente_nome'],
                'hours' => $row['total_hours'],
                'overtime' => $row['overtime_hours'],
                'notes' => $row['notes'],
                'days' => collect($row['giorni'])->filter(fn ($v) => $v !== '')->count(),
                'anomalies' => $userId ? $anomaliesByUser->get($userId, collect())->count() : 0,
            ];
        });

        // ==========================
        // SITES (per Sites Sheet)
        // ==========================
        $sites = $sessions->groupBy(fn ($session) =>
            $session->resolved_site_id ?? $session->site_id
        )
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
                    'anomalies' =>
                        $anomaliesBySite->get($siteId, collect())->count(),
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


    private function contractHoursFor(User $user): array
    {
        $days = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
        $hours = [];

        foreach ($days as $day) {
            $hours[$day] = (float) ($user->{$day} ?? 0);
        }

        if ($user->contractSchedule) {
            $rules = $user->contractSchedule->rules ?? [];

            foreach ($days as $day) {
                if ($hours[$day] <= 0 && ! empty($rules[$day]['hours'])) {
                    $hours[$day] = (float) $rules[$day]['hours'];
                }
            }
        }

        return $hours;
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
            'underwork' => 'Ore insufficienti',
            default => ucfirst((string) $type),
        };
    }
}

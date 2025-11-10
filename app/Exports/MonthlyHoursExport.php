<?php

namespace App\Exports;

use App\Models\User;
use App\Models\DgWorkSession;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class MonthlyHoursExport implements FromView
{
    protected int $year;
    protected int $month;

    public function __construct(int $year, int $month)
    {
        $this->year  = $year;
        $this->month = $month;
    }

    public function view(): View
    {
        $start = Carbon::create($this->year, $this->month, 1);
        $end   = $start->copy()->endOfMonth();

        $sessions = DgWorkSession::whereBetween('session_date', [$start, $end])
            ->get()
            ->groupBy('user_id');

        $users = User::employees()
            ->with(['mainSite.client'])
            ->orderBy('last_name')
            ->get()
            ->map(function ($u) use ($start, $sessions) {
                $userSessions = $sessions->get($u->id, collect())->keyBy('session_date');

                $giorni = [];
                $daysInMonth = $start->daysInMonth;
                $overtime = 0;
                $total = 0;

                for ($d = 1; $d <= $daysInMonth; $d++) {
                    $date = $start->copy()->day($d)->toDateString();
                    $session = $userSessions->get($date);

                    if ($session) {
                        $h = round(($session->worked_minutes ?? 0) / 60, 2);
                        $o = round(($session->overtime_minutes ?? 0) / 60, 2);
                        $giorni[$d] = $h > 0 ? $h : '';
                        $total += $h;
                        $overtime += $o;
                    } else {
                        $giorni[$d] = '';
                    }
                }

                return [
                    'utente'       => $u->full_name,
                    'matricola'    => $u->payroll_code,
                    'cliente_cod'  => $u->mainSite?->client?->payroll_client_code ?? '',
                    'site_cod'     => $u->mainSite?->payroll_site_code ?? '',
                    'cliente_nome' => $u->mainSite?->client?->name ?? '',
                    'cantiere'     => $u->mainSite?->name ?? '',
                    'hired_at'     => $u->hired_at?->format('d/m/Y') ?? '',
                    'end_at'       => $u->contract_end_at?->format('d/m/Y') ?? '',
                    'giorni'       => $giorni,
                    'total_hours'  => number_format($total, 2, ',', ''),
                    'overtime'     => number_format($overtime, 2, ',', ''),
                ];
            });

        return view('exports.monthly_hours_matrix', [
            'rows' => $users,
            'year' => $this->year,
            'month' => $this->month,
        ]);
    }
}

<?php

namespace App\Exports;

use App\Models\User;
use App\Models\DgWorkSession;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\{FromView, WithStyles, ShouldAutoSize};
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MonthlyHoursExport implements FromView, WithStyles, ShouldAutoSize
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

        $users = User::employees()
            ->with(['mainSite.client'])
            ->orderBy('last_name')
            ->get()
            ->map(function ($u) use ($start, $end) {
                
                $sessions = DgWorkSession::where('user_id', $u->id)
                    ->whereBetween('session_date', [$start, $end])
                    ->get();

                $days = [
                    'lun' => 0,
                    'mar' => 0,
                    'mer' => 0,
                    'gio' => 0,
                    'ven' => 0,
                    'sab' => 0,
                    'dom' => 0,
                ];

                $overtime = 0;

                foreach ($sessions as $s) {
                    $dow = strtolower($s->session_date->isoFormat('ddd')); // lun, mar, mer ecc.
                    if (isset($days[$dow])) {
                        $days[$dow] += round(($s->worked_minutes ?? 0) / 60, 2);
                    }
                    $overtime += round(($s->overtime_minutes ?? 0) / 60, 2);
                }

                $total = array_sum($days);

                return [
                    'utente'       => $u->full_name,
                    'matricola'    => $u->payroll_code,
                    'cliente_cod'  => $u->mainSite->client->payroll_client_code ?? '',
                    'site_cod'     => $u->mainSite->payroll_site_code ?? '',
                    'cliente_nome' => $u->mainSite->client->name ?? '',
                    'cantiere'     => $u->mainSite->name ?? '',
                    'hired_at'     => $u->hired_at?->format('d/m/Y') ?? '',
                    'end_at'       => $u->contract_end_at?->format('d/m/Y') ?? '',
                    'mon' => $days['lun'],
                    'tue' => $days['mar'],
                    'wed' => $days['mer'],
                    'thu' => $days['gio'],
                    'fri' => $days['ven'],
                    'sat' => $days['sab'],
                    'sun' => $days['dom'],
                    'total_hours' => $total,
                    'contract'    => $u->contract_hours_monthly ?? '',
                    'overtime'    => $overtime,
                ];
            });

        return view('exports.monthly_hours', [
            'users' => $users,
            'year'  => $this->year,
            'month' => $this->month,
        ]);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

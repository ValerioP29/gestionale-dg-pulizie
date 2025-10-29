<?php

namespace App\Services\Reports;

use App\Models\DgWorkSession;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;

class MonthlyHoursReport
{
    public function build(int $year, int $month): Collection
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end   = (clone $start)->endOfMonth();

        // Precarico relazioni
        $sessions = DgWorkSession::query()
            ->with([
                'user:id,first_name,last_name,payroll_code,hired_at,contract_end_at,contract_hours_monthly',
                'site:id,name,type,client_id,payroll_site_code',
                'site.client:id,name,payroll_client_code',
                'resolvedSite:id,name,type,client_id,payroll_site_code', // in caso usi resolved
                'resolvedSite.client:id,name,payroll_client_code',
            ])
            ->whereBetween('session_date', [$start->toDateString(), $end->toDateString()])
            ->get();

        $byUser = $sessions->groupBy('user_id');

        return $byUser->map(function (Collection $rows) {
            $first = $rows->first();
            $user  = $first->user ?? null;

            // Sito prevalente per minuti (se hai resolvedSite usalo, altrimenti site)
            $siteId = $rows->groupBy(fn($s) => $s->resolved_site_id ?: $s->site_id)
                ->map(fn($g) => $g->sum('worked_minutes'))
                ->sortDesc()
                ->keys()
                ->first();

            $site = $rows->first(function ($s) use ($siteId) {
                return ($s->resolved_site_id ?: $s->site_id) == $siteId;
            });

            $siteModel = $site?->resolvedSite ?: $site?->site;

            // Postgres DOW: 0=Dom ... 6=Sab
            $weekdayMinutes = [
                0 => 0, // Dom
                1 => 0, // Lun
                2 => 0, // Mar
                3 => 0, // Mer
                4 => 0, // Gio
                5 => 0, // Ven
                6 => 0, // Sab
            ];

            $worked = 0;
            $overtime = 0;

            foreach ($rows as $s) {
                $dow = (int) Carbon::parse($s->session_date)->dayOfWeek; // 0..6
                $weekdayMinutes[$dow] += (int) ($s->worked_minutes ?? 0);
                $worked   += (int) ($s->worked_minutes ?? 0);
                $overtime += (int) ($s->overtime_minutes ?? 0); // << usa la colonna giusta
            }

            $fmtH = fn(int $m) => $m ? number_format($m / 60, 2, ',', '') : '';

            return [
                'tipologia'            => $siteModel?->type ?? '',
                'cod_ragg_cli'         => $siteModel?->client?->payroll_client_code ?? '',
                'cod_cli'              => $siteModel?->payroll_site_code ?? '',
                'cliente'              => $siteModel?->client?->name ?? '',
                'cantiere'             => $siteModel?->name ?? '',
                'matricola'            => $user?->payroll_code ?? '',
                'cognome'              => $user?->last_name ?? '',
                'nome'                 => $user?->first_name ?? '',
                'data_assunzione'      => optional($user?->hired_at)->format('d/m/Y'),
                'data_scadenza'        => optional($user?->contract_end_at)->format('d/m/Y'),
                // LUN->DOM come nel layout
                'lun'                  => $fmtH($weekdayMinutes[1]),
                'mar'                  => $fmtH($weekdayMinutes[2]),
                'mer'                  => $fmtH($weekdayMinutes[3]),
                'gio'                  => $fmtH($weekdayMinutes[4]),
                'ven'                  => $fmtH($weekdayMinutes[5]),
                'sab'                  => $fmtH($weekdayMinutes[6]),
                'dom'                  => $fmtH($weekdayMinutes[0]),
                'totale_ore_contratto' => $user?->contract_hours_monthly ? number_format($user->contract_hours_monthly, 2, ',', '') : '',
                'straordinari'         => $fmtH($overtime),
            ];
        })->values();
    }
}

<?php

namespace App\Jobs;

use App\Models\DgWorkSession;
use App\Models\DgReportCache;
use App\Models\DgAnomaly;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

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
                $daysPresent     = $records->where('worked_minutes', '>', 0)->count();

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
                $absences    = (clone $anomaliesQuery)->where('type', 'absence')->count();

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
                        'days_absent'      => $absences,
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
}

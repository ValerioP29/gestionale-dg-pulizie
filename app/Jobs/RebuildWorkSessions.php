<?php

namespace App\Jobs;

use App\Models\DgPunch;
use App\Models\DgWorkSession;
use App\Services\Anomalies\AnomalyEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class RebuildWorkSessions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected ?string $date;

    public function __construct(?string $date = null)
    {
        $this->date = $date;
    }

  public function handle(): void
    {
        $engine = new AnomalyEngine();
        $count  = 0;

        // Se passo una data → ricostruisce solo quella data
        if ($this->date) {
            $targetDate = Carbon::parse($this->date)->toDateString();

            $punches = DgPunch::query()
                ->whereDate('created_at', $targetDate)
                ->orderBy('created_at')
                ->get()
                ->groupBy(['user_id', 'site_id']);

            foreach ($punches as $userId => $bySite) {
                foreach ($bySite as $siteId => $records) {
                    $session = $this->rebuildSession($userId, $siteId, $records, $engine);
                    $count++;
                }
            }

            info("RebuildWorkSessions → ricostruite {$count} sessioni per $targetDate");
            return;
        }

        // ✅ Se NON passo data → ricostruisce TUTTO
        // prendi tutte le date disponibili
        $dates = DgPunch::query()
            ->selectRaw("DATE(created_at) as d")
            ->groupBy('d')
            ->orderBy('d')
            ->pluck('d');

        foreach ($dates as $date) {
            $punches = DgPunch::query()
                ->whereDate('created_at', $date)
                ->orderBy('created_at')
                ->get()
                ->groupBy(['user_id', 'site_id']);

            foreach ($punches as $userId => $bySite) {
                foreach ($bySite as $siteId => $records) {
                    $session = $this->rebuildSession($userId, $siteId, $records, $engine);
                    $count++;
                }
            }
        }

        info("RebuildWorkSessions → ricostruite {$count} sessioni totali");
    }

    // ✅ estrai la logica qui
    private function rebuildSession($userId, $siteId, $records, $engine)
    {
        $checkIn  = $records->firstWhere('type', 'check_in');
        $checkOut = $records->reverse()->firstWhere('type', 'check_out');

        $worked = 0;
        $status = 'incomplete';

        if ($checkIn && $checkOut && $checkOut->created_at->gt($checkIn->created_at)) {
            $worked = $checkOut->created_at->diffInMinutes($checkIn->created_at) - 30;
            $status = $worked > 0 ? 'complete' : 'invalid';
        }

        $session = DgWorkSession::updateOrCreate(
            [
                'user_id'      => $userId,
                'session_date' => $checkIn?->created_at?->toDateString()
                    ?? $checkOut?->created_at?->toDateString()
                    ?? now()->toDateString(),
            ],
            [
                'site_id'        => $siteId,
                'check_in'       => $checkIn?->created_at,
                'check_out'      => $checkOut?->created_at,
                'worked_minutes' => max(0, $worked),
                'status'         => $status,
                'source'         => 'rebuild',
            ]
        );

        $engine->evaluateSession($session);
        return $session;
    }

}

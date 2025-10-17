<?php

namespace App\Jobs;

use App\Models\DgPunch;
use App\Models\DgWorkSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\CarbonImmutable;

class GenerateWorkSessions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected ?string $date;

    public function __construct(?string $date = null)
    {
        $this->date = $date;
    }

    public function handle(): void
    {
        $date = $this->date
            ? CarbonImmutable::parse($this->date)->toDateString()
            : now()->subDay()->toDateString();

        Log::info("🔄 Avvio calcolo sessioni per {$date}");

        $punches = DgPunch::whereDate('created_at', $date)
            ->orderBy('user_id')
            ->orderBy('created_at')
            ->get()
            ->groupBy('user_id');

        foreach ($punches as $userId => $records) {
            $siteId = $records->first()->site_id ?? null;
            $checkIn = $records->firstWhere('type', 'check_in');
            $checkOut = $records->lastWhere('type', 'check_out');

            if (!$checkIn || !$checkOut) {
                DgWorkSession::updateOrCreate(
                    ['user_id' => $userId, 'site_id' => $siteId, 'session_date' => $date],
                    ['status' => 'incomplete', 'worked_minutes' => 0]
                );
                Log::warning("⚠️ Utente {$userId}: timbrature incomplete per {$date}");
                continue;
            }

            $workedMinutes = $checkIn->created_at->diffInMinutes($checkOut->created_at);
            $status = $checkOut->created_at->lt($checkIn->created_at) ? 'invalid' : 'complete';

            DgWorkSession::updateOrCreate(
                [
                    'user_id' => $userId,
                    'site_id' => $siteId,
                    'session_date' => $date,
                ],
                [
                    'check_in' => $checkIn->created_at,
                    'check_out' => $checkOut->created_at,
                    'worked_minutes' => $workedMinutes,
                    'status' => $status,
                ]
            );

            Log::info("✅ Sessione {$status} per utente {$userId} ({$workedMinutes} min)");
        }

        Log::info("🏁 Calcolo sessioni completato per {$date}");
    }
}

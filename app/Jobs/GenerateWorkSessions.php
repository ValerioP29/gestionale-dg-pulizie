<?php

namespace App\Jobs;

use App\Models\DgPunch;
use App\Models\DgWorkSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

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
        $targetDate = $this->date
            ? Carbon::parse($this->date)->toDateString()
            : now()->subDay()->toDateString();

        // Prendo tutti i punch del giorno (usando created_at come fallback)
        $punches = DgPunch::query()
            ->whereDate('created_at', $targetDate) // se usi payload punched_at lato DB, cambia qui
            ->orderBy('created_at')
            ->get();

        // Raggruppo per utente (il sito lo lasciamo risolvere via SiteResolver)
        $byUser = $punches->groupBy('user_id');

        foreach ($byUser as $userId => $rows) {
            // Per ogni utente del giorno cerco primo check_in e ultimo check_out
            $checkIn  = $rows->first(function ($p) { return $p->type === 'check_in'; });
            $checkOut = $rows->reverse()->first(function ($p) { return $p->type === 'check_out'; });

            $session = DgWorkSession::firstOrNew([
                'user_id'      => $userId,
                'session_date' => $targetDate,
            ]);

            $session->site_id   = $session->site_id ?: ($checkIn?->site_id ?? $checkOut?->site_id);
            $session->check_in  = $checkIn  ? $checkIn->punchInstant()  : null;
            $session->check_out = $checkOut ? $checkOut->punchInstant() : null;

            // Non impostare worked_minutes / status: ci pensa l’Observer
            $session->save();
        }
    }
}

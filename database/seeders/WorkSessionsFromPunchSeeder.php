<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\DgPunch;
use App\Models\DgWorkSession;
use App\Services\Anomalies\AnomalyEngine;
use Carbon\Carbon;
use DB;

class WorkSessionsFromPunchSeeder extends Seeder
{
    public function run(): void
    {
        $engine = new AnomalyEngine();

        // Raggruppa punch per utente + giorno
        $grouped = DgPunch::query()
            ->orderBy('created_at')
            ->get()
            ->groupBy(function($p) {
                return $p->user_id . '_' . Carbon::parse($p->created_at)->toDateString();
            });

        foreach ($grouped as $key => $punches) {

            $example = $punches->first();
            $date    = Carbon::parse($example->created_at)->toDateString();

            // separa check-in e check-out
            $inPunches  = $punches->where('type','check_in');
            $outPunches = $punches->where('type','check_out');

            $checkIn  = $inPunches->min('created_at');  // primo ingresso
            $checkOut = $outPunches->max('created_at'); // ultima uscita

            $worked = 0;
            if ($checkIn && $checkOut) {
                $worked = Carbon::parse($checkOut)->diffInMinutes(Carbon::parse($checkIn));
            }

            if (!$checkIn && !$checkOut) {
                $status = 'invalid';
            } elseif ($checkIn && !$checkOut) {
                $status = 'incomplete';
            } elseif ($worked > 0) {
                $status = 'complete';
            } else {
                $status = 'invalid';
            }

            $session = DgWorkSession::create([
                'user_id'        => $example->user_id,
                'site_id'        => $example->site_id,
                'session_date'   => $date,
                'check_in'       => $checkIn,
                'check_out'      => $checkOut ?: null,
                'worked_minutes' => $worked,
                'status'         => $status,
                'source'         => 'seed',
            ]);

            // collega punch alla sessione
            DgPunch::whereIn('id', $punches->pluck('id'))
                ->update(['session_id' => $session->id]);

            // se vuoi analisi anomalie:
            // $engine->evaluateSession($session);
        }

        $this->command?->info('✅ WorkSessionsFromPunchSeeder completato (sessioni ricostruite).');
    }
}

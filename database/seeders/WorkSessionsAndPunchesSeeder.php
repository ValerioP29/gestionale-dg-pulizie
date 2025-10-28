<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\DgSite;
use App\Models\DgWorkSession;
use App\Models\DgPunch;
use App\Services\Anomalies\AnomalyEngine;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class WorkSessionsAndPunchesSeeder extends Seeder
{
    public function run(): void
    {
        $engine = new AnomalyEngine();

        $employees = User::where('role','employee')->get(['id','main_site_id']);
        if ($employees->isEmpty()) return;

        $sites = DgSite::pluck('id')->all();
        if (empty($sites)) return;

        // Ultime 4 settimane
        $period = CarbonPeriod::create(
            Carbon::now()->startOfMonth()->subWeeks(4),
            Carbon::now()->endOfDay()
        );

        foreach ($employees as $emp) {
            foreach ($period as $day) {

                // 70% delle giornate lavorative reali
                if (rand(1,100) <= 30) continue;

                $siteId = $emp->main_site_id ?? $sites[array_rand($sites)];

                // orari teorici
                $in  = Carbon::parse($day->toDateString().' 08:00:00');
                $out = Carbon::parse($day->toDateString().' 16:00:00');

                // variabilità
                $roll = rand(1,100);

                if ($roll <= 10) {
                    // Assenza totale
                    $checkIn = null;
                    $checkOut = null;
                    $worked = 0;

                } elseif ($roll <= 30) {
                    // Solo check-in
                    $checkIn = $in->copy()->addMinutes(rand(1,25));
                    $checkOut = null;
                    $worked = rand(90,300);

                } elseif ($roll <= 50) {
                    // Solo check-out
                    $checkIn = null;
                    $checkOut = $out->copy()->subMinutes(rand(1,25));
                    $worked = rand(90,300);

                } elseif ($roll <= 70) {
                    // Late + early
                    $checkIn  = $in->copy()->addMinutes(rand(5,45));
                    $checkOut = $out->copy()->subMinutes(rand(5,45));
                    $worked = max(0, $checkOut->diffInMinutes($checkIn) - 30);

                } elseif ($roll <= 90) {
                    // Normale
                    $checkIn  = $in->copy()->addMinutes(rand(0,10));
                    $checkOut = $out->copy()->subMinutes(rand(0,10));
                    $worked = max(0, $checkOut->diffInMinutes($checkIn) - 30);

                } else {
                    // Overtime
                    $checkIn  = $in->copy()->addMinutes(rand(0,5));
                    $checkOut = $out->copy()->addMinutes(rand(30,120));
                    $worked = max(0, $checkOut->diffInMinutes($checkIn) - 30);
                }

                // Salva la sessione
                $session = DgWorkSession::create([
                    'user_id'        => $emp->id,
                    'site_id'        => $siteId,
                    'session_date'   => $day->toDateString(),
                    'check_in'       => $checkIn,
                    'check_out'      => $checkOut,
                    'worked_minutes' => $worked,
                    'status'         => $worked > 0 ? 'complete' : 'invalid',
                    'source'         => 'auto',
                ]);

                // Timbrature reali con uuid + device + rete
                if ($checkIn) {
                    DgPunch::create([
                        'uuid'           => Str::uuid(),
                        'user_id'        => $emp->id,
                        'site_id'        => $siteId,
                        'session_id'     => $session->id,
                        'type'           => 'check_in',       // CORRETTO per la tua app
                        'created_at'       => $checkIn,
                        'latitude'       => 41.120000 + (rand(-5,5)/1000),
                        'longitude'      => 16.870000 + (rand(-5,5)/1000),
                        'accuracy_m'     => rand(2,15),
                        'device_id'      => 'DEVICE_ANDROID_'.rand(100,999),
                        'device_battery' => rand(10,100),
                        'network_type'   => rand(0,1) ? 'WiFi' : '4G',
                        'source'         => 'seed',
                        'payload'        => ['seed' => true],
                    ]);
                }

                if ($checkOut) {
                    DgPunch::create([
                        'uuid'           => Str::uuid(),
                        'user_id'        => $emp->id,
                        'site_id'        => $siteId,
                        'session_id'     => $session->id,
                        'type'           => 'check_out',
                        'created_at'       => $checkOut,
                        'latitude'       => 41.120000 + (rand(-5,5)/1000),
                        'longitude'      => 16.870000 + (rand(-5,5)/1000),
                        'accuracy_m'     => rand(2,15),
                        'device_id'      => 'DEVICE_ANDROID_'.rand(100,999),
                        'device_battery' => rand(10,100),
                        'network_type'   => rand(0,1) ? 'WiFi' : '4G',
                        'source'         => 'seed',
                        'payload'        => ['seed' => true],
                    ]);
                }

                // Genera anomalie & overtime
                $engine->evaluateSession($session);
            }
        }
    }
}

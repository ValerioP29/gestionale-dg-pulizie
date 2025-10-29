<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\DgSite;
use App\Models\DgWorkSession;
use App\Models\DgPunch;
use App\Services\Anomalies\AnomalyEngine;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Str;

class WorkSessionsAndPunchesSeeder extends Seeder
{
    public function run(): void
    {
        $engine = new AnomalyEngine();

        $employees = User::where('role', 'employee')
            ->whereNotNull('contract_schedule_id')
            ->get(['id', 'main_site_id', 'contract_schedule_id']);

        if ($employees->isEmpty()) {
            return;
        }

        $sites = DgSite::pluck('id')->all();
        if (empty($sites)) {
            return;
        }

        // due mesi di dati reali
        $period = CarbonPeriod::create(
            now()->startOfMonth()->subMonths(2),
            now()->endOfMonth()
        );

        foreach ($employees as $emp) {
            $contract = $emp->contractSchedule;

            foreach ($period as $day) {

                $weekday = strtolower($day->format('D')); // mon, tue, wed...

                // ore previste dal contratto
                $expectedHours = $contract->{$weekday} ?? 0;
                if ($expectedHours <= 0) {
                    continue; // giorno non lavorativo
                }

                // 5% probabilità di assenza totale
                if (rand(1,100) <= 5) {
                    continue;
                }

                $siteId = $emp->main_site_id ?? $sites[array_rand($sites)];

                $start = $day->copy()->setTime(8, 0);
                $end   = $start->copy()->addHours($expectedHours);

                // variazione realistica
                $late = rand(0, 15); // fino 15 minuti di ritardo
                $earlyLeave = rand(0, 10); // fino 10 minuti di uscita anticipata
                $overtime = rand(0, 100) > 85 ? rand(15, 90) : 0; // 15-90 min straord., non sempre

                $checkIn  = $start->copy()->addMinutes($late);
                $checkOut = $end->copy()->subMinutes($earlyLeave)->addMinutes($overtime);

                $worked = max(0, $checkOut->diffInMinutes($checkIn));

                // salva sessione
                $session = DgWorkSession::create([
                    'user_id'        => $emp->id,
                    'site_id'        => $siteId,
                    'session_date'   => $day->toDateString(),
                    'check_in'       => $checkIn,
                    'check_out'      => $checkOut,
                    'worked_minutes' => $worked,
                    'overtime_minutes' => $overtime,
                    'status'         => $worked > 0 ? 'complete' : 'invalid',
                    'source'         => 'auto',
                ]);

                // timbrature realistiche
                DgPunch::create([
                    'uuid'           => Str::uuid(),
                    'user_id'        => $emp->id,
                    'site_id'        => $siteId,
                    'session_id'     => $session->id,
                    'type'           => 'check_in',
                    'created_at'     => $checkIn,
                    'latitude'       => 41.120000 + (rand(-5,5)/1000),
                    'longitude'      => 16.870000 + (rand(-5,5)/1000),
                    'accuracy_m'     => rand(2, 10),
                    'device_id'      => 'ANDROID_' . rand(100,999),
                    'device_battery' => rand(20,100),
                    'network_type'   => rand(0,1) ? 'WiFi' : '4G',
                    'source'         => 'seed',
                    'payload'        => ['seed' => true],
                ]);

                DgPunch::create([
                    'uuid'           => Str::uuid(),
                    'user_id'        => $emp->id,
                    'site_id'        => $siteId,
                    'session_id'     => $session->id,
                    'type'           => 'check_out',
                    'created_at'     => $checkOut,
                    'latitude'       => 41.120000 + (rand(-5,5)/1000),
                    'longitude'      => 16.870000 + (rand(-5,5)/1000),
                    'accuracy_m'     => rand(2, 10),
                    'device_id'      => 'ANDROID_' . rand(100,999),
                    'device_battery' => rand(20,100),
                    'network_type'   => rand(0,1) ? 'WiFi' : '4G',
                    'source'         => 'seed',
                    'payload'        => ['seed' => true],
                ]);

                // calcolo anomalie
                $engine->evaluateSession($session);
            }
        }
    }
}

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

        // Prendi solo dipendenti con contratto
        $employees = User::query()
            ->where('role', 'employee')
            ->whereNotNull('contract_schedule_id')
            ->get();

        if ($employees->isEmpty()) {
            $this->command?->error('⚠ Nessun dipendente con contratto trovato.');
            return;
        }

        $sites = DgSite::all();
        if ($sites->isEmpty()) {
            $this->command?->error('⚠ Nessun sito presente.');
            return;
        }

        // Periodo materiale, NON iteratore consumabile
        $start = now()->startOfMonth()->subMonths(2)->startOfDay();
        $end   = now()->endOfMonth()->endOfDay();

        $days = collect(CarbonPeriod::create($start, '1 day', $end))
            ->map(fn($d) => Carbon::parse($d->toDateString()))
            ->values();

        // Mappa giorni inglesi -> colonne DB
        $map = [
            'monday'    => 'mon',
            'tuesday'   => 'tue',
            'wednesday' => 'wed',
            'thursday'  => 'thu',
            'friday'    => 'fri',
            'saturday'  => 'sat',
            'sunday'    => 'sun',
        ];

        foreach ($employees as $emp) {
            $contract = $emp->contractSchedule;
            if (!$contract) {
                continue;
            }

            foreach ($days as $day) {

                // giorno della settimana
                $weekday = strtolower($day->englishDayOfWeek);
                $field   = $map[$weekday] ?? null;

                if (!$field) {
                    continue;
                }

                $expectedHours = (float) ($contract->{$field} ?? 0);

                // Zero ore -> giornata non minima
                if ($expectedHours <= 0) {
                    continue;
                }

                // ~8% assenti
                if (rand(1,100) <= 8) {
                    continue;
                }

                // sito
                $site = $emp->mainSite ?? $sites->random();

                // orario atteso
                $startTime = $day->copy()->setTime(8,0);
                $endTime   = $startTime->copy()->addHours($expectedHours);

                $late        = rand(0, 30);
                $earlyLeave  = rand(0, 20);
                $overtime    = rand(0, 100) > 90 ? rand(15, 120) : 0;

                $checkIn  = $startTime->copy()->addMinutes($late);
                $checkOut = $endTime->copy()->subMinutes($earlyLeave)->addMinutes($overtime);

                $missingCheckout = rand(1,10) === 1;

                $worked = $missingCheckout
                    ? $checkIn->diffInMinutes($endTime)
                    : $checkOut->diffInMinutes($checkIn);

                $session = DgWorkSession::create([
                    'user_id'         => $emp->id,
                    'site_id'         => $site->id,
                    'session_date'    => $day->toDateString(),
                    'check_in'        => $checkIn,
                    'check_out'       => $missingCheckout ? null : $checkOut,
                    'worked_minutes'  => max(0, $worked),
                    'overtime_minutes'=> $overtime,
                    'status'          => $worked > 0 ? 'complete' : 'invalid',
                    'source'          => 'seed',
                ]);

                // PUNCH IN
                self::punch($emp, $site, $session, 'check_in', $checkIn);

                // Doppio check-in
                if (rand(1,100) <= 30) {
                    self::punch($emp, $site, $session, 'check_in', $checkIn->copy()->addMinutes(rand(1,10)));
                }

                // PUNCH OUT
                if (!$missingCheckout) {
                    self::punch($emp, $site, $session, 'check_out', $checkOut);

                    // Doppio check-out
                    if (rand(1,100) <= 20) {
                        self::punch($emp, $site, $session, 'check_out', $checkOut->copy()->addMinutes(rand(1,5)));
                    }
                }

                // ~15% fuori area
                if (rand(1,100) <= 15) {
                    self::punch(
                        $emp,
                        $site,
                        $session,
                        rand(0,1) ? 'check_in' : 'check_out',
                        $checkIn->copy()->addMinutes(rand(2,60)),
                        true
                    );
                }

                // Se vuoi, riattiva dopo test
                // $engine->evaluateSession($session);
            }
        }

        $this->command?->info('✅ WorkSessionsAndPunchesSeeder completato con successo.');
    }

    private static function punch($emp, $site, $session, $type, $timestamp, $outOfBounds = false)
    {
        $lat = 41.120000 + (rand(-20,20)/1000);
        $lng = 16.870000 + (rand(-20,20)/1000);

        if ($outOfBounds) {
            $lat += rand(5,20)/1000;
            $lng += rand(5,20)/1000;
        }

        DgPunch::create([
            'uuid'           => Str::uuid(),
            'user_id'        => $emp->id,
            'site_id'        => $site->id,
            'session_id'     => $session->id,
            'type'           => $type,
            'created_at'     => $timestamp,
            'latitude'       => $lat,
            'longitude'      => $lng,
            'accuracy_m'     => rand(2,35),
            'device_id'      => 'DEV_' . rand(100,999),
            'device_battery' => rand(1,100),
            'network_type'   => rand(0,1) ? 'WiFi' : '4G',
            'source'         => 'seed',
            'payload'        => [
                'manual_edit' => false,
                'seed' => true,
            ]
        ]);
    }
}

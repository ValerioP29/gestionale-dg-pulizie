<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\DgSite;
use App\Models\DgPunch;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Str;

class PunchSeeder extends Seeder
{
    public function run(): void
    {
        $employees = User::query()
            ->where('role', 'employee')
            ->whereNotNull('contract_schedule_id')
            ->get();

        if ($employees->isEmpty()) {
            $this->command?->error('⚠ Nessun dipendente trovato.');
            return;
        }

        $sites = DgSite::all();
        if ($sites->isEmpty()) {
            $this->command?->error('⚠ Nessun sito presente.');
            return;
        }

        // Periodo rastrellato
        $start = now()->startOfMonth()->subMonths(2)->startOfDay();
        $end   = now()->endOfMonth()->endOfDay();

        $days = collect(CarbonPeriod::create($start, '1 day', $end))
            ->map(fn($d) => Carbon::parse($d->toDateString()))
            ->values();

        foreach ($employees as $emp) {

            foreach ($days as $day) {

                // 10% assente totale → NO timbrature
                if (rand(1,100) <= 10) {
                    continue;
                }

                $site = $emp->mainSite ?? $sites->random();
                $baseIn  = $day->copy()->setTime(8,0);
                $baseOut = $day->copy()->setTime(16,0);

                $late       = rand(0, 30);
                $earlyLeave = rand(0, 20);
                $overtime   = rand(0,100) > 90 ? rand(15,120) : 0;

                $checkIn  = $baseIn->copy()->addMinutes($late);
                $checkOut = $baseOut->copy()->subMinutes($earlyLeave)->addMinutes($overtime);

                // 10% senza check-out
                $missingCheckout = rand(1,10) === 1;

                // Punch IN
                self::makePunch($emp, $site, 'check_in', $checkIn);

                // 20% doppio punch IN
                if (rand(1,100) <= 20) {
                    self::makePunch($emp, $site, 'check_in', $checkIn->copy()->addMinutes(rand(1,10)));
                }

                // Punch OUT se non mancante
                if (!$missingCheckout) {
                    self::makePunch($emp, $site, 'check_out', $checkOut);

                    // 20% doppio OUT
                    if (rand(1,100) <= 20) {
                        self::makePunch($emp, $site, 'check_out', $checkOut->copy()->addMinutes(rand(1,5)));
                    }
                }

                // 15% punch fuori area
                if (rand(1,100) <= 15) {
                    $randomTs = $checkIn->copy()->addMinutes(rand(10,60));
                    self::makePunch($emp, $site, rand(0,1) ? 'check_in' : 'check_out', $randomTs, true);
                }
            }
        }

        $this->command?->info('✅ PunchSeeder completato (solo timbrature).');
    }

    private static function makePunch($emp, $site, $type, $timestamp, $outOfBounds = false)
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

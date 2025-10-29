<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DgContractSchedule;

class DgContractScheduleSeeder extends Seeder
{
    public function run(): void
    {
        // Cancella solo se in fresh. Se vuoi sicuro, usa truncate QUI ma non in produzione.
        DgContractSchedule::query()->delete();

        $patterns = [
            ['name' => 'Full-time 120h', 'mon'=>5, 'tue'=>5, 'wed'=>5, 'thu'=>5, 'fri'=>5, 'sat'=>5, 'sun'=>0],
            ['name' => 'Part-time 80h',   'mon'=>4, 'tue'=>4, 'wed'=>4, 'thu'=>4, 'fri'=>4, 'sat'=>0, 'sun'=>0],
            ['name' => 'Part-time 60h',   'mon'=>2.5, 'tue'=>2.5, 'wed'=>2.5, 'thu'=>2.5, 'fri'=>2.5, 'sat'=>2.5, 'sun'=>0],
            ['name' => 'Mini-20h',        'mon'=>1, 'tue'=>1, 'wed'=>1, 'thu'=>1, 'fri'=>1, 'sat'=>0, 'sun'=>0],
            ['name' => 'Turno 60h',       'mon'=>3, 'tue'=>3, 'wed'=>3, 'thu'=>3, 'fri'=>3, 'sat'=>0, 'sun'=>0],
            ['name' => 'Turno 48h',       'mon'=>2, 'tue'=>2, 'wed'=>2, 'thu'=>2, 'fri'=>2, 'sat'=>2, 'sun'=>0],
            ['name' => 'Full-time 96h',   'mon'=>4, 'tue'=>4, 'wed'=>4, 'thu'=>4, 'fri'=>4, 'sat'=>4, 'sun'=>0],
        ];

        foreach ($patterns as $p) {
            $total = ($p['mon'] + $p['tue'] + $p['wed'] + $p['thu'] + $p['fri'] + $p['sat'] + $p['sun']) * 4; // ca. 4 settimane

            DgContractSchedule::updateOrCreate(
                ['name' => $p['name']],
                [
                    'mon' => $p['mon'],
                    'tue' => $p['tue'],
                    'wed' => $p['wed'],
                    'thu' => $p['thu'],
                    'fri' => $p['fri'],
                    'sat' => $p['sat'],
                    'sun' => $p['sun'],
                    'contract_hours_monthly' => $total,
                ]
            );
        }
    }
}

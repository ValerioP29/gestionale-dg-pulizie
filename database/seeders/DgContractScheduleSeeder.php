<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DgContractSchedule;

class DgContractScheduleSeeder extends Seeder
{
    public function run(): void
    {
        // Full-time standard 8-16 con 30' pausa
        DgContractSchedule::updateOrCreate(
            ['name' => 'Full-time standard'],
            [
                'rules' => [
                    "mon" => ["start" => "08:00", "end" => "16:00", "break" => 30],
                    "tue" => ["start" => "08:00", "end" => "16:00", "break" => 30],
                    "wed" => ["start" => "08:00", "end" => "16:00", "break" => 30],
                    "thu" => ["start" => "08:00", "end" => "16:00", "break" => 30],
                    "fri" => ["start" => "08:00", "end" => "16:00", "break" => 30],
                    "sat" => null,
                    "sun" => null
                ],
                'active' => true,
            ]
        );

        // Part-time mattina 8-12
        DgContractSchedule::updateOrCreate(
            ['name' => 'Part-time AM'],
            [
                'rules' => [
                    "mon" => ["start" => "08:00", "end" => "12:00", "break" => 0],
                    "tue" => ["start" => "08:00", "end" => "12:00", "break" => 0],
                    "wed" => ["start" => "08:00", "end" => "12:00", "break" => 0],
                    "thu" => ["start" => "08:00", "end" => "12:00", "break" => 0],
                    "fri" => ["start" => "08:00", "end" => "12:00", "break" => 0],
                    "sat" => null,
                    "sun" => null
                ],
                'active' => true,
            ]
        );
    }
}

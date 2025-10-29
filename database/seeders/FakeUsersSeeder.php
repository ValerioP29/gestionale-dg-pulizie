<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\DgContractSchedule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class FakeUsersSeeder extends Seeder
{
    public function run(): void
    {
        // ADMIN
        User::updateOrCreate(
            ['email' => 'admin@azienda.it'],
            [
                'first_name' => 'System',
                'last_name'  => 'Admin',
                'name'       => 'System Admin',
                'password'   => Hash::make('password'),
                'role'       => 'admin',
                'active'     => true,
                'can_login'  => true,
            ]
        );

        // SUPERVISOR
        User::updateOrCreate(
            ['email' => 'supervisor@azienda.it'],
            [
                'first_name' => 'Mario',
                'last_name'  => 'Rossi',
                'name'       => 'Mario Rossi',
                'password'   => Hash::make('password'),
                'role'       => 'supervisor',
                'active'     => true,
                'can_login'  => true,
            ]
        );

        // VIEWER
        User::updateOrCreate(
            ['email' => 'viewer@azienda.it'],
            [
                'first_name' => 'Luigi',
                'last_name'  => 'Bianchi',
                'name'       => 'Luigi Bianchi',
                'password'   => Hash::make('password'),
                'role'       => 'viewer',
                'active'     => true,
                'can_login'  => true,
            ]
        );

        // CONTRATTI per dipendenti
        $contracts = DgContractSchedule::all();

        $employees = [
            ['first' => 'Maria',    'last' => 'Teresa',    'mat' => '001', 'hired' => '2018-04-11'],
            ['first' => 'Inna',     'last' => 'Apreotesei','mat' => '045', 'hired' => '2019-09-12'],
            ['first' => 'Silvia',   'last' => 'Centra',    'mat' => '058', 'hired' => '2000-05-13'],
            ['first' => 'Tiziana',  'last' => 'Gambalonga','mat' => '110', 'hired' => '2021-09-23'],
            ['first' => 'Isabella', 'last' => 'Falconi',   'mat' => '115', 'hired' => '2021-11-02'],
            ['first' => 'Marcella', 'last' => 'Coppola',   'mat' => '116', 'hired' => '2021-11-02'],
            ['first' => 'Annarita', 'last' => 'Di Tucci',  'mat' => '118', 'hired' => '2021-11-26'],
            ['first' => 'Manuel',   'last' => 'Carocci',   'mat' => '131', 'hired' => '2022-03-25'],
        ];

        foreach ($employees as $emp) {

            $contract = $contracts->random();

            User::updateOrCreate(
                ['email' => strtolower($emp['first']).'.'.strtolower(Str::slug($emp['last'])).'@azienda.it'],
                [
                    'first_name' => $emp['first'],
                    'last_name'  => $emp['last'],
                    'name'       => "{$emp['first']} {$emp['last']}",
                    'password'   => Hash::make('password'),
                    'role'       => 'employee',
                    'payroll_code' => $emp['mat'],
                    'hired_at'     => Carbon::parse($emp['hired']),
                    'contract_end_at' => null,
                    'contract_schedule_id' => $contract->id,
                    'active'     => true,
                    'can_login'  => true,
                ]
            );
        }
    }
}

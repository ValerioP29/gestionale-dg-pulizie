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

        // Ottieni contratti disponibili
        $contracts = DgContractSchedule::all();
        if ($contracts->isEmpty()) {
            $this->command?->error('⚠ Nessun contratto trovato in dg_contract_schedules, impossibile generare dipendenti.');
            return;
        }

        $faker = \Faker\Factory::create('it_IT');

        // 20 dipendenti finti
        for ($i = 1; $i <= 20; $i++) {
            $first  = $faker->firstName;
            $last   = $faker->lastName;
            $email  = strtolower(Str::slug($first.'.'.$last)).'@azienda.it';

            $hired = $faker->dateTimeBetween('-5 years', 'now');
            $maybeEnding = rand(1, 10) === 1 // 10% hanno contratto terminato
                ? $faker->dateTimeBetween($hired, 'now')
                : null;

            User::updateOrCreate(
                ['email' => $email],
                [
                    'first_name' => $first,
                    'last_name'  => $last,
                    'name'       => "$first $last",
                    'password'   => 'password', // verrà hashata automaticamente dal mutator
                    'role'       => 'employee',
                    'phone'      => $faker->phoneNumber,
                    'payroll_code' => str_pad((string)$i, 3, '0', STR_PAD_LEFT),
                    'hired_at'     => Carbon::parse($hired),
                    'contract_end_at' => $maybeEnding,
                    'contract_schedule_id' => $contracts->random()->id,
                    'active' => $maybeEnding === null,
                    'can_login' => $faker->boolean(90), // 90% possono loggare
                    'last_login_at' => $faker->dateTimeBetween('-1 year', 'now'),
                ]
            );
        }

        $this->command?->info('✅ FakeUsersSeeder completato: admin + supervisor + viewer + 20 employees.');
    }
}

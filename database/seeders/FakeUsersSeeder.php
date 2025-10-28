<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\DgContractSchedule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Arr;

class FakeUsersSeeder extends Seeder
{
    public function run(): void
    {
        $fulltime = DgContractSchedule::where('name','Full-time standard')->first();
        $parttime = DgContractSchedule::where('name','Part-time AM')->first();

        // Admin
        User::updateOrCreate(
            ['email' => 'admin@dg.test'],
            [
                'first_name' => 'Admin',
                'last_name'  => 'DG',
                'password'   => Hash::make('password'),
                'role'       => 'admin',
                'can_login'  => true,
                'active'     => true,
                'contract_schedule_id' => $fulltime?->id,
            ]
        );

        // Supervisori
        for ($i=1; $i<=3; $i++) {
            User::updateOrCreate(
                ['email' => "supervisor{$i}@dg.test"],
                [
                    'first_name' => 'Supervisor',
                    'last_name'  => (string)$i,
                    'password'   => Hash::make('password'),
                    'role'       => 'supervisor',
                    'can_login'  => true,
                    'active'     => true,
                    'contract_schedule_id' => $fulltime?->id,
                ]
            );
        }

        // Dipendenti
        for ($i=1; $i<=25; $i++) {
            $schedule = Arr::random([$fulltime?->id, $parttime?->id]);
            User::updateOrCreate(
                ['email' => "employee{$i}@dg.test"],
                [
                    'first_name' => 'Employee',
                    'last_name'  => (string)$i,
                    'password'   => Hash::make('password'),
                    'role'       => 'employee',
                    'can_login'  => true,
                    'active'     => true,
                    'contract_schedule_id' => $schedule,
                ]
            );
        }
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class FakeUsersSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Crea i ruoli se non esistono
        foreach (['Admin', 'HR', 'Capocantiere', 'Dipendente'] as $roleName) {
            Role::firstOrCreate(['name' => $roleName]);
        }

        // 2. Admin principale (te)
        User::updateOrCreate(
            ['email' => 'admin@dg.local'],
            [
                'first_name' => 'Admin',
                'last_name'  => 'Principale',
                'name'       => 'Admin Principale',
                'phone'      => '0000000000',
                'active'     => true,
                'password'   => Hash::make('Fricchio29!'),
            ]
        )->assignRole('Admin');

        // 3. HR
        User::factory()->count(3)->create()->each(function ($user) {
            $user->assignRole('HR');
        });

        // 4. Capocantieri
        User::factory()->count(10)->create()->each(function ($user) {
            $user->assignRole('Capocantiere');
        });

        // 5. Dipendenti
        User::factory()->count(50)->create()->each(function ($user) {
            $user->assignRole('Dipendente');
        });
    }
}

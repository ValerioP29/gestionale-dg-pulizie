<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class FakeUsersSeeder extends Seeder
{
    public function run(): void
    {
        // 1️⃣ Admin principale
        User::updateOrCreate(
            ['email' => 'admin@dg.local'],
            [
                'first_name' => 'Valerio',
                'last_name'  => 'Persiani',
                'name'       => 'Admin Principale',
                'phone'      => '0000000000',
                'active'     => true,
                'role'       => 'admin',
                'can_login'  => true,
                'password'   => Hash::make('1234'),
            ]
        );

        // 2️⃣ Supervisori (gestionali)
        User::factory()->count(3)->create([
            'role' => 'supervisor',
            'can_login' => true,
            'active' => true,
            'password' => Hash::make('1234'),
        ]);

        // 3️⃣ Viewer (es. capocantieri gestionali)
        User::factory()->count(10)->create([
            'role' => 'viewer',
            'can_login' => true,
            'active' => true,
            'password' => Hash::make('1234'),
        ]);

        // 4️⃣ Dipendenti (solo app mobile, niente accesso gestionale)
        User::factory()->count(50)->create([
            'role' => 'employee',
            'can_login' => false,
            'active' => true,
            'password' => Hash::make('1234'),
        ]);
    }
}

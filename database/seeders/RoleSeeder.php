<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // ruoli base
        foreach (['Admin', 'HR', 'Capocantiere', 'Dipendente'] as $name) {
            Role::firstOrCreate(['name' => $name]);
        }

        // admin demo
        $admin = User::firstOrCreate(
            ['email' => 'admin@dg.local'],
            [
                'first_name' => 'Admin',
                'last_name'  => 'Demo',
                'password'   => Hash::make('password'), // cambia in produzione
                'phone'      => '0000000000',
                'active'     => true,
            ]
        );

        $admin->assignRole('Admin');
    }
}

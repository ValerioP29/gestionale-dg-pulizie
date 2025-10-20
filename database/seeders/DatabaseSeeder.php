<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Richiama i seeder reali del progetto
        $this->call([
            FakeUsersSeeder::class,
            SitesSeeder::class,
            PunchesSeeder::class,
            PayslipsSeeder::class,
            UserConsentsSeeder::class,
            SyncQueueSeeder::class,
        ]);
    }
}

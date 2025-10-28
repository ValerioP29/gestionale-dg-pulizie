<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1) tasselli base
        $this->call(DgContractScheduleSeeder::class);
        $this->call(ClientGroupsSeeder::class);
        $this->call(ClientsSeeder::class);
        $this->call(JobTitlesSeeder::class);

        // 2) utenti e cantieri
        $this->call(FakeUsersSeeder::class);
        $this->call(SitesSeeder::class);
        $this->call(SiteAssignmentsSeeder::class);

        // 3) dati operativi
        // Se hai già i tuoi, puoi mantenere i tuoi Devices/UserConsents/SyncQueue/Payslips
        if (class_exists(\Database\Seeders\DevicesSeeder::class)) {
            $this->call(\Database\Seeders\DevicesSeeder::class);
        }
        if (class_exists(\Database\Seeders\UserConsentsSeeder::class)) {
            $this->call(\Database\Seeders\UserConsentsSeeder::class);
        }
        if (class_exists(\Database\Seeders\SyncQueueSeeder::class)) {
            $this->call(\Database\Seeders\SyncQueueSeeder::class);
        }

        // 4) sessioni, timbrature e anomalie generate con l'engine
        $this->call(WorkSessionsAndPunchesSeeder::class);

        // 5) buste paga
        if (class_exists(\Database\Seeders\PayslipsSeeder::class)) {
            $this->call(\Database\Seeders\PayslipsSeeder::class);
        } else {
            $this->call(ReportsCacheSeeder::class); // se non hai payslips, generiamo comunque i report
        }

        // 6) report cache (sempre alla fine, su periodo corrente)
        $this->call(ReportsCacheSeeder::class);
    }
}

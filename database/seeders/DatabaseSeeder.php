<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1) tasselli base
        $this->call([
            DgContractScheduleSeeder::class,
            ClientGroupsSeeder::class,
            ClientsSeeder::class,
            JobTitlesSeeder::class,
        ]);

        // 2) utenti e cantieri
        $this->call([
            FakeUsersSeeder::class,
            SitesSeeder::class,
            SiteAssignmentsSeeder::class,
        ]);

        // 3) dati operativi extra (se esistono)
        foreach ([
            \Database\Seeders\DevicesSeeder::class,
            \Database\Seeders\UserConsentsSeeder::class,
            \Database\Seeders\SyncQueueSeeder::class,
        ] as $seeder) {
            if (class_exists($seeder)) {
                $this->call($seeder);
            }
        }

        // ✅ 4) Punch -> Sessioni -> Report (workflow realistico)

        // ✅ primo: genera TIMBRATURE vere
        $this->call(PunchSeeder::class);

        // ✅ secondo: ricostruisce sessioni in base ai punch reali
        $this->call(WorkSessionsFromPunchSeeder::class);

        // ✅ terzo: genera report
        $this->call(ReportsCacheSeeder::class);

        
        /*
        ------------------------------------------------------------------
        ✅ VECCHIO SISTEMA (creava prima sessioni e poi punch)
           LO LASCIO COMMENTATO PER RIFERIMENTO / BACKUP
        ------------------------------------------------------------------

        // $this->call(WorkSessionsAndPunchesSeeder::class);

        ------------------------------------------------------------------
        */
        

        // 6) buste paga (se esiste il seeder)
        if (class_exists(\Database\Seeders\PayslipsSeeder::class)) {
            $this->call(PayslipsSeeder::class);
        }
    }
}

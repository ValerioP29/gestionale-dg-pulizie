<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\DgSite;
use App\Models\DgSiteAssignment;
use Carbon\Carbon;

class SiteAssignmentsSeeder extends Seeder
{
    public function run(): void
    {
        $employees = User::where('role', 'employee')->get();
        $sites     = DgSite::all();
        $admins    = User::whereIn('role', ['admin', 'supervisor'])->pluck('id')->all();

        if ($employees->isEmpty() || $sites->isEmpty()) {
            $this->command?->error('⚠ Impossibile generare assegnazioni: servono utenti employee e cantieri.');
            return;
        }

        $faker = \Faker\Factory::create('it_IT');
        $startBase = Carbon::now()->startOfMonth()->subMonths(2);

        foreach ($employees as $employee) {

            // quanti cantieri? 1-3
            $countSites = rand(1, min(3, $sites->count()));
            $selectedSites = $sites->random($countSites);

            $isFirst = true;

            foreach ($selectedSites as $site) {

                // date random
                $assignFrom = $startBase->copy()->addDays(rand(0, 25));

                // 30% hanno assegnazione chiusa
                $assignTo = rand(1, 10) <= 3
                    ? $assignFrom->copy()->addDays(rand(5, 40))
                    : null;

                // 50% hanno una nota
                $notes = rand(1, 10) <= 5
                    ? $faker->randomElement([null, 'temporaneo', 'sostituzione', 'urgenza', 'notturno'])
                    : null;

                // chi li assegna? admin o supervisor
                $assignedBy = $faker->randomElement($admins);

                DgSiteAssignment::updateOrCreate(
                    [
                        'user_id' => $employee->id,
                        'site_id' => $site->id,
                        'assigned_from' => $assignFrom->toDateString(),
                    ],
                    [
                        'assigned_to' => $assignTo,
                        'assigned_by' => $assignedBy,
                        'notes' => $notes,
                    ]
                );

                // Primo cantiere è quello principale
                if ($isFirst && $assignTo === null) {
                    $employee->update(['main_site_id' => $site->id]);
                    $isFirst = false;
                }
            }
        }

        $this->command?->info('✅ SiteAssignmentsSeeder completato: assegnazioni generate con varietà realistica.');
    }
}

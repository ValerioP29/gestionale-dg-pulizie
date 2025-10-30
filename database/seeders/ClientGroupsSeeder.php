<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DgClientGroup;
use Illuminate\Support\Str;

class ClientGroupsSeeder extends Seeder
{
    public function run(): void
    {
        $faker = \Faker\Factory::create('it_IT');

        // Gruppi principali da te indicati
        $baseGroups = [
            'Gruppo Sanitario',
            'Gruppo Scuole',
            'Gruppo Uffici',
        ];

        foreach ($baseGroups as $g) {
            DgClientGroup::updateOrCreate(
                ['name' => $g],
                [
                    'notes' => "Clienti appartenenti a: $g",
                    'active' => true,
                ]
            );
        }

        // Aggiungo gruppi addizionali
        $extra = [
            'Condomini',
            'Musei & Cultura',
            'Industriali',
            'Privati',
            'Strutture Sportive',
        ];

        foreach ($extra as $g) {
            $group = DgClientGroup::updateOrCreate(
                ['name' => $g],
                [
                    'notes' => "Gruppo creato automaticamente ($g)",
                    'active' => rand(1,100) > 15, // 15% inattivi
                ]
            );

            // 5% soft deleted
            if (rand(1,100) <= 5) {
                $group->delete();
            }
        }

        $this->command?->info('✅ ClientGroupsSeeder PRO completato: gruppi, stati, note, soft-deletes.');
    }
}

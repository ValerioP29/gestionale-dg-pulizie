<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DgJobTitle;

class JobTitlesSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['code' => 'ADDETTO',      'name' => 'Addetto pulizie',        'notes' => null],
            ['code' => 'CAPOSQUADRA',  'name' => 'Caposquadra',            'notes' => 'Gestione team e turni'],
            ['code' => 'SUPERVISOR',   'name' => 'Supervisore',            'notes' => 'Supervisione lavori e report'],
            ['code' => 'RECEPTION',    'name' => 'Receptionist',           'notes' => null],
            ['code' => 'FACCHINO',     'name' => 'Facchino',               'notes' => null],
            ['code' => 'MANUTENZ',     'name' => 'Manutentore',            'notes' => 'Supporto tecnico base'],
            ['code' => 'MAGAZZINO',    'name' => 'Magazziniere',           'notes' => null],
            ['code' => 'LAVA_PAV',     'name' => 'Lavapavimenti',          'notes' => null],
            ['code' => 'COORDINATORE', 'name' => 'Coordinatore Area',      'notes' => 'Responsabile più cantieri'],
            ['code' => 'FORMATO',      'name' => 'Personale Formato HSE',  'notes' => 'Sicurezza & DPI'],
        ];

        foreach ($items as $job) {
            DgJobTitle::updateOrCreate(
                ['code' => $job['code']],
                [
                    'name'   => $job['name'],
                    'notes'  => $job['notes'],
                    'active' => rand(1,100) > 10, // 10% inattivi
                ]
            );
        }

        // 5% soft deleted
        foreach (DgJobTitle::inRandomOrder()->limit(1)->get() as $j) {
            $j->delete();
        }

        $this->command?->info('✅ JobTitlesSeeder PRO completato: 10 ruoli, note, attivi/inattivi, 1 cancellato.');
    }
}

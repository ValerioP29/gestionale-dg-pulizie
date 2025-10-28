<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DgJobTitle;

class JobTitlesSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['code' => 'ADDETTO',      'name' => 'Addetto pulizie'],
            ['code' => 'CAPOSQUADRA',  'name' => 'Caposquadra'],
            ['code' => 'SUPERVISOR',   'name' => 'Supervisore'],
        ];

        foreach ($items as $job) {
            DgJobTitle::updateOrCreate(
                ['code' => $job['code']],
                [
                    'name' => $job['name'],
                    'active' => true,
                ]
            );
        }
    }
}

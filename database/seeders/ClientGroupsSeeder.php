<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DgClientGroup;

class ClientGroupsSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            ['name' => 'Gruppo Sanitario'],
            ['name' => 'Gruppo Scuole'],
            ['name' => 'Gruppo Uffici'],
        ];

        foreach ($groups as $g) {
            DgClientGroup::firstOrCreate(['name' => $g['name']]);
        }
    }
}

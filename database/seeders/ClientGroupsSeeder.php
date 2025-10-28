<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ClientGroupsSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $groups = ['Grande Distribuzione','Sanità','Uffici','Industria','Condomini'];

        foreach ($groups as $g) {
            DB::table('dg_client_groups')->updateOrInsert(
                ['name' => $g],
                ['notes' => null, 'active' => true, 'created_at' => $now, 'updated_at' => $now]
            );
        }
    }
}

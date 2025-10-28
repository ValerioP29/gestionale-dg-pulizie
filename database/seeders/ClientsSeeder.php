<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ClientsSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $groupIds = DB::table('dg_client_groups')->pluck('id')->all();

        $names = [
            'Ospedale San Marco',
            'Centro Commerciale Le Palme',
            'Uffici Fininvest',
            'Condominio Aurora',
            'Stabilimento MetalTech',
        ];

        foreach ($names as $i => $name) {
            DB::table('dg_clients')->updateOrInsert(
                ['name' => $name],
                [
                    'group_id' => $groupIds[$i % max(count($groupIds),1)] ?? null,
                    'vat' => 'IT'.str_pad((string)rand(1000000,9999999), 7, '0', STR_PAD_LEFT),
                    'address' => 'Via Roma '.rand(1,120).', Città',
                    'email' => 'contatti+'.($i+1).'@clienti.test',
                    'phone' => '+39 080 '.rand(1000000,9999999),
                    'active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}

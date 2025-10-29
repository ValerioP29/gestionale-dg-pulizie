<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DgClient;
use App\Models\DgClientGroup;

class ClientsSeeder extends Seeder
{
    public function run(): void
    {
        $clients = [
            [
                'name' => 'Ospedale San Marco',
                'payroll_client_code' => 'PRIT0000OSM',
                'payroll_group_code'  => 'PRIT0000SGGE',
                'group' => 'Gruppo Sanitario'
            ],
            [
                'name' => 'Scuola Rinascita',
                'payroll_client_code' => 'PRF20500SCU',
                'payroll_group_code'  => 'PR000000ET00',
                'group' => 'Gruppo Scuole'
            ],
            [
                'name' => 'Uffici Fijnive',
                'payroll_client_code' => 'PRE47200UFF',
                'payroll_group_code'  => 'PR000000MI00',
                'group' => 'Gruppo Uffici'
            ],
            [
                'name' => 'Condominio Aurora',
                'payroll_client_code' => 'PRE37500CON',
                'payroll_group_code'  => 'PR000000BP00',
                'group' => 'Gruppo Uffici'
            ],
            [
                'name' => 'Museo Cittadino',
                'payroll_client_code' => 'PRD70800MUS',
                'payroll_group_code'  => 'PRD70800CASO',
                'group' => 'Gruppo Uffici'
            ],
        ];

        foreach ($clients as $c) {
            $group = DgClientGroup::where('name', $c['group'])->first();

            DgClient::updateOrCreate(
                ['name' => $c['name']],
                [
                    'group_id' => $group?->id,
                    'payroll_client_code' => $c['payroll_client_code'],
                    'payroll_group_code'  => $c['payroll_group_code'],
                    'vat' => fake()->numerify('IT###########'),
                    'email' => strtolower(str_replace(' ', '.', $c['name'])) . '@clienti.it',
                    'active' => true,
                ]
            );
        }
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DgClient;
use App\Models\DgClientGroup;
use Illuminate\Support\Str;

class ClientsSeeder extends Seeder
{
    public function run(): void
    {
        $faker = \Faker\Factory::create('it_IT');

        $groups = DgClientGroup::pluck('id')->all();
        if (empty($groups)) {
            $this->command?->error('⚠ Nessun DgClientGroup trovato. Prima lancia GroupsSeeder.');
            return;
        }

        // 5 clienti statici come da tuo esempio
        $staticClients = [
            ['Ospedale San Marco', 'PRIT0000OSM', 'PRIT0000SGGE'],
            ['Scuola Rinascita', 'PRF20500SCU', 'PR000000ET00'],
            ['Uffici Fijnive', 'PRE47200UFF', 'PR000000MI00'],
            ['Condominio Aurora', 'PRE37500CON', 'PR000000BP00'],
            ['Museo Cittadino', 'PRD70800MUS', 'PRD70800CASO'],
        ];

        foreach ($staticClients as [$name, $clientCode, $groupCode]) {

            DgClient::updateOrCreate(
                ['name' => $name],
                [
                    'group_id'             => $groups[array_rand($groups)],
                    'vat'                  => $faker->numerify('IT############'),
                    'address'              => $faker->address,
                    'email'                => Str::slug($name) . '@clienti.it',
                    'phone'                => $faker->phoneNumber,
                    'active'               => true,
                    'payroll_client_code'  => $clientCode,
                    'payroll_group_code'   => $groupCode,
                ]
            );
        }

        // 25 clienti random
        for ($i = 0; $i < 25; $i++) {

            $name = $faker->company;

            $client = DgClient::updateOrCreate(
                ['name' => $name],
                [
                    'group_id'             => $groups[array_rand($groups)],
                    'vat'                  => $faker->numerify('IT############'),
                    'address'              => $faker->address,
                    'email'                => Str::slug($name) . '@clienti.it',
                    'phone'                => $faker->phoneNumber,
                    'active'               => rand(1,100) > 10, // 10% inattivi
                    'payroll_client_code'  => 'PR'.rand(10000,99999).'C',
                    'payroll_group_code'   => 'GR'.rand(10000,99999).'G',
                ]
            );

            // 5% dei clienti eliminati
            if (rand(1,100) <= 5) {
                $client->delete();
            }
        }

        $this->command?->info('✅ ClientsSeeder PRO completato: 30 clienti, gruppi random, VAT, email, soft deletes.');
    }
}

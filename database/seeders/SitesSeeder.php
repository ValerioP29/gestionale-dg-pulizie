<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DgClient;
use App\Models\DgSite;
use Illuminate\Support\Str;

class SitesSeeder extends Seeder
{
    public function run(): void
    {
        $faker = \Faker\Factory::create('it_IT');

        $clients = DgClient::all();
        if ($clients->isEmpty()) {
            $this->command?->error('⚠ Nessun DgClient presente. Prima lancia ClientsSeeder.');
            return;
        }

        $types = ['privato', 'pubblico'];

        // 20 siti generici
        for ($i = 1; $i <= 20; $i++) {

            $client = $clients->random();
            $name = $faker->company . ' - ' . $faker->city;

            // payroll code
            $code = 'SIT' . str_pad((string)$i, 3, '0', STR_PAD_LEFT);

            // address e coordinate fake
            $address = $faker->streetAddress . ', ' . $faker->city;
            $lat = $faker->latitude(41.0, 46.5);
            $lng = $faker->longitude(8.5, 15.0);

            // 20% dei cantieri inattivi
            $active = rand(1, 10) > 2;

            // 30% con anomalie per testare dashboard
            $anomalies = rand(1, 10) <= 3 ? [
                ['type' => 'assenza badge', 'minutes' => rand(5, 120)],
                ['type' => 'fuori area', 'minutes' => rand(10, 60)]
            ] : null;

            DgSite::updateOrCreate(
                ['name' => $name],
                [
                    'client_id' => $client->id,
                    'address' => $address,
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'radius_m' => rand(20, 200),
                    'type' => $faker->randomElement($types),
                    'active' => $active,
                    'payroll_site_code' => $code,
                    'anomaly_flags' => $anomalies,
                ]
            );
        }

        $this->command?->info('✅ SitesSeeder completato: 20 cantieri creati.');
    }
}

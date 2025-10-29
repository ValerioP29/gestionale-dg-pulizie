<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DgClient;
use App\Models\DgSite;

class SitesSeeder extends Seeder
{
    public function run(): void
    {
        $sites = [
            ['name' => 'Cantiere Ospedale A', 'client' => 'Ospedale San Marco', 'payroll_site_code' => 'OSM001', 'type' => 'privato', 'address' => 'Via della Salute 10, Bari'],
            ['name' => 'Scuola Rinascita A',  'client' => 'Scuola Rinascita',   'payroll_site_code' => 'SCU001', 'type' => 'pubblico', 'address' => 'Via dei Bambini 5, Napoli'],
            ['name' => 'Uffici Centro',       'client' => 'Uffici Fijnive',     'payroll_site_code' => 'UFF010', 'type' => 'privato', 'address' => 'Via Centrale 18, Roma'],
            ['name' => 'Museo Cittadino',     'client' => 'Museo Cittadino',    'payroll_site_code' => 'MUS002', 'type' => 'pubblico', 'address' => 'Piazza Museo 3, Milano'],
        ];

        foreach ($sites as $s) {
            $client = DgClient::where('name', $s['client'])->first();
            if (!$client) continue;

            DgSite::updateOrCreate(
                ['name' => $s['name']],
                [
                    'client_id' => $client->id,
                    'payroll_site_code' => $s['payroll_site_code'],
                    'type' => $s['type'],
                    'address' => $s['address'],
                    'active' => true,
                ]
            );
        }
    }
}

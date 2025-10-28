<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SitesSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $clientIds = DB::table('dg_clients')->pluck('id')->all();

        $sites = [
            ['name'=>'Cantiere Ospedale A', 'address'=>'Via Ospedale 1', 'lat'=>41.12, 'lng'=>16.87, 'radius'=>80],
            ['name'=>'GDO Le Palme',       'address'=>'Viale Commercio 22', 'lat'=>41.13, 'lng'=>16.90, 'radius'=>120],
            ['name'=>'Uffici Centro',      'address'=>'Via Duomo 5', 'lat'=>41.11, 'lng'=>16.86, 'radius'=>60],
            ['name'=>'Condominio Aurora',  'address'=>'Via Po 10', 'lat'=>41.10, 'lng'=>16.85, 'radius'=>50],
            ['name'=>'MetalTech Plant',    'address'=>'Zona Industriale 7', 'lat'=>41.15, 'lng'=>16.95, 'radius'=>150],
            ['name'=>'Ospedale B',         'address'=>'Via Croce 9', 'lat'=>41.16, 'lng'=>16.92, 'radius'=>100],
            ['name'=>'GDO Le Palme 2',     'address'=>'Viale Commercio 30', 'lat'=>41.14, 'lng'=>16.89, 'radius'=>120],
            ['name'=>'Uffici Nord',        'address'=>'Via Milano 12', 'lat'=>41.20, 'lng'=>16.85, 'radius'=>70],
            ['name'=>'Uffici Sud',         'address'=>'Via Bari 44', 'lat'=>41.05, 'lng'=>16.88, 'radius'=>70],
            ['name'=>'Scuola Rinascita',   'address'=>'Via Scuole 3', 'lat'=>41.08, 'lng'=>16.82, 'radius'=>60],
            ['name'=>'Teatro Comunale',    'address'=>'Piazza Teatro 1', 'lat'=>41.09, 'lng'=>16.81, 'radius'=>60],
            ['name'=>'Museo Cittadino',    'address'=>'Via Museo 2', 'lat'=>41.07, 'lng'=>16.83, 'radius'=>60],
        ];

        foreach ($sites as $i => $s) {
            DB::table('dg_sites')->updateOrInsert(
                ['name' => $s['name']],
                [
                    'address'   => $s['address'],
                    'latitude'  => $s['lat'],
                    'longitude' => $s['lng'],
                    'radius_m'  => $s['radius'],
                    'active'    => true,
                    'type'      => 'privato',
                    'client_id' => $clientIds[$i % max(count($clientIds),1)] ?? null,
                    'created_at'=> $now,
                    'updated_at'=> $now,
                ]
            );
        }
    }
}

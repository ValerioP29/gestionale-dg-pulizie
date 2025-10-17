<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\DgSite;

class SitesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('dg_sites')->insert([
            [
                'name' => 'Cantiere Centro Storico',
                'address' => 'Via Roma 12, Terracina',
                'latitude' => 41.289101,
                'longitude' => 13.245812,
                'radius_m' => 150,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Cantiere Ospedale',
                'address' => 'Via San Francesco 8, Terracina',
                'latitude' => 41.288512,
                'longitude' => 13.239411,
                'radius_m' => 200,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        //Assegna l’admin al primo cantiere
        $admin = User::where('email', 'admin@dg.local')->first();
        $site  = DgSite::first();

        if ($admin && $site) {
            $site->users()->attach($admin->id, [
                'assigned_from' => now(),
                'assigned_by'   => $admin->id,
                'notes'         => 'Assegnazione demo automatica',
            ]);
        }
    }
}

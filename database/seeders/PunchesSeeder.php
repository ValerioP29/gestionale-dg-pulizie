<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\DgSite;
use App\Models\DgPunch;
use Illuminate\Support\Str;

class PunchesSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();
        $site = DgSite::first();

        if ($user && $site) {
            DgPunch::insert([
                [
                    'uuid' => Str::uuid(),
                    'user_id' => $user->id,
                    'site_id' => $site->id,
                    'type' => 'check_in',
                    'latitude' => 41.289100,
                    'longitude' => 13.245800,
                    'accuracy_m' => 5,
                    'device_id' => 'ANDROID_123',
                    'device_battery' => 87,
                    'network_type' => 'WiFi',
                    'created_at' => now()->subHours(8),
                    'synced_at' => now()->subHours(7),
                ],
                [
                    'uuid' => Str::uuid(),
                    'user_id' => $user->id,
                    'site_id' => $site->id,
                    'type' => 'check_out',
                    'latitude' => 41.289200,
                    'longitude' => 13.245900,
                    'accuracy_m' => 6,
                    'device_id' => 'ANDROID_123',
                    'device_battery' => 45,
                    'network_type' => '4G',
                    'created_at' => now()->subHours(1),
                    'synced_at' => now(),
                ],
            ]);
        }
    }
}

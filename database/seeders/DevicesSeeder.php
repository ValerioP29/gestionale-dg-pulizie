<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\DgDevice;

class DevicesSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();

        if ($user) {
            DgDevice::create([
                'user_id' => $user->id,
                'device_id' => 'ANDROID_123',
                'platform' => 'android',
                'registered_at' => now()->subDays(5),
                'last_sync_at' => now(),
            ]);
        }
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DgSyncQueue;
use App\Models\User;
use Illuminate\Support\Str;

class SyncQueueSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();

        if ($user) {
            DgSyncQueue::create([
                'user_id' => $user->id,
                'uuid' => Str::uuid(),
                'payload' => [
                    'type' => 'check_in',
                    'latitude' => 41.2891,
                    'longitude' => 13.2458,
                    'timestamp' => now(),
                ],
                'status' => 'pending',
                'synced' => false,
                'retry_count' => 0,
            ]);
        }
    }
}

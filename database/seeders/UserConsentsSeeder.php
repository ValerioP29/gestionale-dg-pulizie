<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DgUserConsent;

class UserConsentsSeeder extends Seeder
{
    public function run(): void
    {
        DgUserConsent::updateOrCreate([
            'user_id' => 1,
            'type' => 'privacy',
        ], [
            'accepted' => true,
            'accepted_at' => now(),
            'source' => 'admin',
        ]);
    }
}

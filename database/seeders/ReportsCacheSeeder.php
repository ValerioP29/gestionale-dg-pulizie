<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Jobs\GenerateReportsCache;
use Carbon\Carbon;

class ReportsCacheSeeder extends Seeder
{
    public function run(): void
    {
        $start = Carbon::now()->startOfMonth()->subMonths(2)->toDateString();
        $end   = Carbon::now()->endOfMonth()->toDateString();

        $this->command?->info("⏳ Generazione report cache: $start → $end");

        dispatch_sync(new GenerateReportsCache($start, $end));

        $this->command?->info("✅ ReportsCacheSeeder completato.");
    }
}

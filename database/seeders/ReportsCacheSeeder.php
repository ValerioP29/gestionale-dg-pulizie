<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Jobs\GenerateReportsCache;
use Carbon\Carbon;

class ReportsCacheSeeder extends Seeder
{
    public function run(): void
    {
        $start = Carbon::now()->startOfMonth()->toDateString();
        $end   = Carbon::now()->endOfMonth()->toDateString();

        dispatch_sync(new GenerateReportsCache($start, $end));
    }
}

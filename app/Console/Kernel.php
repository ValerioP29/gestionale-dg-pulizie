<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Jobs\GenerateReportsCache;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Genera sessioni giornaliere
        $schedule->command('calculate:sessions')->dailyAt('02:00');

        // Genera report manuale standard (ogni mese consolidato)
        $schedule->job(new GenerateReportsCache())->monthlyOn(1, '03:00');

        // Marca i report mensili come "finali" (is_final = true)
        $schedule->call(function () {
            \App\Models\DgReportCache::whereBetween('period_start', [
                now()->startOfMonth(),
                now()->endOfMonth(),
            ])->update(['is_final' => true]);
        })->monthlyOn(1, '03:10');
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
}

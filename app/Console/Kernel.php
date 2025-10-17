<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // 🕒 Job giornaliero: calcola le sessioni di lavoro
        $schedule->job(new \App\Jobs\GenerateWorkSessions())->dailyAt('00:15');

        // 🗓️ Job mensile: genera la cache dei report
        $schedule->job(new \App\Jobs\GenerateReportsCacheJob())->monthlyOn(1, '02:00');
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}

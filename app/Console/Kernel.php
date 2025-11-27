<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Jobs\GenerateReportsCache;
use App\Support\ReportsCacheStatus;
use Illuminate\Support\CarbonImmutable;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Genera sessioni giornaliere
        $schedule->command('calculate:sessions')->dailyAt('02:00');

        // Rigenera automaticamente il mese corrente ogni notte
        $schedule->call(function () {
            if (ReportsCacheStatus::isRunning()) {
                Log::info('ReportsCache: esecuzione automatica saltata, job già in corso.');

                return;
            }

            $now = now();
            $currentStart = CarbonImmutable::create($now->year, $now->month, 1)->startOfMonth();
            $currentEnd = $currentStart->endOfMonth();

            if ($now->isSameDay($currentStart)) {
                $previousStart = $currentStart->subMonth()->startOfMonth();
                $previousEnd = $previousStart->endOfMonth();

                GenerateReportsCache::dispatch(
                    $previousStart->toDateString(),
                    $previousEnd->toDateString()
                );
            }

            GenerateReportsCache::dispatch(
                $currentStart->toDateString(),
                $currentEnd->toDateString()
            );
        })->dailyAt('03:00');

        // Marca i report mensili come "finali" (is_final = true)
        $schedule->call(function () {
            $anchor = now()->subMonthNoOverflow();
            $start  = $anchor->copy()->startOfMonth();
            $end    = $anchor->copy()->endOfMonth();

            \App\Models\DgReportCache::whereBetween('period_start', [
                $start->toDateString(),
                $end->toDateString(),
            ])->update(['is_final' => true]);
        })->monthlyOn(1, '03:10');
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }

    protected $commands = [
    \App\Console\Commands\GenerateAnomalies::class,
    \App\Console\Commands\RebuildWorkSessionsCommand::class,
];

}

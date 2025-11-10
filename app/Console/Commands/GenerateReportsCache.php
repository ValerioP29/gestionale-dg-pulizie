<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use App\Jobs\GenerateReportsCache as GenerateReportsCacheJob;
use App\Support\ReportsCacheStatus;

class GenerateReportsCache extends Command
{
    protected $signature = 'generate:reports-cache {--month=} {--year=}';
    protected $description = 'Rigenera la cache dei report mensili per ore lavorate e presenze';

    public function handle(): void
    {
        $year = $this->option('year') ?? now()->year;
        $month = $this->option('month') ?? now()->month;

        $start = CarbonImmutable::createFromDate($year, $month, 1)->startOfMonth();
        $end = $start->endOfMonth();

        $this->info("📊 Generazione reports_cache da {$start->toDateString()} a {$end->toDateString()}");

        if (ReportsCacheStatus::isRunning()) {
            $this->warn('⚠️ Rigenerazione già in corso, attendi il completamento prima di riprovare.');

            return;
        }

        ReportsCacheStatus::markPending([
            'period_start' => $start->toDateString(),
            'period_end' => $end->toDateString(),
            'source' => 'cli',
        ]);

        GenerateReportsCacheJob::dispatchSync(
            $start->toDateString(),
            $end->toDateString()
        );

        $this->info('✅ Cache dei report rigenerata tramite job dedicato');
        Log::info("Report cache aggiornato per {$month}/{$year}");
    }
}

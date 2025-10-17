<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DgWorkSession;
use App\Models\DgReportsCache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\CarbonImmutable;

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

        $sessions = DgWorkSession::query()
            ->whereBetween('session_date', [$start, $end])
            ->get()
            ->groupBy(['user_id', 'site_id']);

        $total = 0;

        foreach ($sessions as $userId => $bySite) {
            foreach ($bySite as $siteId => $records) {
                $hours = round($records->sum('worked_minutes') / 60, 2);

                DgReportsCache::updateOrCreate(
                    [
                        'user_id' => $userId,
                        'site_id' => $siteId,
                        'period_start' => $start,
                        'period_end' => $end,
                    ],
                    [
                        'worked_hours' => $hours,
                        'is_valid' => true,
                        'generated_at' => now(),
                    ]
                );

                $total++;
            }
        }

        $this->info("✅ Generati {$total} record di report cache");
        Log::info("Report cache aggiornato ({$total} record) per {$month}/{$year}");
    }
}

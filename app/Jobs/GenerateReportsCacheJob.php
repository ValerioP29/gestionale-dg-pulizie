<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class GenerateReportsCacheJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected ?int $month;
    protected ?int $year;

    public function __construct(?int $month = null, ?int $year = null)
    {
        $this->month = $month;
        $this->year = $year;
    }

    public function handle(): void
    {
        $args = [];
        if ($this->month) $args['--month'] = $this->month;
        if ($this->year) $args['--year'] = $this->year;

        Log::info("🕒 Avvio job GenerateReportsCacheJob", $args);

        Artisan::call('generate:reports-cache', $args);

        Log::info("✅ Job GenerateReportsCacheJob completato");
    }
}

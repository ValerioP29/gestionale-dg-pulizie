<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\GenerateWorkSessions;

class CalculateSessions extends Command
{
    protected $signature = 'calculate:sessions {date? : Data nel formato YYYY-MM-DD}';
    protected $description = 'Rigenera le sessioni lavorative per la data specificata o per ieri.';

    public function handle(): int
    {
        $date = $this->argument('date') ?? now()->subDay()->toDateString();
        $this->info("🔄 Calcolo sessioni per la data: {$date}");

        GenerateWorkSessions::dispatch($date);

        $this->info('✅ Job di rigenerazione sessioni messo in coda.');
        return Command::SUCCESS;
    }
}

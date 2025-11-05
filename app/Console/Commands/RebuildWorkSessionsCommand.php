<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\RebuildWorkSessions;

class RebuildWorkSessionsCommand extends Command
{
    // date è opzionale: se manca → ricostruisci tutto
    protected $signature = 'sync:rebuild-sessions {date?}';
    protected $description = 'Ricostruisce le sessioni di lavoro da timbrature. Senza data → ricostruisce TUTTO.';

    public function handle()
    {
        $date = $this->argument('date');

        dispatch(new RebuildWorkSessions($date));

        if ($date) {
            $this->info("✅ Avviata ricostruzione sessioni per la data: {$date}");
        } else {
            $this->info("✅ Avviata ricostruzione COMPLETA (tutte le date)!");
        }

        $this->info("💡 Avvia `php artisan queue:work` per processare i job se non è già attivo.");
        return Command::SUCCESS;
    }
}

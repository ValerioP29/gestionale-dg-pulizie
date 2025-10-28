<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DgWorkSession;
use App\Services\Anomalies\AnomalyEngine;

class GenerateAnomalies extends Command
{
    protected $signature = 'dg:generate-anomalies {--from=} {--to=} {--user=} {--site=}';
    protected $description = 'Rigenera anomalie e overtime per intervallo e filtri';

    public function handle()
    {
        $engine = new AnomalyEngine();

        $q = DgWorkSession::query();

        if ($f = $this->option('from')) $q->whereDate('session_date', '>=', $f);
        if ($t = $this->option('to'))   $q->whereDate('session_date', '<=', $t);
        if ($u = $this->option('user')) $q->where('user_id', $u);
        if ($s = $this->option('site')) $q->where(function($qq) use ($s){
            $qq->where('resolved_site_id', $s)->orWhere('site_id', $s);
        });

        $count = 0;
        $q->orderBy('session_date')->chunk(300, function ($chunk) use (&$count, $engine) {
            foreach ($chunk as $session) {
                $engine->evaluateSession($session);
                $count++;
            }
        });

        $this->info("Anomalie rigenerate per {$count} sessioni.");
    }
}

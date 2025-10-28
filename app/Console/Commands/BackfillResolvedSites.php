<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DgWorkSession;
use App\Services\SiteResolverService;
use Carbon\Carbon;

class BackfillResolvedSites extends Command
{
    protected $signature = 'dg:backfill-resolved-sites';
    protected $description = 'Popola resolved_site_id per le sessioni esistenti';

    public function handle()
    {
        $this->info('Inizio backfill resolved_site_id...');

        DgWorkSession::with('user')
            ->whereNull('resolved_site_id')
            ->chunk(200, function ($sessions) {
                foreach ($sessions as $session) {
                    $resolvedId = SiteResolverService::resolveFor(
                        $session->user,
                        $session->site_id,
                        Carbon::parse($session->session_date)
                    );
                    $session->resolved_site_id = $resolvedId;
                    $session->saveQuietly();
                }
            });

        $this->info('Backfill completato con successo.');
    }
}

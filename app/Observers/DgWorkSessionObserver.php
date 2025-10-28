<?php

namespace App\Observers;

use App\Models\DgWorkSession;
use App\Services\SiteResolverService;
use App\Services\Anomalies\AnomalyEngine;
use Carbon\Carbon;

class DgWorkSessionObserver
{
    public function saving(DgWorkSession $session): void
    {
        if ($session->user_id && $session->session_date) {
            $session->resolved_site_id = SiteResolverService::resolveFor(
                $session->user ?? $session->user()->first(),
                $session->site_id,
                Carbon::parse($session->session_date)
            );
        }
    }

    public function saved(DgWorkSession $session): void
    {
        // Evita ricorsioni inutili: ricalcola anomalie solo se è cambiato qualcosa di interessante
        if ($session->wasChanged(['check_in','check_out','worked_minutes','status','site_id','resolved_site_id'])) {
            (new AnomalyEngine())->evaluateSession($session);
        }
    }
}

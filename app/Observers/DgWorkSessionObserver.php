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
        // 1) Risolvi cantiere effettivo (come già facevi)
        if ($session->user_id && $session->session_date) {
            $session->resolved_site_id = SiteResolverService::resolveFor(
                $session->user ?? $session->user()->first(),
                $session->site_id,
                Carbon::parse($session->session_date)
            );
        }

        // 2) Status in base alla presenza di check_in / check_out
        $hasIn  = !is_null($session->check_in);
        $hasOut = !is_null($session->check_out);

        if (!$hasIn && !$hasOut) {
            $session->status = 'invalid';
            $session->worked_minutes = 0;
            return;
        }

        if ($hasIn && $hasOut) {
            $session->status = 'complete';

            // 3) Calcolo minuti robusto (overnight/DST), evitando invertiti
            $in  = Carbon::parse($session->check_in);
            $out = Carbon::parse($session->check_out);

            // Turno oltre mezzanotte
            if ($out->lessThan($in)) {
                $out = $out->copy()->addDay();
            }

            // Timestamp diff per ignorare buchi DST e TZ “naive”
            $minutes = intdiv($out->getTimestamp() - $in->getTimestamp(), 60);

            // Clamp anti-spazzatura
            if ($minutes < 0) $minutes = 0;
            if ($minutes > 18 * 60) $minutes = 18 * 60;

            $session->worked_minutes = $minutes;
            return;
        }

        // Solo uno dei due → incomplete, minuti = 0
        $session->status = 'incomplete';
        $session->worked_minutes = 0;
    }

    public function saved(DgWorkSession $session): void
    {
        // Ricalcola anomalie solo se qualcosa di rilevante è cambiato
        if ($session->wasChanged(['check_in','check_out','worked_minutes','status','site_id','resolved_site_id'])) {
            (new AnomalyEngine())->evaluateSession($session);
        }
    }
}

<?php

namespace App\Observers;

use App\Models\DgPunch;
use App\Models\DgWorkSession;
use App\Services\Anomalies\AnomalyEngine;
use App\Services\SiteResolverService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DgPunchObserver
{
    public function creating(DgPunch $punch): void
    {
        // Normalizza type
        $type = strtolower(trim((string) $punch->type));
        if (!in_array($type, ['check_in', 'check_out'], true)) {
            $type = 'check_in';
        }
        $punch->type = $type;

        // UUID se manca
        if (empty($punch->uuid)) {
            $punch->uuid = (string) Str::uuid();
        }
    }

    public function created(DgPunch $punch): void
    {
        $when = $punch->punchInstant();               // robusto a offline
        $sessionDate = $when->toDateString();

        DB::transaction(function () use ($punch, $sessionDate, $when) {
            // 1) Trova/crea sessione del giorno (per utente)
            $session = DgWorkSession::firstOrCreate(
                [
                    'user_id'      => $punch->user_id,
                    'session_date' => $sessionDate,
                ],
                [
                    'site_id'        => $punch->site_id,
                    'status'         => 'incomplete',
                    'worked_minutes' => 0,
                    'source'         => $punch->source ?: 'auto',
                ]
            );

            // 2) Link punch → sessione
            if (!$punch->session_id) {
                $punch->session_id = $session->id;
                // QUI niente saveQuietly: vogliamo firing eventi se cambi in futuro
                $punch->save();
            }

            // 3) Se il punch fornisce site_id e la sessione non ce l'ha, impostalo
            if (!$session->site_id && $punch->site_id) {
                $session->site_id = $punch->site_id;
            }

            // 4) Applica check-in/out usando il timestamp “vero”
            if ($punch->type === 'check_in') {
                if (is_null($session->check_in) || $when->lt(Carbon::parse($session->check_in))) {
                    $session->check_in = $when;
                }
            } else { // check_out
                if (is_null($session->check_out) || $when->gt(Carbon::parse($session->check_out))) {
                    $session->check_out = $when;
                }
            }

            // 5) Risolvi sito effettivo (come facevi)
            if ($session->user_id && $session->session_date) {
                $session->resolved_site_id = SiteResolverService::resolveFor(
                    $session->user ?? $session->user()->first(),
                    $session->site_id,
                    Carbon::parse($session->session_date)
                );
            }

            // 6) Salva con eventi attivi: il WorkSessionObserver calcola status + minutes
            $session->save();

            // 7) Valuta anomalie
            (new AnomalyEngine())->evaluateSession($session);
        });
    }

    public function updated(DgPunch $punch): void
    {
        // Se una timbratura viene corretta da pannello: ricalcola
        if ($punch->session_id) {
            $session = $punch->session()->first();
            if ($session) {
                $session->save(); // trigger observer
                (new AnomalyEngine())->evaluateSession($session);
            }
        }
    }

    public function deleted(DgPunch $punch): void
    {
        // Ricalcola dalla collezione residua (coerente con 'check_in'/'check_out')
        if (!$punch->session_id) return;

        $session = DgWorkSession::find($punch->session_id);
        if (!$session) return;

        DB::transaction(function () use ($session) {
            $in  = $session->punches()
                ->where('type', 'check_in')
                ->orderBy('created_at')
                ->first();

            $out = $session->punches()
                ->where('type', 'check_out')
                ->orderByDesc('created_at')
                ->first();

            $session->check_in  = $in?->punchInstant();
            $session->check_out = $out?->punchInstant();

            $session->save(); // observer ricalcola e poi anomalie
            (new AnomalyEngine())->evaluateSession($session);
        });
    }
}

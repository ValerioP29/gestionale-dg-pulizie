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
        \DB::afterCommit(function () use ($punch) {

            $p = DgPunch::find($punch->id);
            if (!$p) return;

            $when = $p->punchInstant();
            $sessionDate = $when->toDateString();

            \DB::transaction(function () use ($p, $when, $sessionDate) {

                $session = DgWorkSession::firstOrCreate(
                    [
                        'user_id'      => $p->user_id,
                        'session_date' => $sessionDate,
                    ],
                    [
                        'site_id'        => $p->site_id,
                        'status'         => 'incomplete',
                        'worked_minutes' => 0,
                        'source'         => $p->source ?: 'auto',
                    ]
                );

                // Link punch → sessione SENZA eventi
                DgPunch::withoutEvents(function () use ($p, $session) {
                    if (!$p->session_id) {
                        $p->update(['session_id' => $session->id]);
                    }
                });

                // Check-in / Check-out
                if ($p->type === 'check_in') {
                    if (is_null($session->check_in) || $when->lt($session->check_in)) {
                        $session->check_in = $when;
                    }
                } else {
                    if (is_null($session->check_out) || $when->gt($session->check_out)) {
                        $session->check_out = $when;
                    }
                }

                $session->save();
            });
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

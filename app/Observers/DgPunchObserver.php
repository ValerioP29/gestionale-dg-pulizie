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
        // Normalizza type ed evita valore fuori enum
        $punch->type = strtolower(trim((string)$punch->type));
        if (!in_array($punch->type, ['in','out'], true)) {
            $punch->type = 'in';
        }

        // UUID se manca
        if (empty($punch->uuid)) {
            $punch->uuid = (string) Str::uuid();
        }
    }

    public function created(DgPunch $punch): void
    {
        // Usa created_at come timestamp timbratura, se non hai punch_at/punched_at
        $when = $punch->created_at instanceof \DateTimeInterface
            ? Carbon::parse($punch->created_at)
            : now();

        $sessionDate = $when->toDateString();

        DB::transaction(function () use ($punch, $sessionDate, $when) {
            // Trova/crea sessione del giorno
            $session = DgWorkSession::firstOrCreate(
                [
                    'user_id'      => $punch->user_id,
                    'session_date' => $sessionDate,
                ],
                [
                    'site_id'        => $punch->site_id, // seed iniziale
                    'status'         => 'incomplete',
                    'worked_minutes' => 0,
                    'source'         => $punch->source ?: 'auto',
                ]
            );

            // Associa il punch alla sessione
            if (!$punch->session_id) {
                $punch->session_id = $session->id;
                $punch->saveQuietly();
            }

            // Se arriva un site_id dal punch e la sessione è vuota, impostalo
            if (!$session->site_id && $punch->site_id) {
                $session->site_id = $punch->site_id;
            }

            // Applica check-in/check-out
            if ($punch->type === 'in') {
                // Usa il primo check-in valido
                if (is_null($session->check_in) || $when->lt($session->check_in)) {
                    $session->check_in = $when;
                }
            } else { // out
                // Usa l'ultimo check-out valido
                if (is_null($session->check_out) || $when->gt($session->check_out)) {
                    $session->check_out = $when;
                }
            }

            // Ricalcolo minutes
            if ($session->check_in && $session->check_out) {
                // pausa fissa 30' per default; verrà soppiantata dal contratto nel motore anomalie
                $worked = max(0, $session->check_out->diffInMinutes($session->check_in) - 30);
                $session->worked_minutes = $worked;
                $session->status = $worked > 0 ? 'complete' : 'invalid';
            } else {
                $session->status = 'incomplete';
            }

            // Risolvi cantiere effettivo
            $session->resolved_site_id = SiteResolverService::resolveFor(
                $session->user ?? $session->user()->first(),
                $session->site_id,
                Carbon::parse($session->session_date)
            );

            $session->saveQuietly();

            // Valuta anomalie per la sessione
            (new AnomalyEngine())->evaluateSession($session);
        });
    }

    public function updated(DgPunch $punch): void
    {
        // Se una timbratura viene corretta da pannello: ricalcola
        if ($punch->session_id) {
            $session = $punch->session()->first();
            if ($session) {
                (new AnomalyEngine())->evaluateSession($session);
            }
        }
    }

    public function deleted(DgPunch $punch): void
    {
        // In caso di cancellazione: ricalcola la sessione
        if ($punch->session_id) {
            $session = DgWorkSession::find($punch->session_id);
            if (!$session) return;

            DB::transaction(function () use ($session) {
                // Rigenera check_in/out dalla collezione residua
                $ins  = $session->punches()->where('type','in')->orderBy('created_at')->first();
                $outs = $session->punches()->where('type','out')->orderByDesc('created_at')->first();

                $session->check_in  = $ins?->created_at;
                $session->check_out = $outs?->created_at;

                if ($session->check_in && $session->check_out) {
                    $session->worked_minutes = max(0, $session->check_out->diffInMinutes($session->check_in) - 30);
                    $session->status = $session->worked_minutes > 0 ? 'complete' : 'invalid';
                } else {
                    $session->worked_minutes = 0;
                    $session->status = 'incomplete';
                }

                $session->saveQuietly();
                (new AnomalyEngine())->evaluateSession($session);
            });
        }
    }
}

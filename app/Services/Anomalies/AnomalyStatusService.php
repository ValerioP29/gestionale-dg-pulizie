<?php

namespace App\Services\Anomalies;

use App\Models\DgAnomaly;
use App\Models\User;

class AnomalyStatusService
{
    public function approve(DgAnomaly $anomaly, User $actor): void
    {
        // Evita di ri-approvare anomalie già gestite
        if ($anomaly->status && $anomaly->status !== 'open') {
            return;
        }

        $anomaly->status = 'approved';
        $anomaly->approved_at = now();
        $anomaly->approved_by = $actor->getKey();
        $anomaly->save();

        // Rimuovi flag dalla sessione
        if ($anomaly->session) {
            $flags = $anomaly->session->anomaly_flags ?? [];
            $filtered = array_filter(
                $flags,
                fn ($flag) => ($flag['type'] ?? null) !== $anomaly->type
            );

            $anomaly->session->anomaly_flags = array_values($filtered);
            $anomaly->session->save();
        }

        activity('Anomalie')
            ->causedBy($actor)
            ->performedOn($anomaly)
            ->withProperties([
                'anomaly_id' => $anomaly->id,
                'status' => 'approved',
                'note' => $anomaly->note,
            ])
            ->log('Anomalia approvata');
    }

    public function reject(DgAnomaly $anomaly, User $actor, ?string $reason = null): void
    {
        // Evita ri-rifiuti
        if ($anomaly->status && $anomaly->status !== 'open') {
            return;
        }

        $anomaly->status = 'rejected';
        $anomaly->rejected_at = now();
        $anomaly->rejected_by = $actor->getKey();

        if ($reason) {
            $anomaly->note = $anomaly->note
                ? ($anomaly->note . "\nRifiuto: {$reason}")
                : $reason;
        }

        $anomaly->save();

        // se respingi e la sessione era completa, tornerebbe "incomplete"
        if ($anomaly->session) {
            $session = $anomaly->session;
            if ($session->status === 'complete') {
                $session->status = 'incomplete';
            }
            $session->save();
        }

        activity('Anomalie')
            ->causedBy($actor)
            ->performedOn($anomaly)
            ->withProperties([
                'anomaly_id' => $anomaly->id,
                'status' => 'rejected',
                'reason' => $reason,
            ])
            ->log('Anomalia respinta');
    }
}

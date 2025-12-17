<?php

namespace App\Services\Anomalies;

use App\Models\DgAnomaly;
use App\Models\User;

class AnomalyStatusService
{
    public function approve(DgAnomaly $anomaly, User $actor, ?string $note = null): bool
    {
        // Evita di ri-approvare anomalie già gestite
        if ($anomaly->status && $anomaly->status !== 'open') {
            return false;
        }

        $anomaly->status = 'approved';
        $anomaly->approved_at = now();
        $anomaly->approved_by = $actor->getKey();

        $this->appendNote($anomaly, $note, 'Approvazione');
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

        return true;
    }

    public function reject(DgAnomaly $anomaly, User $actor, ?string $reason = null): bool
    {
        // Evita ri-rifiuti
        if ($anomaly->status && $anomaly->status !== 'open') {
            return false;
        }

        $anomaly->status = 'rejected';
        $anomaly->rejected_at = now();
        $anomaly->rejected_by = $actor->getKey();

        $this->appendNote($anomaly, $reason, 'Rifiuto');

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

        return true;
    }

    private function appendNote(DgAnomaly $anomaly, ?string $note, string $context): void
    {
        $note = trim((string) $note);

        if ($note === '') {
            return;
        }

        $contextNote = sprintf('%s: %s', $context, $note);

        if (filled($anomaly->note)) {
            $anomaly->note = trim($anomaly->note . "\n" . $contextNote);
        } else {
            $anomaly->note = $contextNote;
        }
    }
}

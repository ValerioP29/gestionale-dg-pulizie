<?php

namespace App\Services\Anomalies;

use App\Models\DgAnomaly;
use App\Models\User;
class AnomalyStatusService
{
    public function approve(DgAnomaly $anomaly, User $actor): void
    {
        $anomaly->status = 'approved';
        $anomaly->approved_at = now();
        $anomaly->approved_by = $actor->getKey();
        $anomaly->save();

        if ($anomaly->session) {
            $flags = $anomaly->session->anomaly_flags ?? [];
            $filtered = array_filter($flags, fn ($flag) => ($flag['type'] ?? null) !== $anomaly->type);
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
        $anomaly->status = 'rejected';
        $anomaly->rejected_at = now();
        $anomaly->rejected_by = $actor->getKey();

        if ($reason) {
            $anomaly->note = $anomaly->note
                ? ($anomaly->note . "\nRifiuto: {$reason}")
                : $reason;
        }

        $anomaly->save();

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

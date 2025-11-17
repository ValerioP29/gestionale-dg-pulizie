<?php

namespace App\Services\WorkSessions;

use App\Models\DgWorkSession;
use App\Models\User;

class WorkSessionApprovalService
{
    public function approve(DgWorkSession $session, User $actor): void
    {
        $session->approval_status = 'approved';
        $session->approved_at = now();
        $session->approved_by = $actor->getKey();
        $session->anomaly_flags = [];
        $session->save();

        activity('Sessioni di lavoro')
            ->causedBy($actor)
            ->performedOn($session)
            ->withProperties([
                'session_id' => $session->id,
            ])
            ->log('Sessione di lavoro approvata manualmente');
    }

    public function reject(DgWorkSession $session, User $actor, ?string $reason = null): void
    {
        $session->approval_status = 'rejected';
        $session->rejected_at = now();
        $session->rejected_by = $actor->getKey();

        if ($reason) {
            $session->override_reason = $reason;
        }

        $session->save();

        activity('Sessioni di lavoro')
            ->causedBy($actor)
            ->performedOn($session)
            ->withProperties([
                'session_id' => $session->id,
                'reason' => $reason,
            ])
            ->log('Sessione di lavoro respinta');
    }
}

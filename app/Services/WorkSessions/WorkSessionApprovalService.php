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

        activity('Sesioni di lavoro')
            ->causedBy($actor)
            ->performedOn($session)
            ->log('Sessione di lavoro approvata manualmente');
    }

    public function reject(DgWorkSession $session, User $actor, ?string $reason = null): void
    {
        $session->approval_status = 'rejected';
        $session->approved_at = now();
        $session->approved_by = $actor->getKey();

        if ($reason) {
            $session->override_reason = $reason;
        }

        $session->save();
    }
}

<?php

namespace App\Services\WorkSessions;

use App\Enums\WorkSessionApprovalStatus;
use App\Models\DgWorkSession;
use App\Models\User;
use App\Support\ReportsCacheRegenerator;

class WorkSessionApprovalService
{
    public function approve(DgWorkSession $session, User $actor): void
    {
        $session->approval_status = WorkSessionApprovalStatus::APPROVED->value;
        $session->approved_at = now();
        $session->approved_by = $actor->getKey();
        $session->rejected_at = null;
        $session->rejected_by = null;
        $session->anomaly_flags = [];
        $session->save();

        ReportsCacheRegenerator::dispatchForSessionDate($session->session_date);

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
        $session->approval_status = WorkSessionApprovalStatus::REJECTED->value;
        $session->rejected_at = now();
        $session->rejected_by = $actor->getKey();
        $session->approved_at = null;
        $session->approved_by = null;

        if ($reason) {
            $session->override_reason = $reason;
        }

        $session->save();

        ReportsCacheRegenerator::dispatchForSessionDate($session->session_date);

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

<?php

namespace App\Observers;

use App\Enums\WorkSessionApprovalStatus;
use App\Models\DgWorkSession;
use App\Services\Anomalies\AnomalyEngine;
use App\Services\SiteResolverService;
use App\Support\ReportsCacheRegenerator;
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
            $this->syncApprovalStatus($session);
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
            $this->syncApprovalStatus($session);
            return;
        }

        // Solo uno dei due → incomplete, minuti = 0
        $session->status = 'incomplete';
        $session->worked_minutes = 0;

        $this->syncApprovalStatus($session);
    }

    public function saved(DgWorkSession $session): void
    {
        $fieldsAffectingReports = [
            'check_in',
            'check_out',
            'worked_minutes',
            'status',
            'site_id',
            'resolved_site_id',
            'session_date',
            'overtime_minutes',
        ];

        // Ricalcola anomalie solo se qualcosa di rilevante è cambiato
        if ($session->wasChanged($fieldsAffectingReports)) {
            (new AnomalyEngine())->evaluateSession($session);
            ReportsCacheRegenerator::dispatchForSessionDate($session->session_date);
        }
    }

    private function syncApprovalStatus(DgWorkSession $session): void
    {
        $session->approval_status ??= WorkSessionApprovalStatus::PENDING->value;

        if (! $session->exists) {
            return;
        }

        $approvalSensitive = [
            'check_in',
            'check_out',
            'worked_minutes',
            'session_date',
            'site_id',
            'resolved_site_id',
            'overtime_minutes',
            'extra_minutes',
            'extra_reason',
            'override_reason',
            'override_set_by',
            'status',
        ];

        if (! $session->isDirty($approvalSensitive)) {
            return;
        }

        $original = $session->getOriginal('approval_status');

        if (in_array($original, [
            WorkSessionApprovalStatus::APPROVED->value,
            WorkSessionApprovalStatus::REJECTED->value,
        ], true)) {
            $session->approval_status = WorkSessionApprovalStatus::IN_REVIEW->value;
            $session->approved_at = null;
            $session->approved_by = null;
            $session->rejected_at = null;
            $session->rejected_by = null;

            return;
        }

        if ($original !== WorkSessionApprovalStatus::IN_REVIEW->value) {
            $session->approval_status = WorkSessionApprovalStatus::PENDING->value;
            $session->approved_at = null;
            $session->approved_by = null;
            $session->rejected_at = null;
            $session->rejected_by = null;
        }
    }
}

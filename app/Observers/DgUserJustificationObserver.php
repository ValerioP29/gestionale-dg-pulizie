<?php

namespace App\Observers;

use App\Models\DgUserJustification;
use App\Models\DgWorkSession;
use App\Services\Anomalies\AnomalyEngine;
use App\Support\ReportsCacheRegenerator;
use Carbon\CarbonImmutable;

class DgUserJustificationObserver
{
    public function saved(DgUserJustification $justification): void
    {
        $this->refreshRange($justification);
    }

    public function deleted(DgUserJustification $justification): void
    {
        $this->refreshRange($justification);
    }

    private function refreshRange(DgUserJustification $justification): void
    {
        if (! $justification->user_id || ! $justification->date) {
            return;
        }

        $start = CarbonImmutable::parse($justification->date);
        $end = $justification->date_end
            ? CarbonImmutable::parse($justification->date_end)
            : $start;

        $cursor = $start;
        while ($cursor->lte($end)) {
            $session = DgWorkSession::query()
                ->where('user_id', $justification->user_id)
                ->whereDate('session_date', $cursor->toDateString())
                ->first();

            if ($session) {
                (new AnomalyEngine())->evaluateSession($session);
            }

            ReportsCacheRegenerator::dispatchForSessionDate($cursor);
            $cursor = $cursor->addDay();
        }
    }
}

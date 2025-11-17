<?php

namespace App\Services\Anomalies\Rules;

use App\Models\DgWorkSession;
use App\Services\Anomalies\Support\ContractDayDTO;
use App\Services\Anomalies\Support\ExpectedShiftWindow;
use Carbon\CarbonImmutable;

class LateEarlyRule implements AnomalyRule
{
    public function evaluate(DgWorkSession $s, ?ContractDayDTO $c): array
    {
        if (! $c || ! $s->check_in || ! $s->check_out) {
            return [];
        }

        $lateGrace = (int) config('anomalies.late_grace_minutes', 0);
        $earlyGrace = (int) config('anomalies.early_leave_grace_minutes', 0);

        [$expectedStart, $expectedEnd] = ExpectedShiftWindow::resolve($s, $c);
        $actualStart = CarbonImmutable::parse($s->check_in);
        $actualEnd = CarbonImmutable::parse($s->check_out);

        $results = [];

        if ($actualStart->greaterThan($expectedStart)) {
            $lateMinutes = $actualStart->diffInMinutes($expectedStart);

            if ($lateMinutes > $lateGrace) {
                $results[] = [
                    'type' => 'late_entry',
                    'minutes' => $lateMinutes,
                    'note' => null,
                ];
            }
        }

        if ($expectedEnd->greaterThan($actualEnd)) {
            $earlyMinutes = $expectedEnd->diffInMinutes($actualEnd);

            if ($earlyMinutes > $earlyGrace) {
                $results[] = [
                    'type' => 'early_exit',
                    'minutes' => $earlyMinutes,
                    'note' => null,
                ];
            }
        }

        return $results;
    }
}

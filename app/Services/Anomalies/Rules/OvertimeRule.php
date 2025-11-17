<?php

namespace App\Services\Anomalies\Rules;

use App\Models\DgWorkSession;
use App\Services\Anomalies\Support\ContractDayDTO;
use App\Services\Anomalies\Support\ExpectedShiftWindow;
use Carbon\CarbonImmutable;

class OvertimeRule implements AnomalyRule
{
    public function evaluate(DgWorkSession $s, ?ContractDayDTO $c): array
    {
        if (! $c || ! $s->check_in || ! $s->check_out) {
            return [];
        }

        $minOvertime = (int) config('anomalies.min_overtime_minutes', 0);

        $expected = ExpectedShiftWindow::expectedMinutes($s, $c);

        $worked = CarbonImmutable::parse($s->check_out)
            ->diffInMinutes(CarbonImmutable::parse($s->check_in));

        $extra = max(0, $worked - $expected);

        if ($extra < $minOvertime) {
            $s->overtime_minutes = 0;

            return [];
        }

        $s->overtime_minutes = (int) $extra;

        return [[
            'type' => 'overtime',
            'minutes' => $extra,
            'note' => null,
        ]];
    }
}

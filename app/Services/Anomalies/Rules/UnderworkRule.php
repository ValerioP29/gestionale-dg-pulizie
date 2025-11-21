<?php

namespace App\Services\Anomalies\Rules;

use App\Models\DgWorkSession;
use App\Services\Anomalies\Support\ContractDayDTO;
use App\Services\Anomalies\Support\ExpectedShiftWindow;
use Carbon\CarbonImmutable;

class UnderworkRule implements AnomalyRule
{
    public function evaluate(DgWorkSession $s, ?ContractDayDTO $c): array
    {
        if (! $c) {
            return [];
        }

        $worked = $s->worked_minutes;

        if (is_null($worked)) {
            if (! $s->check_in || ! $s->check_out) {
                return [];
            }

            $worked = CarbonImmutable::parse($s->check_out)
                ->diffInMinutes(CarbonImmutable::parse($s->check_in));

            $worked = max(0, $worked - $c->breakMinutes);
        }

        $expected = ExpectedShiftWindow::expectedMinutes($s, $c);
        $missing = max(0, $expected - $worked);
        $threshold = (int) config('anomalies.min_underwork_minutes', 15);

        if ($missing <= $threshold) {
            return [];
        }

        return [[
            'type' => 'underwork',
            'minutes' => $missing,
            'note' => sprintf(
                'Lavorato %d minuti su %d previsti',
                $worked,
                $expected
            ),
        ]];
    }
}

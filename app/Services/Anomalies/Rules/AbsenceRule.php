<?php

namespace App\Services\Anomalies\Rules;

use App\Models\DgWorkSession;
use App\Services\Anomalies\Support\ContractDayDTO;
use App\Services\Anomalies\Support\ExpectedShiftWindow;
use App\Services\Justifications\JustificationCalendar;
use Carbon\CarbonImmutable;

class AbsenceRule implements AnomalyRule
{
    public function __construct(private readonly JustificationCalendar $calendar)
    {
    }

    public function evaluate(DgWorkSession $s, ?ContractDayDTO $c): array
    {
        if (! $c) {
            return [];
        }

        $noWork = is_null($s->check_in) && is_null($s->check_out) && (int) $s->worked_minutes === 0;

        if (! $noWork) {
            return [];
        }

        if ($s->user_id && $s->session_date) {
            $date = CarbonImmutable::parse($s->session_date);
            $expected = ExpectedShiftWindow::expectedMinutes($s, $c);

            if ($this->calendar->hasCoverageFor($s->user_id, $date, $expected)) {
                return [];
            }
        }

        return [[
            'type' => 'absence',
            'minutes' => 0,
            'note' => null,
        ]];
    }
}

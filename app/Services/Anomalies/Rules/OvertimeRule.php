<?php

namespace App\Services\Anomalies\Rules;

use App\Models\DgWorkSession;
use App\Services\Anomalies\Support\ContractDayDTO;
use Carbon\Carbon;

class OvertimeRule implements AnomalyRule
{
    public function evaluate(DgWorkSession $s, ?ContractDayDTO $c): array
    {
        if (!$c || !$s->check_in || !$s->check_out) return [];
        $expected = Carbon::parse($c->expectedEnd)->diffInMinutes(Carbon::parse($c->expectedStart)) - $c->breakMinutes;
        $worked   = Carbon::parse($s->check_out)->diffInMinutes(Carbon::parse($s->check_in));
        $extra    = max(0, $worked - max(0, $expected));

        // aggiorno il campo sessione per coerenza con i report
        $s->overtime_minutes = (int)$extra;

        return $extra > 0 ? [['type'=>'overtime','minutes'=>$extra,'note'=>null]] : [];
    }
}

<?php

namespace App\Services\Anomalies\Rules;

use App\Models\DgWorkSession;
use App\Services\Anomalies\Support\ContractDayDTO;
use Carbon\Carbon;

class LateEarlyRule implements AnomalyRule
{
    public function evaluate(DgWorkSession $s, ?ContractDayDTO $c): array
    {
        if (!$c || !$s->check_in || !$s->check_out) return [];
        $res = [];
        if (Carbon::parse($s->check_in)->gt(Carbon::parse($c->expectedStart))) {
            $res[] = ['type'=>'late_entry','minutes'=>Carbon::parse($s->check_in)->diffInMinutes(Carbon::parse($c->expectedStart)), 'note'=>null];
        }
        if (Carbon::parse($s->check_out)->lt(Carbon::parse($c->expectedEnd))) {
            $res[] = ['type'=>'early_exit','minutes'=>Carbon::parse($c->expectedEnd)->diffInMinutes(Carbon::parse($s->check_out)), 'note'=>null];
        }
        return $res;
    }
}

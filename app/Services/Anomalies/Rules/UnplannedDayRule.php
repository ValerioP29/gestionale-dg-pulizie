<?php

namespace App\Services\Anomalies\Rules;

use App\Models\DgWorkSession;
use App\Services\Anomalies\Support\ContractDayDTO;

class UnplannedDayRule implements AnomalyRule
{
    public function evaluate(DgWorkSession $s, ?ContractDayDTO $c): array
    {
        return (!$c && (int)$s->worked_minutes > 0)
            ? [['type'=>'unplanned_day','minutes'=>(int)$s->worked_minutes,'note'=>null]]
            : [];
    }
}

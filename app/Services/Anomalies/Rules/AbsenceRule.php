<?php

namespace App\Services\Anomalies\Rules;

use App\Models\DgWorkSession;
use App\Services\Anomalies\Support\ContractDayDTO;

class AbsenceRule implements AnomalyRule
{
    public function evaluate(DgWorkSession $s, ?ContractDayDTO $c): array
    {
        if (!$c) return [];
        $noWork = is_null($s->check_in) && is_null($s->check_out) && (int)$s->worked_minutes === 0;
        return $noWork ? [['type'=>'absence','minutes'=>0,'note'=>null]] : [];
    }
}

<?php

namespace App\Services\Anomalies\Rules;

use App\Models\DgWorkSession;
use App\Services\Anomalies\Support\ContractDayDTO;

class MissingPunchRule implements AnomalyRule
{
    public function evaluate(DgWorkSession $s, ?ContractDayDTO $c): array
    {
        $inMissing = is_null($s->check_in);
        $outMissing = is_null($s->check_out);
        return ($inMissing xor $outMissing)
            ? [['type'=>'missing_punch','minutes'=>0,'note'=>$inMissing?'Manca entrata':'Manca uscita']]
            : [];
    }
}

<?php

namespace App\Services\Anomalies\Rules;

use App\Models\DgWorkSession;
use App\Services\Anomalies\Support\ContractDayDTO;

interface AnomalyRule
{
    /** @return array<int, array{type:string, minutes:int, note:?string}> */
    public function evaluate(DgWorkSession $session, ?ContractDayDTO $contract): array;
}

<?php

namespace App\Services\Anomalies\Support;

class ContractDayDTO
{
    public function __construct(
        public readonly int $weekday,             // 0..6 (lun=0)
        public readonly string $expectedStart,    // '08:00:00'
        public readonly string $expectedEnd,      // '16:00:00'
        public readonly int $breakMinutes,        // 0..n
        public readonly ?int $expectedMinutes = 0 // minuti attesi dal contratto utente
    ) {}
}

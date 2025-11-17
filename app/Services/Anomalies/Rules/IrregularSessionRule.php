<?php

namespace App\Services\Anomalies\Rules;

use App\Models\DgWorkSession;
use App\Services\Anomalies\Support\ContractDayDTO;
use Illuminate\Support\Collection;

class IrregularSessionRule implements AnomalyRule
{
    public function evaluate(DgWorkSession $s, ?ContractDayDTO $c): array
    {
        $threshold = (int) config('anomalies.max_unpaid_break_minutes', 0);

        if ($threshold <= 0) {
            return [];
        }

        /** @var Collection<int, \App\Models\DgPunch> $punches */
        $punches = $s->relationLoaded('punches')
            ? $s->punches
            : $s->punches()->orderBy('created_at')->get();

        if ($punches->count() < 3) {
            return [];
        }

        $maxGap = 0;
        $lastOut = null;

        foreach ($punches as $punch) {
            $instant = $punch->punchInstant();

            if ($punch->type === 'check_out') {
                $lastOut = $instant;
                continue;
            }

            if ($punch->type === 'check_in' && $lastOut) {
                $gap = max(0, $lastOut->diffInMinutes($instant));
                $maxGap = max($maxGap, $gap);
            }
        }

        if ($maxGap <= $threshold) {
            return [];
        }

        return [[
            'type' => 'irregular_session',
            'minutes' => $maxGap,
            'note' => sprintf('Interruzione straordinaria di %d minuti', $maxGap),
        ]];
    }
}

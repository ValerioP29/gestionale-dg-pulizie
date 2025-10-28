<?php

namespace App\Services\Anomalies;

use App\Models\{DgAnomaly, DgWorkSession, DgContractSchedule, User};
use App\Services\Anomalies\Rules\{
    AnomalyRule,
    MissingPunchRule,
    AbsenceRule,
    UnplannedDayRule,
    LateEarlyRule,
    OvertimeRule
};
use App\Services\Anomalies\Support\ContractDayDTO;
use Illuminate\Support\Facades\DB;
use Carbon\CarbonImmutable;

class AnomalyEngine
{
    /** @var array<int, AnomalyRule> */
    protected array $rules;

    public function __construct()
    {
        $this->rules = [
            new MissingPunchRule(),
            new AbsenceRule(),
            new UnplannedDayRule(),
            new LateEarlyRule(),
            new OvertimeRule(),
        ];
    }

    public function evaluateSession(DgWorkSession $session): void
    {
        $date = CarbonImmutable::parse($session->session_date);
        // Carbon ISO: 1=Mon .. 7=Sun  → 0..6 mappa nostra
        $weekday = ($date->dayOfWeekIso - 1);

        $contract = $this->loadContractFor(
            $session->user_id,
            $session->resolved_site_id ?? $session->site_id,
            $weekday
        );

        $payloads = [];

        foreach ($this->rules as $rule) {
            $result = $rule->evaluate($session, $contract);
            if (!empty($result)) {
                $payloads = array_merge($payloads, $result);
            }
        }

        // dedup per type, tieni minutes più alto
        $byType = [];
        foreach ($payloads as $f) {
            $t = $f['type'];
            if (!isset($byType[$t]) || ($f['minutes'] ?? 0) > ($byType[$t]['minutes'] ?? 0)) {
                $byType[$t] = $f;
            }
        }

        $flags = array_values($byType);

        DB::transaction(function () use ($session, $flags, $date) {
            // idempotenza — solo anomalie ricalcolabili
            DgAnomaly::where('user_id', $session->user_id)
                ->whereDate('date', $date->toDateString())
                ->whereIn('type', [
                    'missing_punch','absence','overtime','unplanned_day','late_entry','early_exit'
                ])
                ->delete();

            foreach ($flags as $a) {
                DgAnomaly::create([
                    'user_id'    => $session->user_id,
                    'session_id' => $session->id,
                    'date'       => $date->toDateString(),
                    'type'       => $a['type'],
                    'minutes'    => (int)($a['minutes'] ?? 0),
                    'status'     => 'open',
                    'note'       => $a['note'] ?? null,
                ]);
            }

            // snapshot JSON per UI
            $session->anomaly_flags = $flags;
            $session->saveQuietly();
        });
    }

    /**
     * Carica la regola contrattuale per quel dipendente e quel weekday
     */
    private function loadContractFor(int $userId, ?int $siteId, int $weekday): ?ContractDayDTO
    {
        // weekday → chiavi JSON eloquenti
        $map = ['mon','tue','wed','thu','fri','sat','sun'];
        $dayKey = $map[$weekday] ?? 'mon';

        /** @var User|null $user */
        $user = User::query()->select(['id','contract_schedule_id'])->find($userId);
        if (!$user || !$user->contract_schedule_id) {
            return null; // Nessun contratto → se lavora quel giorno: UNPLANNED_DAY
        }

        /** @var DgContractSchedule|null $schedule */
        $schedule = DgContractSchedule::query()->select(['id','rules'])->find($user->contract_schedule_id);
        if (!$schedule || empty($schedule->rules) || empty($schedule->rules[$dayKey])) {
            return null;
        }

        $rule = $schedule->rules[$dayKey];

        if ($rule === null) {
            // Giorno non previsto dal contratto
            return null;
        }

        // Normalizza
        $start = ($rule['start'] ?? '08:00') . ':00';
        $end   = ($rule['end']   ?? '16:00') . ':00';
        $break = (int)($rule['break'] ?? 0);

        return new ContractDayDTO(
            weekday: $weekday,
            expectedStart: $start,
            expectedEnd: $end,
            breakMinutes: $break
        );
    }
}

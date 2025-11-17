<?php

namespace App\Services\Anomalies;

use App\Models\{DgAnomaly, DgWorkSession, DgContractSchedule, User};
use App\Services\Anomalies\Rules\{
    AbsenceRule,
    AnomalyRule,
    IrregularSessionRule,
    LateEarlyRule,
    MissingPunchRule,
    OvertimeRule,
    UnplannedDayRule
};
use App\Services\Anomalies\Support\ContractDayDTO;
use App\Services\Justifications\JustificationCalendar;
use App\Support\ReportsCacheRegenerator;
use Illuminate\Support\Facades\DB;
use Carbon\CarbonImmutable;

class AnomalyEngine
{
    /** @var array<int, AnomalyRule> */
    protected array $rules;

    protected JustificationCalendar $calendar;

    /** @var string[] */
    private array $calculatedTypes = [
        'missing_punch',
        'absence',
        'overtime',
        'unplanned_day',
        'late_entry',
        'early_exit',
        'irregular_session',
    ];

    /** @var string[] */
    private array $managedStatuses = ['approved', 'rejected', 'justified', 'managed'];

    public function __construct(?JustificationCalendar $calendar = null)
    {
        $this->calendar = $calendar ?? new JustificationCalendar();

        $this->rules = [
            new MissingPunchRule(),
            new AbsenceRule($this->calendar),
            new UnplannedDayRule(),
            new LateEarlyRule(),
            new OvertimeRule(),
            new IrregularSessionRule(),
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

        $previousFlags = $session->anomaly_flags ?? [];

        DB::transaction(function () use ($session, $flags, $date) {
            $existing = DgAnomaly::where('user_id', $session->user_id)
                ->whereDate('date', $date->toDateString())
                ->whereIn('type', $this->calculatedTypes)
                ->get()
                ->keyBy(fn (DgAnomaly $anomaly) => $this->anomalyKey($anomaly->session_id, $anomaly->type, $date->toDateString()));

            $desiredKeys = [];

            foreach ($flags as $flag) {
                $key = $this->anomalyKey($session->id, $flag['type'], $date->toDateString());

                if (! $existing->has($key)) {
                    $fallbackKey = $this->anomalyKey(null, $flag['type'], $date->toDateString());
                    if ($existing->has($fallbackKey)) {
                        $key = $fallbackKey;
                    }
                }

                $desiredKeys[$key] = true;

                if (! $existing->has($key)) {
                    DgAnomaly::create([
                        'user_id'    => $session->user_id,
                        'session_id' => $session->id,
                        'date'       => $date->toDateString(),
                        'type'       => $flag['type'],
                        'minutes'    => (int) ($flag['minutes'] ?? 0),
                        'status'     => 'open',
                        'note'       => $flag['note'] ?? null,
                    ]);
                    continue;
                }

                /** @var DgAnomaly $anomaly */
                $anomaly = $existing->get($key);

                if (in_array($anomaly->status, $this->managedStatuses, true)) {
                    continue;
                }

                $anomaly->fill([
                    'session_id' => $session->id,
                    'minutes'    => (int) ($flag['minutes'] ?? 0),
                ]);

                if (array_key_exists('note', $flag) && $flag['note'] !== $anomaly->note) {
                    $anomaly->note = $flag['note'];
                }

                if ($anomaly->isDirty()) {
                    $anomaly->save();
                }
            }

            foreach ($existing as $key => $anomaly) {
                if (isset($desiredKeys[$key]) || in_array($anomaly->status, $this->managedStatuses, true)) {
                    continue;
                }

                $anomaly->delete();
            }

            // snapshot JSON per UI, evitando di ri-triggerare observer
            $session->anomaly_flags = $flags;
            $session->saveQuietly();
        });

        if (json_encode($previousFlags) !== json_encode($flags)) {
            ReportsCacheRegenerator::dispatchForSessionDate($session->session_date);
        }
    }

    private function anomalyKey(?int $sessionId, string $type, string $date): string
    {
        return implode('|', [
            $sessionId ?? 'none',
            $date,
            $type,
        ]);
    }

    /**
     * Carica la regola contrattuale per quel dipendente e quel weekday
     */
    private function loadContractFor(int $userId, ?int $siteId, int $weekday): ?ContractDayDTO
    {
        $map = ['mon','tue','wed','thu','fri','sat','sun'];
        $dayKey = $map[$weekday] ?? 'mon';

        $user = User::select(['id','contract_schedule_id'])->find($userId);
        if (!$user || !$user->contract_schedule_id) return null;

        $schedule = DgContractSchedule::find($user->contract_schedule_id);
        if (!$schedule) return null;

        // 1) Se esiste il JSON rules
        if (!empty($schedule->rules[$dayKey])) {
            $r = $schedule->rules[$dayKey];
            $start = ($r['start'] ?? '08:00').':00';
            $end   = ($r['end']   ?? '12:00').':00';
            $break = (int)($r['break'] ?? 0);

            return new ContractDayDTO(
                weekday: $weekday,
                expectedStart: $start,
                expectedEnd: $end,
                breakMinutes: $break
            );
        }

        // 2) Fallback alle colonne fisiche (mon/tue/wed…)
        $hours = $schedule->{$dayKey} ?? 0;
        if ($hours <= 0) return null;

        $start = '08:00:00';
        $end   = (CarbonImmutable::parse($start)->addHours($hours))->format('H:i:s');

        return new ContractDayDTO(
            weekday: $weekday,
            expectedStart: $start,
            expectedEnd: $end,
            breakMinutes: 0
        );
    }

}

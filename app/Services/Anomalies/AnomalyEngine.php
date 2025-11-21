<?php

namespace App\Services\Anomalies;

use App\Models\{DgAnomaly, DgPunch, DgWorkSession, User};
use App\Services\Anomalies\Rules\{
    AbsenceRule,
    AnomalyRule,
    IrregularSessionRule,
    LateEarlyRule,
    MissingPunchRule,
    OvertimeRule,
    UnderworkRule,
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
        'underwork',
    ];

    /** @var string[] */
    private array $managedStatuses = ['approved', 'rejected', 'justified', 'managed'];

    /** @var array<int, CarbonImmutable> */
    private array $firstPunchCache = [];

    /** @var array<int, CarbonImmutable> */
    private array $firstSessionCache = [];

    /** @var array<int, CarbonImmutable> */
    private array $employmentStartCache = [];

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
            new UnderworkRule(),
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
            $weekday,
            $date
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
    private function loadContractFor(int $userId, ?int $siteId, int $weekday, CarbonImmutable $date): ?ContractDayDTO
    {
        $map = ['mon','tue','wed','thu','fri','sat','sun'];
        $dayKey = $map[$weekday] ?? 'mon';

        $user = User::with('contractSchedule:id,break_minutes,rules')
            ->select(['id','contract_schedule_id','mon','tue','wed','thu','fri','sat','sun','rules'])
            ->find($userId);

        if (! $user) {
            return null;
        }

        $startBoundary = $this->employmentStartFor($user, $date);
        $endBoundary = $this->employmentEndFor($user);

        if ($date->startOfDay()->lt($startBoundary) || ($endBoundary && $date->greaterThan($endBoundary))) {
            return null;
        }

        $dayHours = (float) ($user->{$dayKey} ?? 0);

        if ($dayHours <= 0) {
            return null;
        }

        $ruleSet = $user->rules ?? [];
        $schedule = $user->contractSchedule;

        if (empty($ruleSet[$dayKey]) && $schedule && ! empty($schedule->rules[$dayKey])) {
            $ruleSet[$dayKey] = $schedule->rules[$dayKey];
        }

        $rule = $ruleSet[$dayKey] ?? [];

        $start = ($rule['start'] ?? '08:00') . ':00';
        $end = ($rule['end'] ?? null);

        if ($end) {
            $end = trim($end);
            $end = str_contains($end, ':') ? $end : $end . ':00';
        } else {
            $end = CarbonImmutable::parse($start)->addHours($dayHours)->format('H:i:s');
        }

        $breakMinutes = (int) ($rule['break'] ?? $rule['break_minutes'] ?? $schedule?->break_minutes ?? 0);

        return new ContractDayDTO(
            weekday: $weekday,
            expectedStart: $start,
            expectedEnd: $end,
            breakMinutes: $breakMinutes,
            expectedMinutes: (int) round($dayHours * 60)
        );
    }


    private function employmentStartFor(User $user, CarbonImmutable $sessionDate): CarbonImmutable
    {
        if (! array_key_exists($user->id, $this->employmentStartCache)) {
            $start = $user->hired_at?->toImmutable();

            if (! $start) {
                $start = $this->firstPunchDate($user->id)
                    ?? $this->firstWorkedSessionDate($user->id);
            }

            $this->employmentStartCache[$user->id] = ($start ?? $sessionDate)->startOfDay();
        }

        return $this->employmentStartCache[$user->id];
    }

    private function employmentEndFor(User $user): ?CarbonImmutable
    {
        return $user->contract_end_at
            ? CarbonImmutable::parse($user->contract_end_at)->endOfDay()
            : null;
    }

    private function firstPunchDate(int $userId): ?CarbonImmutable
    {
        if (! array_key_exists($userId, $this->firstPunchCache)) {
            $timestamp = DgPunch::where('user_id', $userId)->min('created_at');
            $this->firstPunchCache[$userId] = $timestamp
                ? CarbonImmutable::parse($timestamp)->startOfDay()
                : null;
        }

        return $this->firstPunchCache[$userId];
    }

    private function firstWorkedSessionDate(int $userId): ?CarbonImmutable
    {
        if (! array_key_exists($userId, $this->firstSessionCache)) {
            $sessionDate = DgWorkSession::query()
                ->where('user_id', $userId)
                ->where(function ($query) {
                    $query->whereNotNull('check_in')
                        ->orWhereNotNull('check_out')
                        ->orWhere('worked_minutes', '>', 0);
                })
                ->whereNotNull('session_date')
                ->orderBy('session_date')
                ->value('session_date');

            $this->firstSessionCache[$userId] = $sessionDate
                ? CarbonImmutable::parse($sessionDate)->startOfDay()
                : null;
        }

        return $this->firstSessionCache[$userId];
    }
}

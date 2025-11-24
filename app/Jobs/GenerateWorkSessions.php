<?php

namespace App\Jobs;

use App\Models\DgPunch;
use App\Models\DgWorkSession;
use App\Models\User;
use App\Services\SiteResolverService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class GenerateWorkSessions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected ?string $date;

    public function __construct(?string $date = null)
    {
        $this->date = $date;
    }

    public function handle(): void
    {
        $startedAt = microtime(true);
        $targetDate = $this->date
            ? Carbon::parse($this->date)->startOfDay()
            : now()->subDay()->startOfDay();

        Log::info('GenerateWorkSessions: avvio', [
            'target_date' => $targetDate->toDateString(),
            'source' => $this->date ? 'manual' : 'auto',
        ]);

        // Prendo tutti i punch del giorno usando l'istante reale registrato nel payload
        $punches = DgPunch::query()
            ->with('user')
            ->where(function ($query) use ($targetDate) {
                $query->whereDate('payload->punched_at', $targetDate->toDateString())
                    ->orWhereDate('payload->client_ts', $targetDate->toDateString())
                    ->orWhereDate('created_at', $targetDate->toDateString());
            })
            ->orderBy('created_at')
            ->get();

        $byUser = $punches->groupBy('user_id');

        foreach ($byUser as $userId => $rows) {
            $user = $rows->first()->user ?? User::find($userId);
            if (!$user) {
                continue;
            }

            $byDate = $rows->groupBy(fn (DgPunch $p) => $p->punchInstant()->toDateString());

            foreach ($byDate as $sessionDate => $datedPunches) {
                if ($sessionDate !== $targetDate->toDateString()) {
                    continue;
                }

                $bySite = $datedPunches->groupBy(function (DgPunch $p) use ($user) {
                    $instant = $p->punchInstant();

                    $resolved = SiteResolverService::resolveFor(
                        $user,
                        $p->site_id,
                        $instant
                    );

                    return $resolved ?? 'null';
                });

                foreach ($bySite as $siteKey => $records) {
                    $siteId = $siteKey === 'null' ? null : (int) $siteKey;

                    $ordered = $records->sortBy(fn (DgPunch $p) => $p->punchInstant()->getTimestamp())->values();

                    $checkInPunch = $ordered->firstWhere('type', 'check_in');
                    $checkOutPunch = $ordered->reverse()->firstWhere('type', 'check_out');

                    $session = DgWorkSession::firstOrNew([
                        'user_id'      => $userId,
                        'session_date' => $sessionDate,
                        'site_id'      => $siteId,
                    ]);

                    if (!$session->exists) {
                        $session->fill([
                            'status'         => 'incomplete',
                            'worked_minutes' => 0,
                            'source'         => 'auto',
                        ]);
                    }

                    $session->check_in  = $checkInPunch?->punchInstant();
                    $session->check_out = $checkOutPunch?->punchInstant();

                    $session->save();
                }
            }
        }

        $this->ensureAbsenceSessions($targetDate);

        Log::info('GenerateWorkSessions: completato', [
            'target_date' => $targetDate->toDateString(),
            'punches_processed' => $punches->count(),
            'duration_ms' => round((microtime(true) - $startedAt) * 1000, 2),
        ]);
    }

    protected function ensureAbsenceSessions(Carbon $targetDate): void
    {
        $weekdayKey = ['mon','tue','wed','thu','fri','sat','sun'][$targetDate->dayOfWeekIso - 1];

        $users = User::employees()
            ->where('active', true)
            ->with('contractSchedule')
            ->get();

        foreach ($users as $user) {
            if (!$this->isWorkingDay($user, $weekdayKey)) {
                continue;
            }

            $employmentStart = $user->hired_at?->startOfDay();

            if (! $employmentStart) {
                $firstPunch = $user->punches()->min('created_at');
                $employmentStart = $firstPunch ? Carbon::parse($firstPunch)->startOfDay() : null;
            }

            $employmentEnd = $user->contract_end_at?->endOfDay();

            if (! $employmentStart) {
                continue;
            }

            if ($employmentStart && $targetDate->lt($employmentStart)) {
                continue;
            }

            if ($employmentEnd && $targetDate->gt($employmentEnd)) {
                continue;
            }

            $siteId = SiteResolverService::resolveFor($user, null, $targetDate->copy());

            $exists = DgWorkSession::where('user_id', $user->id)
                ->whereDate('session_date', $targetDate->toDateString())
                ->where(function ($q) use ($siteId) {
                    if (is_null($siteId)) {
                        $q->whereNull('site_id');
                    } else {
                        $q->where('site_id', $siteId);
                    }
                })
                ->exists();

            if ($exists) {
                continue;
            }

            DgWorkSession::create([
                'user_id'        => $user->id,
                'site_id'        => $siteId,
                'session_date'   => $targetDate->toDateString(),
                'status'         => 'invalid',
                'worked_minutes' => 0,
                'source'         => 'auto',
            ]);
        }
    }

    protected function isWorkingDay(User $user, string $dayKey): bool
    {
        $userRules = $user->rules ?? [];
        if (isset($userRules[$dayKey])) {
            $rule = $userRules[$dayKey];
            if (is_array($rule)) {
                if (($rule['enabled'] ?? true) === false) {
                    return false;
                }

                if (!empty($rule['start']) && !empty($rule['end'])) {
                    return true;
                }
                if (isset($rule['hours']) && (float) $rule['hours'] > 0) {
                    return true;
                }
            } elseif ((float) $rule > 0) {
                return true;
            }
        }

        return (float) ($user->{$dayKey} ?? 0) > 0;
    }
}

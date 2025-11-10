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
        $targetDate = $this->date
            ? Carbon::parse($this->date)->startOfDay()
            : now()->subDay()->startOfDay();

        // Prendo tutti i punch del giorno (usando created_at come fallback)
        $punches = DgPunch::query()
            ->with('user')
            ->whereDate('created_at', $targetDate->toDateString()) // se usi payload punched_at lato DB, cambia qui
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

        if ((float) ($user->{$dayKey} ?? 0) > 0) {
            return true;
        }

        $schedule = $user->contractSchedule;
        if ($schedule) {
            $scheduleRules = $schedule->rules ?? [];
            if (isset($scheduleRules[$dayKey])) {
                $rule = $scheduleRules[$dayKey];
                if (is_array($rule)) {
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

            if ((float) ($schedule->{$dayKey} ?? 0) > 0) {
                return true;
            }
        }

        return false;
    }
}

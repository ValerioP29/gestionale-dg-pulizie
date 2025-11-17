<?php

namespace App\Services\Justifications;

use App\Models\DgUserJustification;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class JustificationCalendar
{
    /** @var array<int, \Illuminate\Support\Collection<int, DgUserJustification>> */
    private array $cache = [];

    public function coverageMinutesFor(int $userId, CarbonImmutable $date): int
    {
        $entries = $this->loadForUser($userId);

        if ($entries->isEmpty()) {
            return 0;
        }

        return $entries
            ->filter(fn (DgUserJustification $justification) => $justification->coversDate($date))
            ->map(fn (DgUserJustification $justification) => $justification->coveredMinutesFor($date))
            ->max() ?? 0;
    }

    public function hasCoverageFor(int $userId, CarbonImmutable $date, int $requiredMinutes): bool
    {
        $entries = $this->loadForUser($userId);

        if ($entries->isEmpty()) {
            return false;
        }

        return $entries->contains(function (DgUserJustification $justification) use ($date, $requiredMinutes) {
            if (! $justification->coversDate($date)) {
                return false;
            }

            if ($justification->covers_full_day) {
                return true;
            }

            return $justification->coveredMinutesFor($date) >= $requiredMinutes;
        });
    }

    public function hasFullDayCoverage(int $userId, CarbonImmutable $date): bool
    {
        $entries = $this->loadForUser($userId);

        if ($entries->isEmpty()) {
            return false;
        }

        return $entries->contains(function (DgUserJustification $justification) use ($date) {
            return $justification->covers_full_day && $justification->coversDate($date);
        });
    }

    private function loadForUser(int $userId): Collection
    {
        if (! array_key_exists($userId, $this->cache)) {
            $this->cache[$userId] = DgUserJustification::query()
                ->approved()
                ->where('user_id', $userId)
                ->orderBy('date')
                ->get();
        }

        return $this->cache[$userId];
    }
}

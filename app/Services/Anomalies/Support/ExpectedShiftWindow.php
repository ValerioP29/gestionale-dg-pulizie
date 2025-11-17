<?php

namespace App\Services\Anomalies\Support;

use App\Models\DgWorkSession;
use Carbon\CarbonImmutable;

class ExpectedShiftWindow
{
    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public static function resolve(DgWorkSession $session, ContractDayDTO $contract): array
    {
        $anchor = self::anchorDate($session);

        $start = self::combineDateAndTime($anchor, $contract->expectedStart);
        $end = self::combineDateAndTime($anchor, $contract->expectedEnd);

        if ($end->lessThanOrEqualTo($start)) {
            $end = $end->addDay();
        }

        return [$start, $end];
    }

    public static function expectedMinutes(DgWorkSession $session, ContractDayDTO $contract): int
    {
        [$start, $end] = self::resolve($session, $contract);
        $minutes = $end->diffInMinutes($start) - $contract->breakMinutes;

        return max(0, $minutes);
    }

    private static function anchorDate(DgWorkSession $session): CarbonImmutable
    {
        if ($session->session_date) {
            return CarbonImmutable::parse($session->session_date)->startOfDay();
        }

        if ($session->check_in) {
            return CarbonImmutable::parse($session->check_in)->startOfDay();
        }

        if ($session->check_out) {
            return CarbonImmutable::parse($session->check_out)->startOfDay();
        }

        return CarbonImmutable::now()->startOfDay();
    }

    private static function combineDateAndTime(CarbonImmutable $anchor, string $time): CarbonImmutable
    {
        $normalized = self::normalizeTime($time);
        $dateTime = sprintf('%s %s', $anchor->toDateString(), $normalized);

        return CarbonImmutable::parse($dateTime, $anchor->getTimezone());
    }

    private static function normalizeTime(string $time): string
    {
        $time = trim($time);

        if ($time === '') {
            return '00:00:00';
        }

        if (preg_match('/^\d{2}:\d{2}$/', $time)) {
            return $time . ':00';
        }

        if (preg_match('/^\d{2}$/', $time)) {
            return $time . ':00:00';
        }

        return $time;
    }
}

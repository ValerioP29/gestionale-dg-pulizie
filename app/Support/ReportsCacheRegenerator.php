<?php

namespace App\Support;

use App\Jobs\GenerateReportsCache;
use Illuminate\Support\Carbon;

class ReportsCacheRegenerator
{
    /** @var array<string, bool> */
    private static array $scheduled = [];

    public static function dispatchForSessionDate($date): void
    {
        $carbon = $date instanceof Carbon ? $date->copy() : Carbon::parse($date);
        $key = $carbon->format('Y-m');

        if (isset(self::$scheduled[$key])) {
            return;
        }

        self::$scheduled[$key] = true;

        GenerateReportsCache::dispatch(
            $carbon->copy()->startOfMonth()->toDateString(),
            $carbon->copy()->endOfMonth()->toDateString()
        );
    }
}

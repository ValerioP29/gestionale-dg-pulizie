<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class ReportsCacheStatus
{
    public const CACHE_KEY = 'reports_cache:running';
    private const TTL_MINUTES = 5;

    public static function isRunning(): bool
    {
        return Cache::has(self::CACHE_KEY);
    }

    public static function markPending(array $context = []): void
    {
        Cache::put(self::CACHE_KEY, self::payload($context), now()->addMinutes(self::TTL_MINUTES));
    }

    public static function refresh(array $context = []): void
    {
        $current = Cache::get(self::CACHE_KEY, []);

        Cache::put(
            self::CACHE_KEY,
            self::payload($context, is_array($current) ? $current : []),
            now()->addMinutes(self::TTL_MINUTES)
        );
    }

    public static function clear(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private static function payload(array $context, array $base = []): array
    {
        $base = array_merge(['started_at' => $base['started_at'] ?? now()], $base, $context);
        $base['updated_at'] = now();

        return $base;
    }
}

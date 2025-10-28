<?php

namespace App\Observers;

use Spatie\Activitylog\Models\Activity;

class ActivityLogObserver
{
    public function creating(Activity $activity): void
    {
        try {
            $ip  = request()?->ip();
            $ua  = request()?->userAgent();
        } catch (\Throwable $e) {
            $ip = null; $ua = null;
        }

        $ua = $ua ? mb_substr($ua, 0, 255) : null;

        $props = collect($activity->properties ?? [])->merge([
            'ip_address' => $ip,
            'user_agent' => $ua,
        ]);

        $activity->properties = $props;
    }
}

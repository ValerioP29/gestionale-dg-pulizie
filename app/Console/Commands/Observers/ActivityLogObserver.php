<?php

namespace App\Observers;

use Spatie\Activitylog\Models\Activity;

class ActivityLogObserver
{
    public function creating(Activity $activity): void
    {
        $activity->properties = collect($activity->properties)
            ->merge([
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
    }
}

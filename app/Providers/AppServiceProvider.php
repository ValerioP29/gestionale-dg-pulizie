<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Assets\Js;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;

use App\Models\DgPunch;
use App\Models\DgUserJustification;
use App\Models\DgWorkSession;
use App\Models\DgReportCache;
use Spatie\Activitylog\Models\Activity;

use App\Observers\DgPunchObserver;
use App\Observers\DgUserJustificationObserver;
use App\Observers\DgWorkSessionObserver;
use App\Observers\ActivityLogObserver;
use App\Policies\DgReportCachePolicy;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Observers
        DgPunch::observe(DgPunchObserver::class);
        DgWorkSession::observe(DgWorkSessionObserver::class);
        DgUserJustification::observe(DgUserJustificationObserver::class);
        Activity::observe(ActivityLogObserver::class);

        // Filament assets via Vite, solo se manifest presente
        $manifest = public_path('build/manifest.json');
        if (File::exists($manifest)) {
            FilamentAsset::register([
                Js::make('address-autocomplete', asset('build/assets/address-autocomplete.js')),
            ]);
        }
        // Altrimenti, in dev puoi servirti direttamente di Vite dev server,
        // ma evitare qui per non rompere in CLI/queue.

        Gate::policy(DgReportCache::class, DgReportCachePolicy::class);
    }
}

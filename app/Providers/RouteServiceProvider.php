<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        // Rate limits
        RateLimiter::for('login', function (Request $request) {
            $key = sprintf('%s|%s', $request->ip(), (string) $request->input('email'));
            return Limit::perMinute(10)->by($key);
        });

        RateLimiter::for('payslip-downloads', function (Request $request) {
            $identifier = $request->user()?->id ?? $request->ip();
            return Limit::perMinute(20)->by($identifier);
        });

        // QUI REGISTRIAMO LE ROUTE
        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}

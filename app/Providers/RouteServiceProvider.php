<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        // Limite dedicato per login: 10 tentativi/min per coppia IP+email
        RateLimiter::for('login', function (Request $request) {
            $key = sprintf('%s|%s', $request->ip(), (string) $request->input('email'));
            return Limit::perMinute(10)->by($key);
        });
    }
}

<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use BezhanSalleh\FilamentLanguageSwitch\LanguageSwitch;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Assets\Js;
use Illuminate\Support\Facades\Vite; // 👈 aggiungi questa importazione

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        LanguageSwitch::configureUsing(function (LanguageSwitch $switch) {
            $switch
                ->locales(['it', 'en'])
                ->visible(insidePanels: true)
                ->renderHook('panels::global-search.after');
        });

        // ✅ Usa il facade ufficiale Vite di Laravel
        FilamentAsset::register([
            Js::make('address-autocomplete', Vite::asset('resources/js/filament/address-autocomplete.js')),
        ]);
    }
}

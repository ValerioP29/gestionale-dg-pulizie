<?php

return [

    'name' => env('APP_NAME', 'Gestionale DG Pulizie'),

    'env' => env('APP_ENV', 'production'),

    'debug' => (bool) env('APP_DEBUG', false),

    'url' => env('APP_URL', 'http://localhost'),

    'timezone' => 'Europe/Rome',

    /*
    |--------------------------------------------------------------------------
    | Localizzazione
    |--------------------------------------------------------------------------
    |
    | Queste impostazioni controllano la lingua predefinita dell'app e la
    | lingua di fallback nel caso in cui una traduzione non sia disponibile.
    |
    */
    'locale' => env('APP_LOCALE', 'it'),
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),
    'faker_locale' => env('APP_FAKER_LOCALE', 'it_IT'),

    /*
    |--------------------------------------------------------------------------
    | Chiave di cifratura
    |--------------------------------------------------------------------------
    */
    'key' => env('APP_KEY'),
    'cipher' => 'AES-256-CBC',

    'previous_keys' => [
        ...array_filter(explode(',', (string) env('APP_PREVIOUS_KEYS', ''))),
    ],

    /*
    |--------------------------------------------------------------------------
    | Manutenzione
    |--------------------------------------------------------------------------
    */
    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store'  => env('APP_MAINTENANCE_STORE', 'database'),
    ],
];

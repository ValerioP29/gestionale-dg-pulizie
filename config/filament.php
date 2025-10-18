<?php

return [
    'path' => 'admin', // URL base /admin
    'domain' => null,

    'auth' => [
        'guard' => 'web', // usa autenticazione Laravel standard
    ],

    'brand' => [
        'name' => env('APP_NAME', 'Gestionale DG Pulizie'),
    ],

    // lingua predefinita di Filament
    'locale' => 'it',
];

<?php

return [
    'path' => 'admin',           // /admin
    'domain' => null,

    'auth' => [
        'guard' => 'web',        // semplice per ora
    ],

    'brand' => [
        'name' => env('APP_NAME', 'Gestionale'),
    ],
];

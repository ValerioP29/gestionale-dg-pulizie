<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost',
        'http://127.0.0.1:5174',
        'http://127.0.0.1:5173',
        'http://localhost:5174',
        'http://localhost:5173',
        'https://app.dgpulizie.it',
        'https://panel.dgpulizie.it',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];

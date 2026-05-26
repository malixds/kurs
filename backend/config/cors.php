<?php

return [
    'paths' => [
        'api' => ['api/*'],
        'sanctum_csrf_cookie' => 'sanctum/csrf-cookie',
    ],

    'allowed_origins' => array_filter(array_map(
        'trim',
        explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:8080')),
    )),

    'allowed_origins_patterns' => [
        '#^chrome-extension://.*$#',
    ],

    'allowed_headers' => ['*'],

    'allowed_methods' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];

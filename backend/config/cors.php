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

    // Only explicitly whitelisted extension IDs may use CORS. The extension
    // itself talks to the API via host_permissions and does not need CORS;
    // a wildcard here would let ANY installed extension ride dashboard
    // session cookies (supports_credentials is enabled below).
    'allowed_origins_patterns' => array_map(
        fn (string $id): string => sprintf('#^chrome-extension://%s$#', preg_quote($id, '#')),
        array_filter(array_map(
            'trim',
            explode(',', (string) env('CORS_ALLOWED_EXTENSION_IDS', '')),
        )),
    ),

    'allowed_headers' => ['*'],

    'allowed_methods' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];

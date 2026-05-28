<?php

/**
 * OAuth-подключения интеграций (фаза E — после MVP с ручными токенами).
 *
 * Маршруты dashboard.integrations.oauth.* будут добавлены при внедрении OAuth.
 */
return [
    'enabled' => false,
    'providers' => [
        'jira' => [
            'client_id' => env('JIRA_OAUTH_CLIENT_ID'),
            'client_secret' => env('JIRA_OAUTH_CLIENT_SECRET'),
            'redirect_uri' => env('APP_URL').'/dashboard/integrations/jira/oauth/callback',
        ],
        'github' => [
            'client_id' => env('GITHUB_OAUTH_CLIENT_ID'),
            'client_secret' => env('GITHUB_OAUTH_CLIENT_SECRET'),
            'redirect_uri' => env('APP_URL').'/dashboard/integrations/github/oauth/callback',
        ],
    ],
];

<?php

return [
    'max_issues_per_sync' => (int) env('INTEGRATION_MAX_ISSUES', 500),

    'providers' => [
        'yandex_tracker' => [
            'name' => 'Яндекс Трекер',
            'auth_type' => 'token',
            'config_schema' => [
                ['key' => 'oauth_token', 'label' => 'OAuth-токен', 'type' => 'password', 'required' => true],
                ['key' => 'org_id', 'label' => 'ID организации', 'type' => 'text', 'required' => true],
                ['key' => 'org_header', 'label' => 'Тип org (x_org или x_cloud)', 'type' => 'select', 'options' => ['x_org' => 'X-Org-ID (Яндекс 360)', 'x_cloud' => 'X-Cloud-Org-ID (Yandex Cloud)'], 'required' => true],
                ['key' => 'queue_keys', 'label' => 'Очереди (через запятую, опционально)', 'type' => 'text', 'required' => false],
            ],
        ],
        'jira' => [
            'name' => 'Jira Cloud',
            'auth_type' => 'basic',
            'config_schema' => [
                ['key' => 'site_url', 'label' => 'URL сайта (https://your.atlassian.net)', 'type' => 'text', 'required' => true],
                ['key' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true],
                ['key' => 'api_token', 'label' => 'API Token', 'type' => 'password', 'required' => true],
                ['key' => 'project_keys', 'label' => 'Ключи проектов (через запятую, опционально)', 'type' => 'text', 'required' => false],
            ],
        ],
        'linear' => [
            'name' => 'Linear',
            'auth_type' => 'token',
            'config_schema' => [
                ['key' => 'api_key', 'label' => 'API Key', 'type' => 'password', 'required' => true],
                ['key' => 'team_ids', 'label' => 'Team IDs (через запятую, опционально)', 'type' => 'text', 'required' => false],
            ],
        ],
        'github' => [
            'name' => 'GitHub Issues',
            'auth_type' => 'token',
            'config_schema' => [
                ['key' => 'token', 'label' => 'Personal Access Token', 'type' => 'password', 'required' => true],
                ['key' => 'owner', 'label' => 'Owner (org or user)', 'type' => 'text', 'required' => true],
                ['key' => 'repo', 'label' => 'Repository', 'type' => 'text', 'required' => true],
            ],
        ],
    ],

    'plan_limits' => [
        'default' => [
            'deep_analysis' => true,
            'deep_llm_per_month' => 50,
            'max_integrations' => 4,
            'allowed_providers' => ['yandex_tracker', 'jira', 'linear', 'github'],
        ],
    ],
];

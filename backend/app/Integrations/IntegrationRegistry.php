<?php

namespace App\Integrations;

use App\Integrations\Connectors\GithubIssuesConnector;
use App\Integrations\Connectors\JiraCloudConnector;
use App\Integrations\Connectors\LinearConnector;
use App\Integrations\Connectors\YandexTrackerConnector;
use App\Integrations\Contracts\WorkTrackerConnector;
use App\Integrations\Enums\IntegrationProvider;
use InvalidArgumentException;

class IntegrationRegistry
{
    /** @var array<string, class-string<WorkTrackerConnector>> */
    private const MAP = [
        'yandex_tracker' => YandexTrackerConnector::class,
        'jira' => JiraCloudConnector::class,
        'linear' => LinearConnector::class,
        'github' => GithubIssuesConnector::class,
    ];

    public function resolve(string $slug): WorkTrackerConnector
    {
        $class = self::MAP[$slug] ?? null;

        if ($class === null) {
            throw new InvalidArgumentException("Unknown integration provider: {$slug}");
        }

        return app($class);
    }

    public function resolveFromProvider(IntegrationProvider $provider): WorkTrackerConnector
    {
        return $this->resolve($provider->value);
    }

    /** @return array<string, class-string<WorkTrackerConnector>> */
    public function all(): array
    {
        return self::MAP;
    }
}

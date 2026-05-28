<?php

namespace App\Integrations\Contracts;

use App\Integrations\DTO\WorkProgressCollection;
use App\Integrations\DTO\WorkProgressRequest;
use App\Integrations\Enums\IntegrationProvider;

interface WorkTrackerConnector
{
    public function provider(): IntegrationProvider;

    public function testConnection(array $credentials, array $settings = []): bool;

    public function fetchWorkProgress(WorkProgressRequest $request): WorkProgressCollection;
}

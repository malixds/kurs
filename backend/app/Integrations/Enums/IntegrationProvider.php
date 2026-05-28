<?php

namespace App\Integrations\Enums;

enum IntegrationProvider: string
{
    case YandexTracker = 'yandex_tracker';
    case Jira = 'jira';
    case Linear = 'linear';
    case Github = 'github';

    public function label(): string
    {
        return config("integrations.providers.{$this->value}.name", $this->value);
    }
}

<?php

namespace App\Services\Billing;

class PlanLimitService
{
    public function can(int $companyId, string $ability): bool
    {
        $limits = config('integrations.plan_limits.default', []);

        return match ($ability) {
            'deep_analysis' => (bool) ($limits['deep_analysis'] ?? true),
            default => str_starts_with($ability, 'integration:')
                ? $this->canUseProvider($companyId, str_replace('integration:', '', $ability), $limits)
                : true,
        };
    }

    public function maxIntegrations(int $companyId): int
    {
        return (int) config('integrations.plan_limits.default.max_integrations', 4);
    }

    public function allowedProviders(int $companyId): array
    {
        return config('integrations.plan_limits.default.allowed_providers', []);
    }

    private function canUseProvider(int $companyId, string $slug, array $limits): bool
    {
        $allowed = $limits['allowed_providers'] ?? [];

        return $allowed === [] || in_array($slug, $allowed, true);
    }
}

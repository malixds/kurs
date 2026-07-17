<?php

namespace App\Integrations;

use App\Integrations\DTO\WorkProgressRequest;
use App\Integrations\Enums\IntegrationStatus;
use App\Integrations\Support\IntegrationEmployeeMatcher;
use App\Models\CompanyIntegration;
use App\Models\Employee;
use App\Models\EmployeeIntegrationIdentity;
use App\Models\IntegrationProvider as IntegrationProviderModel;
use App\Models\WorkProgressSnapshot;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use RuntimeException;

class IntegrationManager
{
    public function __construct(
        private readonly IntegrationRegistry $registry,
        private readonly IntegrationEmployeeMatcher $employeeMatcher,
    ) {}

    public function providersForUi(): array
    {
        $config = config('integrations.providers', []);

        return collect($config)->map(function (array $meta, string $slug) {
            return [
                'slug' => $slug,
                'name' => $meta['name'] ?? $slug,
                'auth_type' => $meta['auth_type'] ?? 'token',
                'config_schema' => $meta['config_schema'] ?? [],
            ];
        })->values()->all();
    }

    public function getOrCreateIntegration(int $companyId, string $providerSlug): CompanyIntegration
    {
        return CompanyIntegration::query()->firstOrCreate(
            ['company_id' => $companyId, 'provider_slug' => $providerSlug],
            ['status' => IntegrationStatus::Disconnected],
        );
    }

    public function saveCredentials(int $companyId, string $providerSlug, array $credentials, array $settings = []): CompanyIntegration
    {
        $integration = $this->getOrCreateIntegration($companyId, $providerSlug);
        $integration->credentials = $credentials;
        $integration->settings = $settings;
        $integration->status = IntegrationStatus::Connected;
        $integration->last_error = null;
        $integration->save();

        return $integration;
    }

    public function disconnect(int $companyId, string $providerSlug): void
    {
        $integration = $this->getOrCreateIntegration($companyId, $providerSlug);
        $integration->credentials = null;
        $integration->settings = null;
        $integration->status = IntegrationStatus::Disconnected;
        $integration->last_error = null;
        $integration->save();
    }

    public function testConnection(int $companyId, string $providerSlug, ?array $credentials = null): bool
    {
        $integration = $this->getOrCreateIntegration($companyId, $providerSlug);
        $creds = $credentials ?? $integration->credentials ?? [];

        if ($creds === []) {
            throw new RuntimeException('Укажите данные для подключения.');
        }

        $connector = $this->registry->resolve($providerSlug);
        $settings = $integration->settings ?? [];

        return $connector->testConnection($creds, $settings);
    }

    public function connectedIntegrations(int $companyId): Collection
    {
        return CompanyIntegration::query()
            ->where('company_id', $companyId)
            ->where('status', IntegrationStatus::Connected)
            ->get();
    }

    public function syncWorkProgress(
        int $companyId,
        CarbonInterface $from,
        CarbonInterface $to,
        array $providerSlugs,
    ): array {
        $results = [];
        $errors = [];

        foreach ($providerSlugs as $slug) {
            $integration = CompanyIntegration::query()
                ->where('company_id', $companyId)
                ->where('provider_slug', $slug)
                ->where('status', IntegrationStatus::Connected)
                ->first();

            if ($integration === null || empty($integration->credentials)) {
                $errors[] = "Интеграция «{$slug}» не подключена.";

                continue;
            }

            try {
                $connector = $this->registry->resolve($slug);
                $request = new WorkProgressRequest(
                    companyId: $companyId,
                    from: $from,
                    to: $to,
                    credentials: $integration->credentials,
                    settings: $integration->settings ?? [],
                );

                $collection = $connector->fetchWorkProgress($request);
                $payload = $collection->toArray();

                WorkProgressSnapshot::query()->updateOrCreate(
                    [
                        'company_id' => $companyId,
                        'provider_slug' => $slug,
                        'period_from' => $from->toDateString(),
                        'period_to' => $to->toDateString(),
                    ],
                    [
                        'payload' => $payload,
                        'fetched_at' => now(),
                    ],
                );

                $this->syncEmployeeIdentities($integration, $collection->employees);

                $integration->last_sync_at = now();
                $integration->last_error = null;
                $integration->save();

                $results[$slug] = $payload;
            } catch (\Throwable $e) {
                $integration->status = IntegrationStatus::Error;
                $integration->last_error = $e->getMessage();
                $integration->save();
                $errors[] = "{$slug}: {$e->getMessage()}";
            }
        }

        return ['results' => $results, 'errors' => $errors];
    }

    public function getSnapshots(int $companyId, CarbonInterface $from, CarbonInterface $to, array $providerSlugs): array
    {
        return WorkProgressSnapshot::query()
            ->where('company_id', $companyId)
            ->where('period_from', $from->toDateString())
            ->where('period_to', $to->toDateString())
            ->when($providerSlugs !== [], fn ($q) => $q->whereIn('provider_slug', $providerSlugs))
            ->get()
            ->keyBy('provider_slug')
            ->map(fn (WorkProgressSnapshot $s) => $s->payload)
            ->all();
    }

    private function syncEmployeeIdentities(CompanyIntegration $integration, array $employeeDtos): void
    {
        $companyId = $integration->company_id;
        $employees = Employee::query()->where('company_id', $companyId)->get();

        foreach ($employeeDtos as $dto) {
            $employee = $this->employeeMatcher->find(
                $employees,
                $dto->externalEmail,
                $dto->displayName,
                $dto->assigneeKey,
            );

            if ($employee === null) {
                continue;
            }

            EmployeeIntegrationIdentity::query()->updateOrCreate(
                [
                    'company_integration_id' => $integration->id,
                    'employee_id' => $employee->id,
                ],
                [
                    'external_user_id' => $dto->assigneeKey,
                    'external_login' => $dto->assigneeKey,
                    'external_email' => $dto->externalEmail,
                ],
            );
        }
    }

    public function seedProviders(): void
    {
        foreach (config('integrations.providers', []) as $slug => $meta) {
            IntegrationProviderModel::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $meta['name'],
                    'auth_type' => $meta['auth_type'] ?? 'token',
                    'config_schema' => $meta['config_schema'] ?? [],
                    'is_active' => true,
                ],
            );
        }
    }
}

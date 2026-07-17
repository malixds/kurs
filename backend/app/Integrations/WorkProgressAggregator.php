<?php

namespace App\Integrations;

use App\Integrations\Support\IntegrationEmployeeMatcher;
use App\Integrations\Support\ProviderMetricsMerger;
use App\Models\CompanyIntegration;
use App\Models\Employee;
use App\Models\EmployeeIntegrationIdentity;
use App\Models\WorkProgressSnapshot;

class WorkProgressAggregator
{
    public function __construct(
        private readonly IntegrationEmployeeMatcher $employeeMatcher,
        private readonly ProviderMetricsMerger $metricsMerger = new ProviderMetricsMerger,
    ) {}

    /**
     * @param  array<string, array>  $snapshotsByProvider  slug => payload from snapshot
     */
    public function mergeForCompany(int $companyId, array $snapshotsByProvider): array
    {
        $employees = Employee::query()->where('company_id', $companyId)->get()->keyBy('id');
        $warnings = [];
        $sources = array_keys($snapshotsByProvider);
        $teamClosed = 0;
        $teamOverdue = 0;

        $byEmployeeId = [];
        $unmappedAssignees = [];

        foreach ($snapshotsByProvider as $providerSlug => $payload) {
            foreach ($payload['warnings'] ?? [] as $warning) {
                $warnings[] = "[{$providerSlug}] {$warning}";
            }

            $teamClosed += $payload['team_summary']['tasks_closed'] ?? 0;
            $teamOverdue += $payload['team_summary']['overdue'] ?? 0;

            foreach ($payload['employees'] ?? [] as $row) {
                $employeeId = $this->resolveEmployeeId($companyId, $providerSlug, $row, $employees);

                if ($employeeId === null) {
                    $warnings[] = "Нет маппинга для {$row['display_name']} ({$providerSlug}).";
                    $unmappedAssignees[] = [
                        'provider' => $providerSlug,
                        'display_name' => $row['display_name'] ?? null,
                        'external_email' => $row['external_email'] ?? null,
                        'tasks_closed' => $row['tasks_closed'] ?? 0,
                        'tasks_created' => $row['tasks_created'] ?? 0,
                        'overdue_count' => $row['overdue_count'] ?? 0,
                        'tasks_open_at_period_end' => $row['tasks_open_at_period_end'] ?? 0,
                        'overdue_issues' => $row['overdue_issues'] ?? [],
                        'open_issues' => $row['open_issues'] ?? [],
                        'closed_issues' => $row['closed_issues'] ?? [],
                        'by_status' => $row['by_status'] ?? [],
                    ];

                    continue;
                }

                if (! isset($byEmployeeId[$employeeId])) {
                    $employee = $employees->get($employeeId);
                    $byEmployeeId[$employeeId] = [
                        'employee_id' => $employeeId,
                        'name' => $employee?->name,
                        'email' => $employee?->email,
                        'wellbeing_linked' => true,
                        'providers' => [],
                    ];
                }

                $incoming = [
                    'tasks_closed' => $row['tasks_closed'] ?? 0,
                    'tasks_created' => $row['tasks_created'] ?? 0,
                    'tasks_updated' => $row['tasks_updated'] ?? 0,
                    'tasks_open_at_period_end' => $row['tasks_open_at_period_end'] ?? 0,
                    'overdue_count' => $row['overdue_count'] ?? 0,
                    'avg_resolution_days' => $row['avg_resolution_days'] ?? null,
                    'by_status' => $row['by_status'] ?? [],
                    'sample_issues' => $row['sample_issues'] ?? [],
                    'closed_issues' => $row['closed_issues'] ?? [],
                    'open_issues' => $row['open_issues'] ?? [],
                    'overdue_issues' => $row['overdue_issues'] ?? [],
                ];

                $existing = $byEmployeeId[$employeeId]['providers'][$providerSlug] ?? null;
                $byEmployeeId[$employeeId]['providers'][$providerSlug] = $existing !== null
                    ? $this->metricsMerger->merge($existing, $incoming)
                    : $incoming;
            }
        }

        return [
            'sources' => $sources,
            'team_summary' => [
                'tasks_closed' => $teamClosed,
                'overdue' => $teamOverdue,
            ],
            'employees' => array_values($byEmployeeId),
            'unmapped_assignees' => $unmappedAssignees,
            'warnings' => array_values(array_unique($warnings)),
        ];
    }

    public function loadSnapshots(int $companyId, string $from, string $to, array $providerSlugs): array
    {
        $query = WorkProgressSnapshot::query()
            ->where('company_id', $companyId)
            ->where('period_from', $from)
            ->where('period_to', $to);

        if ($providerSlugs !== []) {
            $query->whereIn('provider_slug', $providerSlugs);
        }

        return $query->get()->mapWithKeys(fn (WorkProgressSnapshot $s) => [$s->provider_slug => $s->payload])->all();
    }

    private function resolveEmployeeId(int $companyId, string $providerSlug, array $row, $employees): ?int
    {
        $integration = CompanyIntegration::query()
            ->where('company_id', $companyId)
            ->where('provider_slug', $providerSlug)
            ->first();

        if ($integration) {
            $identity = EmployeeIntegrationIdentity::query()
                ->where('company_integration_id', $integration->id)
                ->where(function ($q) use ($row) {
                    $q->where('external_login', $row['assignee_key'] ?? '')
                        ->orWhere('external_user_id', $row['assignee_key'] ?? '');
                })
                ->first();

            if ($identity) {
                return $identity->employee_id;
            }
        }

        $match = $this->employeeMatcher->find(
            $employees,
            $row['external_email'] ?? null,
            $row['display_name'] ?? null,
            $row['assignee_key'] ?? null,
        );

        return $match?->id;
    }
}

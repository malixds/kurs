<?php

namespace App\Integrations;

use App\Models\Employee;
use App\Models\EmployeeIntegrationIdentity;
use App\Models\WorkProgressSnapshot;

class WorkProgressAggregator
{
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

                $byEmployeeId[$employeeId]['providers'][$providerSlug] = [
                    'tasks_closed' => $row['tasks_closed'] ?? 0,
                    'tasks_created' => $row['tasks_created'] ?? 0,
                    'tasks_updated' => $row['tasks_updated'] ?? 0,
                    'tasks_open_at_period_end' => $row['tasks_open_at_period_end'] ?? 0,
                    'overdue_count' => $row['overdue_count'] ?? 0,
                    'by_status' => $row['by_status'] ?? [],
                    'sample_issues' => $row['sample_issues'] ?? [],
                ];
            }
        }

        return [
            'sources' => $sources,
            'team_summary' => [
                'tasks_closed' => $teamClosed,
                'overdue' => $teamOverdue,
            ],
            'employees' => array_values($byEmployeeId),
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
        $integration = \App\Models\CompanyIntegration::query()
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

        if (! empty($row['external_email'])) {
            $match = $employees->first(fn (Employee $e) => strcasecmp($e->email ?? '', $row['external_email']) === 0);

            return $match?->id;
        }

        return null;
    }
}

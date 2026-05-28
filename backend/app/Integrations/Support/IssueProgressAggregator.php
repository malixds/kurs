<?php

namespace App\Integrations\Support;

use App\Integrations\DTO\EmployeeWorkProgressDto;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class IssueProgressAggregator
{
    /** @var array<string, array<string, mixed>> */
    private array $byAssignee = [];

    public function addIssue(
        ?string $assigneeKey,
        string $displayName,
        ?string $email,
        string $issueKey,
        ?string $status,
        ?CarbonInterface $createdAt,
        ?CarbonInterface $updatedAt,
        ?CarbonInterface $resolvedAt,
        ?CarbonInterface $dueAt,
        CarbonInterface $periodFrom,
        CarbonInterface $periodTo,
    ): void {
        $key = $assigneeKey ?? strtolower($displayName);
        $key = $key !== '' ? $key : 'unassigned';

        if (! isset($this->byAssignee[$key])) {
            $this->byAssignee[$key] = [
                'display_name' => $displayName,
                'email' => $email,
                'tasks_closed' => 0,
                'tasks_created' => 0,
                'tasks_updated' => 0,
                'tasks_open' => 0,
                'overdue' => 0,
                'by_status' => [],
                'sample_issues' => [],
            ];
        }

        $row = &$this->byAssignee[$key];
        $statusKey = $status ?? 'unknown';
        $row['by_status'][$statusKey] = ($row['by_status'][$statusKey] ?? 0) + 1;

        if (count($row['sample_issues']) < 5) {
            $row['sample_issues'][] = $issueKey;
        }

        if ($createdAt && $createdAt->between($periodFrom, $periodTo)) {
            $row['tasks_created']++;
        }

        if ($updatedAt && $updatedAt->between($periodFrom, $periodTo)) {
            $row['tasks_updated']++;
        }

        if ($resolvedAt && $resolvedAt->between($periodFrom, $periodTo)) {
            $row['tasks_closed']++;
        } elseif (! $resolvedAt && $updatedAt && $updatedAt->lte($periodTo)) {
            $row['tasks_open']++;
        }

        if ($dueAt && $dueAt->lt(now()) && ! $resolvedAt) {
            $row['overdue']++;
        }
    }

    /** @return array<int, EmployeeWorkProgressDto> */
    public function toDtos(): array
    {
        return collect($this->byAssignee)->map(function (array $row, string $key) {
            return new EmployeeWorkProgressDto(
                assigneeKey: $key,
                displayName: $row['display_name'],
                externalEmail: $row['email'],
                tasksClosed: $row['tasks_closed'],
                tasksCreated: $row['tasks_created'],
                tasksUpdated: $row['tasks_updated'],
                tasksOpenAtPeriodEnd: $row['tasks_open'],
                overdueCount: $row['overdue'],
                byStatus: $row['by_status'],
                sampleIssues: $row['sample_issues'],
            );
        })->values()->all();
    }
}

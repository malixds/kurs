<?php

namespace App\Integrations\Support;

use App\Integrations\DTO\EmployeeWorkProgressDto;
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
                'closed_issues' => [],
                'open_issues' => [],
                'overdue_issues' => [],
                'resolution_days_sum' => 0,
                'resolution_count' => 0,
            ];
        }

        $row = &$this->byAssignee[$key];
        $statusKey = $status ?? 'unknown';
        $row['by_status'][$statusKey] = ($row['by_status'][$statusKey] ?? 0) + 1;

        if (count($row['sample_issues']) < 8) {
            $row['sample_issues'][] = $issueKey;
        }

        if ($createdAt && $createdAt->between($periodFrom, $periodTo)) {
            $row['tasks_created']++;
        }

        if ($updatedAt && $updatedAt->between($periodFrom, $periodTo)) {
            $row['tasks_updated']++;
        }

        $isResolvedInPeriod = $resolvedAt && $resolvedAt->between($periodFrom, $periodTo);

        if ($isResolvedInPeriod) {
            $row['tasks_closed']++;
            $this->pushIssueKey($row, 'closed_issues', $issueKey, 15);

            if ($createdAt) {
                $row['resolution_days_sum'] += max(0, $createdAt->diffInDays($resolvedAt));
                $row['resolution_count']++;
            }
        } elseif (! $resolvedAt && $updatedAt && $updatedAt->lte($periodTo)) {
            $row['tasks_open']++;
            $this->pushIssueKey($row, 'open_issues', $issueKey, 15);
        }

        if ($dueAt && $dueAt->lt(now()) && ! $resolvedAt) {
            $row['overdue']++;
            $this->pushIssueKey($row, 'overdue_issues', $issueKey, 20);
        }
    }

    /** @return array<int, EmployeeWorkProgressDto> */
    public function toDtos(): array
    {
        return collect($this->byAssignee)->map(function (array $row, string $key) {
            $avgResolution = null;
            if (($row['resolution_count'] ?? 0) > 0) {
                $avgResolution = round($row['resolution_days_sum'] / $row['resolution_count'], 1);
            }

            return new EmployeeWorkProgressDto(
                assigneeKey: $key,
                displayName: $row['display_name'],
                externalEmail: $row['email'],
                tasksClosed: $row['tasks_closed'],
                tasksCreated: $row['tasks_created'],
                tasksUpdated: $row['tasks_updated'],
                tasksOpenAtPeriodEnd: $row['tasks_open'],
                overdueCount: $row['overdue'],
                avgDaysInProgress: $avgResolution,
                byStatus: $row['by_status'],
                sampleIssues: $row['sample_issues'],
                closedIssues: $row['closed_issues'] ?? [],
                openIssues: $row['open_issues'] ?? [],
                overdueIssues: $row['overdue_issues'] ?? [],
            );
        })->values()->all();
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function pushIssueKey(array &$row, string $field, string $issueKey, int $max): void
    {
        if (! in_array($issueKey, $row[$field], true) && count($row[$field]) < $max) {
            $row[$field][] = $issueKey;
        }
    }
}

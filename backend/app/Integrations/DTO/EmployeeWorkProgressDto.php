<?php

namespace App\Integrations\DTO;

readonly class EmployeeWorkProgressDto
{
    public function __construct(
        public string $assigneeKey,
        public string $displayName,
        public ?string $externalEmail = null,
        public int $tasksClosed = 0,
        public int $tasksCreated = 0,
        public int $tasksUpdated = 0,
        public int $tasksOpenAtPeriodEnd = 0,
        public int $overdueCount = 0,
        public ?float $avgDaysInProgress = null,
        public array $byStatus = [],
        public array $sampleIssues = [],
        public array $closedIssues = [],
        public array $openIssues = [],
        public array $overdueIssues = [],
    ) {}

    public function toArray(): array
    {
        return [
            'assignee_key' => $this->assigneeKey,
            'display_name' => $this->displayName,
            'external_email' => $this->externalEmail,
            'tasks_closed' => $this->tasksClosed,
            'tasks_created' => $this->tasksCreated,
            'tasks_updated' => $this->tasksUpdated,
            'tasks_open_at_period_end' => $this->tasksOpenAtPeriodEnd,
            'overdue_count' => $this->overdueCount,
            'avg_resolution_days' => $this->avgDaysInProgress,
            'by_status' => $this->byStatus,
            'sample_issues' => $this->sampleIssues,
            'closed_issues' => $this->closedIssues,
            'open_issues' => $this->openIssues,
            'overdue_issues' => $this->overdueIssues,
        ];
    }
}

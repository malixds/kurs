<?php

namespace App\Integrations\DTO;

use App\Integrations\Enums\IntegrationProvider;

readonly class WorkProgressCollection
{
    /** @param  array<int, EmployeeWorkProgressDto>  $employees */
    public function __construct(
        public IntegrationProvider $provider,
        public array $employees,
        public array $warnings = [],
        public int $issuesFetched = 0,
        public ?string $jql = null,
    ) {}

    public function toArray(): array
    {
        $data = [
            'provider' => $this->provider->value,
            'team_summary' => $this->teamSummary(),
            'employees' => array_map(fn (EmployeeWorkProgressDto $e) => $e->toArray(), $this->employees),
            'warnings' => $this->warnings,
        ];

        if ($this->jql !== null) {
            $data['meta'] = ['jql' => $this->jql];
        }

        return $data;
    }

    public function teamSummary(): array
    {
        $closed = 0;
        $overdue = 0;
        $open = 0;

        foreach ($this->employees as $employee) {
            $closed += $employee->tasksClosed;
            $overdue += $employee->overdueCount;
            $open += $employee->tasksOpenAtPeriodEnd;
        }

        return [
            'tasks_closed' => $closed,
            'overdue' => $overdue,
            'tasks_open' => $open,
            'contributors' => count($this->employees),
            'issues_fetched' => $this->issuesFetched,
        ];
    }
}

<?php

namespace App\Services\Analysis;

/**
 * Урезанный JSON для LLM: только агрегаты и ключи просроченных задач.
 * Полный экспорт для UI — {@see DeepAnalysisExportService::build()}.
 */
class DeepAnalysisLlmPayloadBuilder
{
    public function fromExport(array $export): array
    {
        $workProgress = $export['work_progress'] ?? [];

        return [
            'company' => $export['company'] ?? null,
            'period' => $export['period'] ?? null,
            'integration_warnings' => $export['integration_warnings'] ?? [],
            'wellbeing_summary' => $export['wellbeing']['summary'] ?? null,
            'team_summary' => $workProgress['team_summary'] ?? null,
            'sources' => $workProgress['sources'] ?? [],
            'employee_delivery_matrix' => $this->trimMatrix($export['employee_delivery_matrix'] ?? []),
            'llm_instructions' => $export['llm_instructions'] ?? null,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $matrix
     * @return list<array<string, mixed>>
     */
    private function trimMatrix(array $matrix): array
    {
        return array_map(fn (array $row) => [
            'name' => $row['name'] ?? 'Unknown',
            'department' => $row['department'] ?? null,
            'wellbeing' => $row['wellbeing'] ?? null,
            'tasks' => $row['tasks'] !== null ? $this->trimTasks($row['tasks']) : null,
        ], $matrix);
    }

    /**
     * @param  array<string, mixed>  $tasks
     * @return array<string, mixed>
     */
    private function trimTasks(array $tasks): array
    {
        $maxOverdue = (int) config('llm.deep_analysis.max_overdue_issue_keys', 10);

        $trimmed = [
            'provider' => $tasks['provider'] ?? null,
            'tasks_closed' => $tasks['tasks_closed'] ?? 0,
            'tasks_created' => $tasks['tasks_created'] ?? 0,
            'tasks_updated' => $tasks['tasks_updated'] ?? 0,
            'overdue_count' => $tasks['overdue_count'] ?? 0,
            'tasks_open' => $tasks['tasks_open'] ?? 0,
            'avg_resolution_days' => $tasks['avg_resolution_days'] ?? null,
            'overdue_issues' => array_slice($tasks['overdue_issues'] ?? [], 0, $maxOverdue),
            'mapping_note' => $tasks['mapping_note'] ?? null,
        ];

        if (isset($tasks['providers']) && is_array($tasks['providers'])) {
            $trimmed['providers'] = [];
            foreach ($tasks['providers'] as $slug => $metrics) {
                if (is_array($metrics)) {
                    $trimmed['providers'][$slug] = $this->trimTasks($metrics);
                }
            }
        }

        if (($trimmed['overdue_count'] ?? 0) > count($trimmed['overdue_issues'])) {
            $trimmed['overdue_issues_truncated'] = true;
        }

        return array_filter($trimmed, fn ($value) => $value !== null && $value !== []);
    }
}

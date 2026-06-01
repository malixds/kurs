<?php

namespace App\Services\Analysis;

class DeepAnalysisEmployeeMatrixBuilder
{
    /**
     * Сводная таблица «человек × wellbeing × задачи» для LLM.
     *
     * @param  array<string, mixed>  $wellbeing
     * @param  array<string, mixed>  $workProgress
     * @return list<array<string, mixed>>
     */
    public function build(array $wellbeing, array $workProgress): array
    {
        $wellbeingById = collect($wellbeing['employees'] ?? [])->keyBy('employee_id');
        $workById = collect($workProgress['employees'] ?? [])->keyBy('employee_id');

        $ids = $wellbeingById->keys()->merge($workById->keys())->unique()->sort()->values();

        $matrix = [];
        foreach ($ids as $employeeId) {
            $wb = $wellbeingById->get($employeeId);
            $wp = $workById->get($employeeId);

            $matrix[] = [
                'employee_id' => (int) $employeeId,
                'name' => $wp['name'] ?? $wb['name'] ?? 'Unknown',
                'email' => $wp['email'] ?? $wb['email'] ?? null,
                'department' => $wb['department'] ?? null,
                'wellbeing' => $wb !== null ? $this->summarizeWellbeing($wb) : null,
                'tasks' => $wp !== null ? $this->summarizeTasks($wp) : null,
            ];
        }

        foreach ($workProgress['unmapped_assignees'] ?? [] as $row) {
            $matrix[] = [
                'employee_id' => null,
                'name' => $row['display_name'] ?? 'Без маппинга',
                'email' => $row['external_email'] ?? null,
                'department' => null,
                'wellbeing' => null,
                'tasks' => [
                    'provider' => $row['provider'] ?? 'tracker',
                    'tasks_closed' => $row['tasks_closed'] ?? 0,
                    'tasks_created' => $row['tasks_created'] ?? 0,
                    'overdue_count' => $row['overdue_count'] ?? 0,
                    'tasks_open' => $row['tasks_open_at_period_end'] ?? 0,
                    'overdue_issues' => $row['overdue_issues'] ?? [],
                    'open_issues' => $row['open_issues'] ?? [],
                    'closed_issues' => $row['closed_issues'] ?? [],
                    'by_status' => $row['by_status'] ?? [],
                    'mapping_note' => 'Нет связи с сотрудником в системе — wellbeing недоступен.',
                ],
            ];
        }

        return $matrix;
    }

    /**
     * @param  array<string, mixed>  $employee
     * @return array<string, mixed>
     */
    private function summarizeWellbeing(array $employee): array
    {
        $moods = [];
        $stresses = [];
        $supports = [];

        foreach ($employee['check_ins'] ?? [] as $checkIn) {
            foreach ($checkIn['answers'] ?? [] as $answer) {
                $question = mb_strtolower((string) ($answer['question'] ?? ''));
                $score = $answer['score'] ?? null;

                if (str_contains($question, 'mood') || str_contains($question, 'настроен')) {
                    if ($score !== null) {
                        $moods[] = (float) $score;
                    }
                } elseif (str_contains($question, 'stress') || str_contains($question, 'стресс')) {
                    if ($score !== null) {
                        $stresses[] = (float) $score;
                    }
                } elseif (($answer['type'] ?? '') === 'boolean' || str_contains($question, 'support') || str_contains($question, 'поддерж')) {
                    $supports[] = ($answer['answer'] ?? '') === 'yes';
                }
            }
        }

        return [
            'check_in_days' => count($employee['check_ins'] ?? []),
            'avg_mood' => $moods !== [] ? round(array_sum($moods) / count($moods), 2) : null,
            'avg_stress' => $stresses !== [] ? round(array_sum($stresses) / count($stresses), 2) : null,
            'team_support_yes_rate' => $supports !== []
                ? round(100 * count(array_filter($supports)) / count($supports), 0)
                : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $employee
     * @return array<string, mixed>
     */
    private function summarizeTasks(array $employee): array
    {
        $providers = $employee['providers'] ?? [];
        $primary = $providers[array_key_first($providers)] ?? [];

        if (count($providers) === 1) {
            return $this->flattenProviderMetrics($primary, array_key_first($providers));
        }

        $merged = [
            'tasks_closed' => 0,
            'tasks_created' => 0,
            'tasks_updated' => 0,
            'overdue_count' => 0,
            'tasks_open' => 0,
            'overdue_issues' => [],
            'open_issues' => [],
            'closed_issues' => [],
            'by_status' => [],
            'providers' => [],
        ];

        foreach ($providers as $slug => $metrics) {
            $merged['tasks_closed'] += $metrics['tasks_closed'] ?? 0;
            $merged['tasks_created'] += $metrics['tasks_created'] ?? 0;
            $merged['tasks_updated'] += $metrics['tasks_updated'] ?? 0;
            $merged['overdue_count'] += $metrics['overdue_count'] ?? 0;
            $merged['tasks_open'] += $metrics['tasks_open_at_period_end'] ?? 0;
            $merged['overdue_issues'] = array_merge($merged['overdue_issues'], $metrics['overdue_issues'] ?? []);
            $merged['open_issues'] = array_merge($merged['open_issues'], $metrics['open_issues'] ?? []);
            $merged['closed_issues'] = array_merge($merged['closed_issues'], $metrics['closed_issues'] ?? []);
            $merged['providers'][$slug] = $this->flattenProviderMetrics($metrics, $slug);
        }

        return $merged;
    }

    /**
     * @param  array<string, mixed>  $metrics
     * @return array<string, mixed>
     */
    private function flattenProviderMetrics(array $metrics, ?string $provider): array
    {
        return [
            'provider' => $provider,
            'tasks_closed' => $metrics['tasks_closed'] ?? 0,
            'tasks_created' => $metrics['tasks_created'] ?? 0,
            'tasks_updated' => $metrics['tasks_updated'] ?? 0,
            'overdue_count' => $metrics['overdue_count'] ?? 0,
            'tasks_open' => $metrics['tasks_open_at_period_end'] ?? 0,
            'avg_resolution_days' => $metrics['avg_resolution_days'] ?? null,
            'by_status' => $metrics['by_status'] ?? [],
            'overdue_issues' => $metrics['overdue_issues'] ?? [],
            'open_issues' => $metrics['open_issues'] ?? [],
            'closed_issues' => $metrics['closed_issues'] ?? [],
            'sample_issues' => $metrics['sample_issues'] ?? [],
        ];
    }
}

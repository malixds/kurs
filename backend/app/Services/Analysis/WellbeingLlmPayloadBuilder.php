<?php

namespace App\Services\Analysis;

/**
 * Сжатый JSON wellbeing для LLM: сводка по именам, без сырых check_ins.
 */
class WellbeingLlmPayloadBuilder
{
    public function fromExport(array $export): array
    {
        return [
            'company' => $export['company'] ?? null,
            'period' => $export['period'] ?? null,
            'summary' => $export['summary'] ?? null,
            'employee_wellbeing_summary' => $this->summarizeEmployees($export['employees'] ?? []),
            'llm_instructions' => 'В каждой рекомендации укажи поле name из employee_wellbeing_summary и минимум одну цифру (avg_mood, avg_stress или team_support_yes_rate).',
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $employees
     * @return list<array<string, mixed>>
     */
    private function summarizeEmployees(array $employees): array
    {
        $rows = [];

        foreach ($employees as $employee) {
            $wb = $this->summarizeWellbeing($employee);
            $rows[] = [
                'name' => $employee['name'] ?? 'Unknown',
                'external_id' => $employee['external_id'] ?? null,
                'department' => $employee['department'] ?? null,
                'check_in_days' => $wb['check_in_days'],
                'avg_mood' => $wb['avg_mood'],
                'avg_stress' => $wb['avg_stress'],
                'team_support_yes_rate' => $wb['team_support_yes_rate'],
            ];
        }

        return $rows;
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
}

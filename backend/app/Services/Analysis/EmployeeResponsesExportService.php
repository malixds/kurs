<?php

namespace App\Services\Analysis;

use App\Models\Company;
use App\Models\SurveyAnswer;
use App\Support\AnalysisPeriodResolver;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class EmployeeResponsesExportService
{
    public function exportForCompany(
        int $companyId,
        CarbonInterface $from,
        CarbonInterface $to,
    ): array {
        $company = Company::query()->findOrFail($companyId);

        $answers = SurveyAnswer::query()
            ->with(['employee.department', 'question'])
            ->where('company_id', $companyId)
            ->whereBetween('check_in_date', [
                $from->toDateString(),
                $to->toDateString(),
            ])
            ->orderBy('check_in_date')
            ->orderBy('employee_id')
            ->get();

        $employees = $this->groupByEmployee($answers);

        return [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
            ],
            'period' => AnalysisPeriodResolver::toArray($from, $to),
            'summary' => [
                'employees_with_data' => count($employees),
                'total_answers' => $answers->count(),
                'total_check_in_days' => collect($employees)->sum(
                    fn (array $employee) => count($employee['check_ins']),
                ),
            ],
            'employees' => array_values($employees),
        ];
    }

    private function groupByEmployee(Collection $answers): array
    {
        $grouped = [];

        foreach ($answers->groupBy('employee_id') as $employeeId => $employeeAnswers) {
            $employee = $employeeAnswers->first()->employee;

            $checkIns = [];

            foreach ($employeeAnswers->groupBy(fn ($a) => $a->check_in_date->format('Y-m-d')) as $date => $dayAnswers) {
                $answerRows = $dayAnswers->map(fn (SurveyAnswer $answer) => [
                    'question_id' => $answer->survey_question_id,
                    'question' => $answer->question?->question,
                    'type' => $answer->question?->type?->value,
                    'answer' => $answer->answer,
                    'score' => $answer->score !== null ? (float) $answer->score : null,
                ])->values()->all();

                $scores = collect($answerRows)->pluck('score')->filter(fn ($s) => $s !== null);

                $checkIns[] = [
                    'date' => $date,
                    'average_score' => $scores->isNotEmpty()
                        ? round((float) $scores->avg(), 2)
                        : null,
                    'answers' => $answerRows,
                ];
            }

            $grouped[$employeeId] = [
                'employee_id' => (int) $employeeId,
                'external_id' => $employee?->external_id,
                'name' => $employee?->name ?? 'Unknown',
                'email' => $employee?->email,
                'department' => $employee?->department?->name,
                'check_ins' => $checkIns,
            ];
        }

        return $grouped;
    }
}

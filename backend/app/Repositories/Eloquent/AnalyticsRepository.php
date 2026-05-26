<?php

namespace App\Repositories\Eloquent;

use App\DTOs\Analytics\AnalyticsFilterDto;
use App\Models\SurveyAnswer;
use App\Repositories\Contracts\AnalyticsRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class AnalyticsRepository implements AnalyticsRepositoryInterface
{
    public function averageMoodScore(AnalyticsFilterDto $filter): ?float
    {
        $average = $this->baseQuery($filter)
            ->whereNotNull('score')
            ->avg('score');

        return $average !== null ? round((float) $average, 2) : null;
    }

    public function moodTrendByDate(AnalyticsFilterDto $filter): array
    {
        return $this->baseQuery($filter)
            ->select([
                'check_in_date',
                DB::raw('ROUND(AVG(score)::numeric, 2) as average_score'),
                DB::raw('COUNT(*) as responses'),
            ])
            ->whereNotNull('score')
            ->groupBy('check_in_date')
            ->orderBy('check_in_date')
            ->get()
            ->map(fn ($row) => [
                'date' => $row->check_in_date->format('Y-m-d'),
                'average_score' => $row->average_score !== null ? (float) $row->average_score : null,
                'responses' => (int) $row->responses,
            ])
            ->all();
    }

    public function employeeSummaries(AnalyticsFilterDto $filter): array
    {
        return SurveyAnswer::query()
            ->select([
                'employees.id as employee_id',
                'employees.name',
                DB::raw('ROUND(AVG(survey_answers.score)::numeric, 2) as average_score'),
                DB::raw('COUNT(survey_answers.id) as responses'),
            ])
            ->join('employees', 'employees.id', '=', 'survey_answers.employee_id')
            ->where('survey_answers.company_id', $filter->companyId)
            ->whereBetween('survey_answers.check_in_date', [
                $filter->from->toDateString(),
                $filter->to->toDateString(),
            ])
            ->when($filter->departmentId, fn (Builder $q) => $q->where('employees.department_id', $filter->departmentId))
            ->whereNotNull('survey_answers.score')
            ->groupBy('employees.id', 'employees.name')
            ->orderByDesc('average_score')
            ->get()
            ->map(fn ($row) => [
                'employee_id' => (int) $row->employee_id,
                'name' => $row->name,
                'average_score' => $row->average_score !== null ? (float) $row->average_score : null,
                'responses' => (int) $row->responses,
            ])
            ->all();
    }

    public function departmentOverview(AnalyticsFilterDto $filter): array
    {
        return SurveyAnswer::query()
            ->select([
                'departments.id as department_id',
                'departments.name as department_name',
                DB::raw('ROUND(AVG(survey_answers.score)::numeric, 2) as average_score'),
                DB::raw('COUNT(DISTINCT employees.id) as employees'),
            ])
            ->join('employees', 'employees.id', '=', 'survey_answers.employee_id')
            ->leftJoin('departments', 'departments.id', '=', 'employees.department_id')
            ->where('survey_answers.company_id', $filter->companyId)
            ->whereBetween('survey_answers.check_in_date', [
                $filter->from->toDateString(),
                $filter->to->toDateString(),
            ])
            ->whereNotNull('survey_answers.score')
            ->groupBy('departments.id', 'departments.name')
            ->orderByDesc('average_score')
            ->get()
            ->map(fn ($row) => [
                'department_id' => $row->department_id !== null ? (int) $row->department_id : null,
                'department_name' => $row->department_name ?? 'Unassigned',
                'average_score' => $row->average_score !== null ? (float) $row->average_score : null,
                'employees' => (int) $row->employees,
            ])
            ->all();
    }

    public function employeeHistory(int $employeeId, int $companyId, AnalyticsFilterDto $filter): array
    {
        $answers = SurveyAnswer::query()
            ->with('question')
            ->where('company_id', $companyId)
            ->where('employee_id', $employeeId)
            ->whereBetween('check_in_date', [
                $filter->from->toDateString(),
                $filter->to->toDateString(),
            ])
            ->orderByDesc('check_in_date')
            ->get()
            ->groupBy(fn ($answer) => $answer->check_in_date->format('Y-m-d'));

        return $answers->map(function ($dayAnswers, $date) {
            $scores = $dayAnswers->pluck('score')->filter();

            return [
                'date' => $date,
                'average_score' => $scores->isNotEmpty()
                    ? round((float) $scores->avg(), 2)
                    : null,
                'answers' => $dayAnswers->map(fn ($answer) => [
                    'question' => $answer->question?->question,
                    'answer' => $answer->answer,
                    'score' => $answer->score !== null ? (float) $answer->score : null,
                ])->values()->all(),
            ];
        })->values()->all();
    }

    private function baseQuery(AnalyticsFilterDto $filter): Builder
    {
        return SurveyAnswer::query()
            ->when($filter->employeeId, fn (Builder $q) => $q->where('employee_id', $filter->employeeId))
            ->when($filter->departmentId, function (Builder $q) use ($filter): void {
                $q->whereHas('employee', fn (Builder $employeeQuery) => $employeeQuery
                    ->where('department_id', $filter->departmentId));
            })
            ->where('company_id', $filter->companyId)
            ->whereBetween('check_in_date', [
                $filter->from->toDateString(),
                $filter->to->toDateString(),
            ]);
    }
}

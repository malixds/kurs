<?php

namespace App\Repositories\Contracts;

use App\DTOs\Analytics\AnalyticsFilterDto;

interface AnalyticsRepositoryInterface
{
    public function averageMoodScore(AnalyticsFilterDto $filter): ?float;

    /** @return array<int, array{date: string, average_score: float|null, responses: int}> */
    public function moodTrendByDate(AnalyticsFilterDto $filter): array;

    /** @return array<int, array{employee_id: int, name: string|null, average_score: float|null, responses: int}> */
    public function employeeSummaries(AnalyticsFilterDto $filter): array;

    /** @return array<int, array{department_id: int|null, department_name: string, average_score: float|null, employees: int}> */
    public function departmentOverview(AnalyticsFilterDto $filter): array;

    /** @return array<int, array{date: string, average_score: float|null, answers: array<int, array{question: string, answer: string, score: float|null}>}> */
    public function employeeHistory(int $employeeId, int $companyId, AnalyticsFilterDto $filter): array;
}

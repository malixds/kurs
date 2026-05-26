<?php

namespace App\Services\Analytics;

use App\DTOs\Analytics\AnalyticsFilterDto;
use App\Repositories\Contracts\AnalyticsRepositoryInterface;
use App\Services\Analytics\BurnoutRiskService;
use Illuminate\Support\Facades\Cache;

class AnalyticsService
{
    private const CACHE_TTL_SECONDS = 300;

    public function __construct(
        private readonly AnalyticsRepositoryInterface $analyticsRepository,
        private readonly BurnoutRiskService $burnoutRiskService,
    ) {}

    public function getOverview(AnalyticsFilterDto $filter): array
    {
        $cacheKey = $this->cacheKey('overview', $filter);

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($filter): array {
            return [
                'average_mood_score' => $this->analyticsRepository->averageMoodScore($filter),
                'burnout_risk' => $this->burnoutRiskService->assess(
                    $filter->companyId,
                    $filter->employeeId,
                    $filter->departmentId,
                ),
                'trends' => $this->analyticsRepository->moodTrendByDate($filter),
                'weekly_summary' => $this->buildWeeklySummary($filter),
                'department_overview' => $this->analyticsRepository->departmentOverview($filter),
                'employee_summaries' => $this->analyticsRepository->employeeSummaries($filter),
            ];
        });
    }

    public function getEmployeeHistory(int $employeeId, AnalyticsFilterDto $filter): array
    {
        return [
            'employee_id' => $employeeId,
            'history' => $this->analyticsRepository->employeeHistory(
                $employeeId,
                $filter->companyId,
                $filter,
            ),
            'burnout_risk' => $this->burnoutRiskService->assess(
                $filter->companyId,
                $employeeId,
            ),
        ];
    }

    private function buildWeeklySummary(AnalyticsFilterDto $filter): array
    {
        $trends = $this->analyticsRepository->moodTrendByDate($filter);
        $scores = array_filter(array_column($trends, 'average_score'), fn ($score) => $score !== null);
        $responses = array_sum(array_column($trends, 'responses'));

        return [
            'period_from' => $filter->from->toDateString(),
            'period_to' => $filter->to->toDateString(),
            'average_score' => $scores !== [] ? round(array_sum($scores) / count($scores), 2) : null,
            'total_responses' => $responses,
            'active_days' => count($trends),
        ];
    }

    private function cacheKey(string $prefix, AnalyticsFilterDto $filter): string
    {
        return sprintf(
            'analytics:%s:%d:%s:%s:%s:%s',
            $prefix,
            $filter->companyId,
            $filter->from->toDateString(),
            $filter->to->toDateString(),
            $filter->employeeId ?? 'all',
            $filter->departmentId ?? 'all',
        );
    }
}

<?php

namespace App\Services\Analytics;

use App\DTOs\Analytics\AnalyticsFilterDto;
use App\Enums\BurnoutRiskLevel;
use App\Repositories\Contracts\AnalyticsRepositoryInterface;
use Carbon\Carbon;

class BurnoutRiskService
{
    public function __construct(
        private readonly AnalyticsRepositoryInterface $analyticsRepository,
    ) {}

    public function assess(int $companyId, ?int $employeeId = null, ?int $departmentId = null): array
    {
        $currentFilter = new AnalyticsFilterDto(
            companyId: $companyId,
            from: Carbon::now()->subDays(6)->startOfDay(),
            to: Carbon::now()->endOfDay(),
            employeeId: $employeeId,
            departmentId: $departmentId,
        );

        $previousFilter = new AnalyticsFilterDto(
            companyId: $companyId,
            from: Carbon::now()->subDays(13)->startOfDay(),
            to: Carbon::now()->subDays(7)->endOfDay(),
            employeeId: $employeeId,
            departmentId: $departmentId,
        );

        $currentAverage = $this->analyticsRepository->averageMoodScore($currentFilter);
        $previousAverage = $this->analyticsRepository->averageMoodScore($previousFilter);

        $level = $this->resolveLevel($currentAverage, $previousAverage);

        return [
            'level' => $level->value,
            'label' => $level->label(),
            'current_average' => $currentAverage,
            'previous_average' => $previousAverage,
            'trend' => $this->resolveTrend($currentAverage, $previousAverage),
        ];
    }

    private function resolveLevel(?float $current, ?float $previous): BurnoutRiskLevel
    {
        if ($current === null) {
            return BurnoutRiskLevel::Low;
        }

        if ($current < 2.5) {
            return BurnoutRiskLevel::High;
        }

        if ($current < 3.5 && $this->isDeclining($current, $previous)) {
            return BurnoutRiskLevel::Medium;
        }

        if ($current < 3.0) {
            return BurnoutRiskLevel::Medium;
        }

        return BurnoutRiskLevel::Low;
    }

    private function isDeclining(?float $current, ?float $previous): bool
    {
        if ($current === null || $previous === null) {
            return false;
        }

        return ($previous - $current) >= 0.3;
    }

    private function resolveTrend(?float $current, ?float $previous): string
    {
        if ($current === null || $previous === null) {
            return 'stable';
        }

        $delta = round($current - $previous, 2);

        if ($delta >= 0.2) {
            return 'improving';
        }

        if ($delta <= -0.2) {
            return 'declining';
        }

        return 'stable';
    }
}

<?php

namespace Tests\Unit;

use App\DTOs\Analytics\AnalyticsFilterDto;
use App\Repositories\Contracts\AnalyticsRepositoryInterface;
use App\Services\Analytics\BurnoutRiskService;
use PHPUnit\Framework\TestCase;

class BurnoutRiskServiceTest extends TestCase
{
    /**
     * Repository stub returning the "current week" average first
     * and the "previous week" average on the second call.
     */
    private function serviceReturning(?float $current, ?float $previous): BurnoutRiskService
    {
        $repository = new class($current, $previous) implements AnalyticsRepositoryInterface
        {
            private int $calls = 0;

            public function __construct(
                private readonly ?float $current,
                private readonly ?float $previous,
            ) {}

            public function averageMoodScore(AnalyticsFilterDto $filter): ?float
            {
                return $this->calls++ === 0 ? $this->current : $this->previous;
            }

            public function moodTrendByDate(AnalyticsFilterDto $filter): array
            {
                return [];
            }

            public function employeeSummaries(AnalyticsFilterDto $filter): array
            {
                return [];
            }

            public function departmentOverview(AnalyticsFilterDto $filter): array
            {
                return [];
            }

            public function employeeHistory(int $employeeId, int $companyId, AnalyticsFilterDto $filter): array
            {
                return [];
            }
        };

        return new BurnoutRiskService($repository);
    }

    public function test_low_average_means_high_risk(): void
    {
        $result = $this->serviceReturning(2.0, 2.1)->assess(1);

        $this->assertSame('high', $result['level']);
    }

    public function test_mid_average_means_medium_risk(): void
    {
        $result = $this->serviceReturning(2.8, 2.8)->assess(1);

        $this->assertSame('medium', $result['level']);
    }

    public function test_decline_pushes_borderline_average_to_medium(): void
    {
        $result = $this->serviceReturning(3.2, 3.6)->assess(1);

        $this->assertSame('medium', $result['level']);
        $this->assertSame('declining', $result['trend']);
    }

    public function test_healthy_average_means_low_risk(): void
    {
        $result = $this->serviceReturning(4.5, 4.2)->assess(1);

        $this->assertSame('low', $result['level']);
        $this->assertSame('improving', $result['trend']);
    }

    public function test_no_data_defaults_to_low_risk_and_stable_trend(): void
    {
        $result = $this->serviceReturning(null, null)->assess(1);

        $this->assertSame('low', $result['level']);
        $this->assertSame('stable', $result['trend']);
        $this->assertNull($result['current_average']);
    }
}

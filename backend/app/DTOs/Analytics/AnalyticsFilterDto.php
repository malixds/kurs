<?php

namespace App\DTOs\Analytics;

use Carbon\CarbonInterface;

readonly class AnalyticsFilterDto
{
    public function __construct(
        public int $companyId,
        public CarbonInterface $from,
        public CarbonInterface $to,
        public ?int $employeeId = null,
        public ?int $departmentId = null,
    ) {}
}

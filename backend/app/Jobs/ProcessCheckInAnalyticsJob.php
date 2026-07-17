<?php

namespace App\Jobs;

use App\Services\Analytics\AnalyticsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessCheckInAnalyticsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [10, 60];

    public function __construct(
        public int $companyId,
        public int $employeeId,
    ) {}

    public function handle(AnalyticsService $analyticsService): void
    {
        // A fresh check-in changes every aggregate for the company, so drop
        // the cached analytics instead of waiting out the TTL.
        $analyticsService->invalidateCompanyCache($this->companyId);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Check-in analytics processing failed', [
            'company_id' => $this->companyId,
            'employee_id' => $this->employeeId,
            'exception' => $exception?->getMessage(),
        ]);
    }
}

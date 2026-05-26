<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessCheckInAnalyticsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $companyId,
        public int $employeeId,
    ) {}

    public function handle(): void
    {
        // Analytics cache entries expire via TTL; this job is a hook for
        // future pre-aggregation or notification workflows.
    }
}

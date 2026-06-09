<?php

namespace App\Jobs;

use App\DTOs\Analytics\AnalyticsFilterDto;
use App\Models\Company;
use App\Services\Analytics\AnalyticsService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateWeeklySummaryJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [30, 120];

    public function failed(?\Throwable $exception): void
    {
        Log::error('Weekly summary generation failed', [
            'exception' => $exception?->getMessage(),
        ]);
    }

    public function handle(AnalyticsService $analyticsService): void
    {
        Company::query()->each(function (Company $company) use ($analyticsService): void {
            $filter = new AnalyticsFilterDto(
                companyId: $company->id,
                from: Carbon::now()->subDays(6)->startOfDay(),
                to: Carbon::now()->endOfDay(),
            );

            $summary = $analyticsService->getOverview($filter);

            Log::info('Weekly wellbeing summary generated', [
                'company_id' => $company->id,
                'summary' => $summary['weekly_summary'],
                'burnout_risk' => $summary['burnout_risk']['level'],
            ]);
        });
    }
}

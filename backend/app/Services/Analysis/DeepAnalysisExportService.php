<?php

namespace App\Services\Analysis;

use App\Integrations\WorkProgressAggregator;
use App\Support\AnalysisPeriodResolver;
use Carbon\CarbonInterface;

class DeepAnalysisExportService
{
    public function __construct(
        private readonly EmployeeResponsesExportService $wellbeingExport,
        private readonly WorkProgressAggregator $workProgressAggregator,
    ) {}

    public function build(
        int $companyId,
        CarbonInterface $from,
        CarbonInterface $to,
        array $providerSlugs,
    ): array {
        $wellbeing = $this->wellbeingExport->exportForCompany($companyId, $from, $to);
        $snapshots = $this->workProgressAggregator->loadSnapshots(
            $companyId,
            $from->toDateString(),
            $to->toDateString(),
            $providerSlugs,
        );

        $workProgress = $this->workProgressAggregator->mergeForCompany($companyId, $snapshots);
        $warnings = $workProgress['warnings'] ?? [];

        if ($snapshots === []) {
            $warnings[] = 'Нет данных из трекеров. Выполните синхронизацию на вкладке «Глубокий анализ» или в настройках интеграций.';
        }

        unset($workProgress['warnings']);

        return [
            'company' => $wellbeing['company'],
            'period' => AnalysisPeriodResolver::toArray($from, $to),
            'wellbeing' => $wellbeing,
            'work_progress' => $workProgress,
            'integration_warnings' => array_values(array_unique($warnings)),
        ];
    }
}

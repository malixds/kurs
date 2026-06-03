<?php

namespace App\Services\Analysis;

use App\Integrations\WorkProgressAggregator;
use App\Models\WorkProgressSnapshot;
use App\Support\AnalysisPeriodResolver;
use Carbon\CarbonInterface;

class DeepAnalysisExportService
{
    public function __construct(
        private readonly EmployeeResponsesExportService $wellbeingExport,
        private readonly WorkProgressAggregator $workProgressAggregator,
        private readonly DeepAnalysisEmployeeMatrixBuilder $matrixBuilder,
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

        $employeeMatrix = $this->matrixBuilder->build($wellbeing, $workProgress);

        return [
            'company' => $wellbeing['company'],
            'period' => AnalysisPeriodResolver::toArray($from, $to),
            'employee_delivery_matrix' => $employeeMatrix,
            'wellbeing' => $wellbeing,
            'work_progress' => $workProgress,
            'integration_warnings' => array_values(array_unique($warnings)),
            'llm_instructions' => 'Развёрнутые рекомендации по employee_delivery_matrix: wellbeing + tasks по каждому name, '
                .'оценка риска, шаги со сроками, цифры (закрыто, просрочено, avg_mood, ключи overdue_issues). '
                .'Не выдумывай метрики, которых нет в JSON.',
        ];
    }

    /**
     * Только данные трекеров из сохранённых снимков (без wellbeing, без LLM).
     *
     * @param  list<string>  $providerSlugs
     */
    public function exportTrackers(
        int $companyId,
        CarbonInterface $from,
        CarbonInterface $to,
        array $providerSlugs,
    ): array {
        $fromStr = $from->toDateString();
        $toStr = $to->toDateString();

        $snapshotModels = WorkProgressSnapshot::query()
            ->where('company_id', $companyId)
            ->where('period_from', $fromStr)
            ->where('period_to', $toStr)
            ->when($providerSlugs !== [], fn ($q) => $q->whereIn('provider_slug', $providerSlugs))
            ->get()
            ->keyBy('provider_slug');

        $snapshots = $snapshotModels->map(fn (WorkProgressSnapshot $s) => $s->payload)->all();
        $merged = $this->workProgressAggregator->mergeForCompany($companyId, $snapshots);
        $warnings = $merged['warnings'] ?? [];
        unset($merged['warnings']);

        if ($snapshots === []) {
            $warnings[] = 'Нет снимков за этот период. Сначала нажмите «Синхронизировать трекеры» для выбранных источников.';
        }

        $byProvider = [];
        foreach ($providerSlugs as $slug) {
            $model = $snapshotModels->get($slug);
            $payload = $snapshots[$slug] ?? null;

            $byProvider[$slug] = [
                'connected' => $model !== null,
                'fetched_at' => $model?->fetched_at?->toIso8601String(),
                'team_summary' => $payload['team_summary'] ?? null,
                'contributors' => isset($payload['employees']) ? count($payload['employees']) : 0,
                'raw' => $payload,
            ];
        }

        if ($providerSlugs === []) {
            foreach ($snapshotModels as $slug => $model) {
                $payload = $model->payload;
                $byProvider[$slug] = [
                    'connected' => true,
                    'fetched_at' => $model->fetched_at?->toIso8601String(),
                    'team_summary' => $payload['team_summary'] ?? null,
                    'contributors' => isset($payload['employees']) ? count($payload['employees']) : 0,
                    'raw' => $payload,
                ];
            }
        }

        return [
            'period' => AnalysisPeriodResolver::toArray($from, $to),
            'providers_requested' => $providerSlugs,
            'has_data' => $snapshots !== [],
            'llm_usage' => [
                'included_in_recommend' => true,
                'json_field' => 'employee_delivery_matrix',
                'description' => 'При «Получить рекомендации» в LLM уходит сжатый JSON: сводка wellbeing, team_summary и employee_delivery_matrix (без сырых check-in и списков всех задач). Полный JSON — только в preview.',
            ],
            'by_provider' => $byProvider,
            'work_progress' => $merged,
            'integration_warnings' => array_values(array_unique($warnings)),
        ];
    }
}

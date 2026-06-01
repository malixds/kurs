<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\AnalysisPeriodRequest;
use App\Http\Requests\Dashboard\DeepAnalysisRequest;
use App\Integrations\IntegrationManager;
use App\Integrations\WorkProgressAggregator;
use App\Services\Analysis\DeepAnalysisExportService;
use App\Services\Analysis\DeepAnalysisLlmPayloadBuilder;
use App\Services\Analysis\LlmAnalysisService;
use App\Services\Billing\PlanLimitService;
use App\Services\Company\ActiveCompanyService;
use App\Support\AnalysisPeriodResolver;
use App\Support\DeepAnalysisPromptCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

class DeepAnalysisController extends Controller
{
    public function __construct(
        private readonly ActiveCompanyService $activeCompanyService,
        private readonly IntegrationManager $integrationManager,
        private readonly WorkProgressAggregator $workProgressAggregator,
        private readonly DeepAnalysisExportService $exportService,
        private readonly DeepAnalysisLlmPayloadBuilder $llmPayloadBuilder,
        private readonly LlmAnalysisService $llmService,
        private readonly PlanLimitService $planLimitService,
    ) {}

    public function index(Request $request): View
    {
        $companyId = $this->activeCompanyService->resolve(Auth::user());
        abort_if($companyId === null, 403);
        abort_unless($this->planLimitService->can($companyId, 'deep_analysis'), 403);

        $connected = $this->integrationManager->connectedIntegrations($companyId);
        $defaultTo = $request->input('to', now()->toDateString());
        $defaultFrom = $request->input('from', now()->subDays(6)->toDateString());

        return view('dashboard.deep-analysis', [
            'prompts' => DeepAnalysisPromptCatalog::options(),
            'connectedIntegrations' => $connected,
            'activeCompany' => Auth::user()->companies()->whereKey($companyId)->first(),
            'defaultFrom' => $defaultFrom,
            'defaultTo' => $defaultTo,
            'defaultPrompt' => $request->input('prompt', 'product_owner'),
        ]);
    }

    public function sync(DeepAnalysisRequest $request): JsonResponse
    {
        [$from, $to] = $this->resolvePeriod($request);
        $companyId = $this->activeCompanyService->resolve($request->user());
        abort_if($companyId === null, 403);

        $providers = $request->input('providers', []);
        $result = $this->integrationManager->syncWorkProgress($companyId, $from, $to, $providers);

        return response()->json([
            'data' => array_merge($result, [
                'summary' => $this->summarizeSyncResults($result['results'] ?? []),
            ]),
            'message' => $result['errors'] === []
                ? 'Данные синхронизированы.'
                : 'Синхронизация завершена с предупреждениями.',
        ], $result['errors'] === [] ? 200 : 207);
    }

    public function trackers(DeepAnalysisRequest $request): JsonResponse
    {
        [$from, $to] = $this->resolvePeriod($request);
        $companyId = $this->activeCompanyService->resolve($request->user());
        abort_if($companyId === null, 403);

        $providers = $request->input('providers', []);

        return response()->json([
            'data' => $this->exportService->exportTrackers($companyId, $from, $to, $providers),
            'message' => 'Данные трекеров за период (без вызова LLM).',
        ]);
    }

    public function preview(DeepAnalysisRequest $request): JsonResponse
    {
        [$from, $to] = $this->resolvePeriod($request);
        $companyId = $this->activeCompanyService->resolve($request->user());
        abort_if($companyId === null, 403);

        $providers = $request->input('providers', []);

        $full = $this->exportService->build($companyId, $from, $to, $providers);
        $llm = $this->llmPayloadBuilder->fromExport($full);

        $response = [
            'data' => $llm,
            'meta' => [
                'format' => 'llm_compact',
                'note' => 'Тот же JSON, что отправляется в LLM на шаге 4. Добавьте ?full=1 для полного экспорта (wellbeing + work_progress).',
            ],
        ];

        if ($request->boolean('full')) {
            $response['data_full'] = $full;
        }

        return response()->json($response);
    }

    public function recommend(DeepAnalysisRequest $request): JsonResponse
    {
        $request->validate([
            'prompt' => ['required', 'string', Rule::in(array_keys(DeepAnalysisPromptCatalog::all()))],
        ]);

        [$from, $to] = $this->resolvePeriod($request);
        $companyId = $this->activeCompanyService->resolve($request->user());
        abort_if($companyId === null, 403);

        $providers = $request->input('providers', []);
        $promptId = $request->input('prompt');
        $promptConfig = DeepAnalysisPromptCatalog::all()[$promptId] ?? null;
        abort_if($promptConfig === null, 422, 'Неизвестный промпт.');

        if ($request->boolean('sync_first')) {
            $this->integrationManager->syncWorkProgress($companyId, $from, $to, $providers);
        }

        $payload = $this->exportService->build($companyId, $from, $to, $providers);

        if ($payload['wellbeing']['summary']['total_answers'] === 0 && ($payload['work_progress']['employees'] ?? []) === []) {
            return response()->json([
                'message' => 'Нет данных wellbeing и трекеров за период. Выполните check-in и синхронизацию интеграций.',
            ], 422);
        }

        $llmPayload = $this->llmPayloadBuilder->fromExport($payload);
        $recommendation = $this->llmService->analyzeCombined($promptConfig['system'], $llmPayload);

        return response()->json([
            'data' => [
                'prompt_id' => $promptId,
                'prompt_label' => $promptConfig['label'],
                'period' => $payload['period'],
                'summary' => $payload['wellbeing']['summary'],
                'recommendation' => $recommendation,
            ],
        ]);
    }

    /**
     * @param  array<string, array>  $results
     * @return array<string, array{contributors: int, tasks_closed: int, overdue: int, warnings: int}>
     */
    private function summarizeSyncResults(array $results): array
    {
        $summary = [];

        foreach ($results as $slug => $payload) {
            $team = $payload['team_summary'] ?? [];
            $summary[$slug] = [
                'contributors' => (int) ($team['contributors'] ?? count($payload['employees'] ?? [])),
                'issues_fetched' => (int) ($team['issues_fetched'] ?? 0),
                'tasks_closed' => (int) ($team['tasks_closed'] ?? 0),
                'overdue' => (int) ($team['overdue'] ?? 0),
                'warnings' => count($payload['warnings'] ?? []),
            ];
        }

        return $summary;
    }

    private function resolvePeriod(AnalysisPeriodRequest $request): array
    {
        try {
            $period = $request->filled('from') || $request->filled('to')
                ? null
                : ($request->filled('period') ? $request->integer('period') : 7);

            return AnalysisPeriodResolver::resolve(
                $period,
                $request->input('from'),
                $request->input('to'),
            );
        } catch (InvalidArgumentException $exception) {
            abort(422, $exception->getMessage());
        }
    }
}

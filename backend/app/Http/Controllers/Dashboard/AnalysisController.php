<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\LlmRecommendationSource;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\AnalysisPeriodRequest;
use App\Services\Analysis\EmployeeResponsesExportService;
use App\Services\Analysis\LlmAnalysisService;
use App\Services\Analysis\LlmRecommendationStoreService;
use App\Services\Analysis\WellbeingLlmPayloadBuilder;
use App\Services\Company\ActiveCompanyService;
use App\Support\AnalysisPeriodResolver;
use App\Support\AnalysisPromptCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

class AnalysisController extends Controller
{
    public function __construct(
        private readonly ActiveCompanyService $activeCompanyService,
        private readonly EmployeeResponsesExportService $exportService,
        private readonly LlmAnalysisService $llmService,
        private readonly WellbeingLlmPayloadBuilder $wellbeingLlmPayloadBuilder,
        private readonly LlmRecommendationStoreService $recommendationStore,
    ) {}

    public function index(Request $request): View
    {
        $companyId = $this->activeCompanyService->resolve(Auth::user());
        abort_if($companyId === null, 403);

        $prompts = AnalysisPromptCatalog::options();

        $defaultTo = $request->input('to', now()->toDateString());
        $defaultFrom = $request->input('from', now()->subDays(6)->toDateString());

        return view('dashboard.analysis', [
            'prompts' => $prompts,
            'activeCompany' => Auth::user()->companies()->whereKey($companyId)->first(),
            'defaultFrom' => $defaultFrom,
            'defaultTo' => $defaultTo,
            'defaultPrompt' => $request->input('prompt', 'psychologist'),
        ]);
    }

    public function exportResponses(AnalysisPeriodRequest $request): JsonResponse
    {
        [$from, $to] = $this->resolvePeriod($request);
        $companyId = $this->activeCompanyService->resolve($request->user());

        abort_if($companyId === null, 403);

        return response()->json([
            'data' => $this->exportService->exportForCompany($companyId, $from, $to),
        ]);
    }

    public function recommend(AnalysisPeriodRequest $request): JsonResponse
    {
        $request->validate([
            'prompt' => ['required', 'string', Rule::in(array_keys(AnalysisPromptCatalog::all()))],
        ]);

        [$from, $to] = $this->resolvePeriod($request);
        $companyId = $this->activeCompanyService->resolve($request->user());

        abort_if($companyId === null, 403);

        $promptId = $request->input('prompt');
        $allPrompts = AnalysisPromptCatalog::all();
        $promptConfig = $allPrompts[$promptId] ?? null;

        abort_if($promptConfig === null, 422, 'Неизвестный промпт.');

        $payload = $this->exportService->exportForCompany($companyId, $from, $to);

        if ($payload['summary']['total_answers'] === 0) {
            return response()->json([
                'message' => 'За выбранный период нет ответов сотрудников. Измените период или дождитесь check-in.',
            ], 422);
        }

        $llmPayload = $this->wellbeingLlmPayloadBuilder->fromExport($payload);
        $recommendation = $this->llmService->analyze($promptConfig['system'], $llmPayload);

        $saved = $this->recommendationStore->store(
            companyId: $companyId,
            userId: $request->user()->id,
            source: LlmRecommendationSource::Analysis,
            promptId: $promptId,
            promptLabel: $promptConfig['label'],
            periodFrom: $from,
            periodTo: $to,
            recommendation: $recommendation,
            summary: $payload['summary'],
        );

        return response()->json([
            'data' => [
                'prompt_id' => $promptId,
                'prompt_label' => $promptConfig['label'],
                'period' => $payload['period'],
                'summary' => $payload['summary'],
                'recommendation' => $recommendation,
                'recommendation_id' => $saved->id,
                'history_url' => route('dashboard.recommendations.show', $saved),
            ],
        ]);
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

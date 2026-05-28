<?php

namespace App\Http\Controllers\Api\V1\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\AnalysisPeriodRequest;
use App\Services\Analysis\EmployeeResponsesExportService;
use App\Services\Company\ActiveCompanyService;
use App\Support\AnalysisPeriodResolver;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class AnalysisExportController extends Controller
{
    public function __construct(
        private readonly EmployeeResponsesExportService $exportService,
        private readonly ActiveCompanyService $activeCompanyService,
    ) {}

    /**
     * GET /api/v1/dashboard/analysis/responses
     *
     * Query: period=7|14 OR from=YYYY-MM-DD&to=YYYY-MM-DD
     */
    public function responses(AnalysisPeriodRequest $request): JsonResponse
    {
        $user = $request->user();
        $companyId = $this->activeCompanyService->resolve($user) ?? $user->company_id;

        abort_if($companyId === null, 403, 'Company context is required.');

        try {
            $period = $request->filled('from') || $request->filled('to')
                ? null
                : ($request->filled('period') ? $request->integer('period') : 7);

            [$from, $to] = AnalysisPeriodResolver::resolve(
                $period,
                $request->input('from'),
                $request->input('to'),
            );
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'data' => $this->exportService->exportForCompany($companyId, $from, $to),
        ]);
    }
}

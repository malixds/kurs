<?php

namespace App\Http\Controllers\Api\V1\Dashboard;

use App\DTOs\Analytics\AnalyticsFilterDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Dashboard\AnalyticsRequest;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Services\Analytics\AnalyticsService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class AnalyticsController extends Controller
{
    public function __construct(
        private readonly AnalyticsService $analyticsService,
        private readonly EmployeeRepositoryInterface $employeeRepository,
    ) {}

    public function overview(AnalyticsRequest $request): JsonResponse
    {
        $filter = $this->buildFilter($request);

        return response()->json([
            'data' => $this->analyticsService->getOverview($filter),
        ]);
    }

    public function employeeHistory(int $employeeId, AnalyticsRequest $request): JsonResponse
    {
        $user = $request->user();

        $employee = $this->employeeRepository->findByIdForCompany(
            $employeeId,
            $user->company_id,
        );

        abort_if($employee === null, 404, 'Employee not found.');

        $filter = $this->buildFilter($request);

        return response()->json([
            'data' => $this->analyticsService->getEmployeeHistory($employeeId, $filter),
        ]);
    }

    private function buildFilter(AnalyticsRequest $request): AnalyticsFilterDto
    {
        $user = $request->user();

        return new AnalyticsFilterDto(
            companyId: $user->company_id,
            from: Carbon::parse($request->input('from', now()->subDays(29)->toDateString()))->startOfDay(),
            to: Carbon::parse($request->input('to', now()->toDateString()))->endOfDay(),
            employeeId: $request->integer('employee_id') ?: null,
            departmentId: $request->integer('department_id') ?: null,
        );
    }
}

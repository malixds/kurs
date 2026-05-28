<?php

namespace App\Http\Controllers\Dashboard;

use App\DTOs\Analytics\AnalyticsFilterDto;
use App\Http\Controllers\Controller;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Services\Analytics\AnalyticsService;
use App\Services\Company\ActiveCompanyService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly AnalyticsService $analyticsService,
        private readonly EmployeeRepositoryInterface $employeeRepository,
        private readonly ActiveCompanyService $activeCompanyService,
    ) {}

    public function index(Request $request): View
    {
        $user = Auth::user();
        $companyId = $this->activeCompanyService->resolve($user);

        abort_if($companyId === null, 403);

        $filter = new AnalyticsFilterDto(
            companyId: $companyId,
            from: Carbon::parse($request->input('from', now()->subDays(29)->toDateString()))->startOfDay(),
            to: Carbon::parse($request->input('to', now()->toDateString()))->endOfDay(),
            departmentId: $request->integer('department_id') ?: null,
        );

        $overview = $this->analyticsService->getOverview($filter);

        $activeCompany = $user->companies()->whereKey($companyId)->first();

        return view('dashboard.index', [
            'overview' => $overview,
            'activeCompany' => $activeCompany,
            'filters' => [
                'from' => $filter->from->toDateString(),
                'to' => $filter->to->toDateString(),
            ],
        ]);
    }

    public function employee(Request $request, int $employeeId): View
    {
        $user = Auth::user();
        $companyId = $this->activeCompanyService->resolve($user);

        abort_if($companyId === null, 403);

        $employee = $this->employeeRepository->findByIdForCompany(
            $employeeId,
            $companyId,
        );

        abort_if($employee === null, 404);

        $filter = new AnalyticsFilterDto(
            companyId: $companyId,
            from: Carbon::parse($request->input('from', now()->subDays(29)->toDateString()))->startOfDay(),
            to: Carbon::parse($request->input('to', now()->toDateString()))->endOfDay(),
            employeeId: $employeeId,
        );

        $history = $this->analyticsService->getEmployeeHistory($employeeId, $filter);

        return view('dashboard.employee', [
            'employee' => $employee,
            'history' => $history,
            'filters' => [
                'from' => $filter->from->toDateString(),
                'to' => $filter->to->toDateString(),
            ],
        ]);
    }
}

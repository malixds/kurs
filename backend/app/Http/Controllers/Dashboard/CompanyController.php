<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\Company\ActiveCompanyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function __construct(
        private readonly ActiveCompanyService $activeCompanyService,
    ) {}

    public function index(Request $request): View
    {
        $companies = $request->user()
            ->companies()
            ->orderByDesc('company_user.created_at')
            ->get()
            ->map(function (Company $company) use ($request): array {
                return [
                    'id' => $company->id,
                    'name' => $company->name,
                    'secret_key' => $company->secret_key,
                    'connected_at' => $company->pivot->created_at,
                    'is_active' => $this->activeCompanyService->resolve($request->user()) === $company->id,
                ];
            });

        return view('companies.index', [
            'companies' => $companies,
        ]);
    }

    public function show(Request $request, Company $company): View
    {
        $this->authorizeCompany($request, $company);

        return view('companies.show', [
            'company' => $company,
            'isActive' => $this->activeCompanyService->resolve($request->user()) === $company->id,
        ]);
    }

    public function switch(Request $request, Company $company): RedirectResponse
    {
        $this->authorizeCompany($request, $company);

        $this->activeCompanyService->setActive($request->user(), $company->id);

        return redirect()->route('dashboard.index');
    }

    private function authorizeCompany(Request $request, Company $company): void
    {
        abort_unless(
            $request->user()->companies()->whereKey($company->id)->exists(),
            403,
        );
    }
}

<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\StoreCompanyRequest;
use App\Services\Onboarding\CompanyOnboardingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    public function __construct(
        private readonly CompanyOnboardingService $onboardingService,
    ) {}

    public function welcome(): View
    {
        return view('onboarding.welcome');
    }

    public function startConnect(): RedirectResponse
    {
        if (auth()->check()) {
            return redirect()->route('onboarding.company.create');
        }

        session(['url.intended' => route('onboarding.company.create')]);

        return redirect()
            ->route('register')
            ->with('info', 'Создайте аккаунт или войдите, чтобы подключить компанию.');
    }

    public function create(): View
    {
        return view('onboarding.connect');
    }

    public function store(StoreCompanyRequest $request): RedirectResponse
    {
        $company = $this->onboardingService->createCompanyForUser(
            $request->user(),
            $request->validated('name'),
        );

        $request->session()->flash('company_secret_key', $company->secret_key);
        $request->session()->flash('company_name', $company->name);
        $request->session()->flash('company_id', $company->id);

        return redirect()->route('onboarding.success');
    }

    public function success(): View|RedirectResponse
    {
        if (! session()->has('company_secret_key')) {
            return redirect()->route('companies.index');
        }

        return view('onboarding.success', [
            'companyName' => session('company_name'),
            'secretKey' => session('company_secret_key'),
        ]);
    }
}

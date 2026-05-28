<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Integrations\IntegrationManager;
use App\Models\CompanyIntegration;
use App\Models\Employee;
use App\Models\EmployeeIntegrationIdentity;
use App\Services\Billing\PlanLimitService;
use App\Services\Company\ActiveCompanyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class IntegrationsController extends Controller
{
    public function __construct(
        private readonly ActiveCompanyService $activeCompanyService,
        private readonly IntegrationManager $integrationManager,
        private readonly PlanLimitService $planLimitService,
    ) {}

    public function index(): View
    {
        $companyId = $this->activeCompanyService->resolve(Auth::user());
        abort_if($companyId === null, 403);

        $providers = $this->integrationManager->providersForUi();
        $integrations = CompanyIntegration::query()
            ->where('company_id', $companyId)
            ->get()
            ->keyBy('provider_slug');

        $employees = Employee::query()
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->get();

        $identities = EmployeeIntegrationIdentity::query()
            ->whereIn('company_integration_id', $integrations->pluck('id'))
            ->with('companyIntegration')
            ->get()
            ->groupBy('employee_id');

        return view('dashboard.integrations', [
            'providers' => $providers,
            'integrations' => $integrations,
            'employees' => $employees,
            'identities' => $identities,
            'activeCompany' => Auth::user()->companies()->whereKey($companyId)->first(),
        ]);
    }

    public function store(Request $request, string $provider): RedirectResponse
    {
        $companyId = $this->activeCompanyService->resolve(Auth::user());
        abort_if($companyId === null, 403);
        abort_unless($this->planLimitService->can($companyId, 'integration:'.$provider), 403);

        $schema = config("integrations.providers.{$provider}.config_schema", []);
        $rules = [];
        foreach ($schema as $field) {
            $key = $field['key'];
            $rules[$key] = ($field['required'] ?? false) ? ['required', 'string'] : ['nullable', 'string'];
        }

        $validated = $request->validate($rules);
        $settings = [];
        if (isset($validated['queue_keys'])) {
            $settings['queue_keys'] = $validated['queue_keys'];
            unset($validated['queue_keys']);
        }
        if (isset($validated['project_keys'])) {
            $settings['project_keys'] = $validated['project_keys'];
            unset($validated['project_keys']);
        }
        if (isset($validated['team_ids'])) {
            $settings['team_ids'] = $validated['team_ids'];
            unset($validated['team_ids']);
        }

        $existing = $this->integrationManager->getOrCreateIntegration($companyId, $provider);
        $merged = $existing->credentials ?? [];
        foreach ($validated as $key => $value) {
            if ($value === '' && isset($merged[$key])) {
                continue;
            }
            $merged[$key] = $value;
        }

        $this->integrationManager->saveCredentials($companyId, $provider, $merged, $settings);

        return redirect()->route('dashboard.integrations')->with('status', 'Интеграция сохранена.');
    }

    public function destroy(string $provider): RedirectResponse
    {
        $companyId = $this->activeCompanyService->resolve(Auth::user());
        abort_if($companyId === null, 403);

        $this->integrationManager->disconnect($companyId, $provider);

        return redirect()->route('dashboard.integrations')->with('status', 'Интеграция отключена.');
    }

    public function test(Request $request, string $provider): JsonResponse
    {
        $companyId = $this->activeCompanyService->resolve($request->user());
        abort_if($companyId === null, 403);

        try {
            $credentials = $request->except(['_token']);
            $ok = $this->integrationManager->testConnection($companyId, $provider, $credentials);

            return response()->json(['ok' => $ok, 'message' => 'Подключение успешно.']);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }
}

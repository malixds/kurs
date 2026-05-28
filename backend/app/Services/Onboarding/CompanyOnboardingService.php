<?php

namespace App\Services\Onboarding;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use App\Services\Company\ActiveCompanyService;
use Illuminate\Support\Facades\DB;

class CompanyOnboardingService
{
    public function __construct(
        private readonly ActiveCompanyService $activeCompanyService,
    ) {}

    public function createCompanyForUser(User $user, string $companyName): Company
    {
        return DB::transaction(function () use ($user, $companyName): Company {
            $company = Company::query()->create([
                'name' => $companyName,
            ]);

            $user->companies()->attach($company->id, [
                'role' => UserRole::Admin->value,
            ]);

            $user->update([
                'company_id' => $company->id,
                'role' => UserRole::Admin,
            ]);

            $this->activeCompanyService->setActive($user, $company->id);

            return $company;
        });
    }
}

<?php

namespace App\Services\Company;

use App\Models\User;

class ActiveCompanyService
{
    public const SESSION_KEY = 'active_company_id';

    public function resolve(User $user): ?int
    {
        $activeId = session(self::SESSION_KEY);

        if ($activeId !== null && $user->companies()->whereKey($activeId)->exists()) {
            return (int) $activeId;
        }

        $companyId = $user->companies()->value('companies.id') ?? $user->company_id;

        if ($companyId === null) {
            return null;
        }

        session([self::SESSION_KEY => $companyId]);

        return (int) $companyId;
    }

    public function setActive(User $user, int $companyId): void
    {
        abort_unless($user->companies()->whereKey($companyId)->exists(), 403);

        session([self::SESSION_KEY => $companyId]);
        $user->update(['company_id' => $companyId]);
    }
}

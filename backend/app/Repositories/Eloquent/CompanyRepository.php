<?php

namespace App\Repositories\Eloquent;

use App\Models\Company;
use App\Repositories\Contracts\CompanyRepositoryInterface;

class CompanyRepository implements CompanyRepositoryInterface
{
    public function findBySecretKey(string $secretKey): ?Company
    {
        return Company::query()->where('secret_key', $secretKey)->first();
    }

    public function findById(int $id): ?Company
    {
        return Company::query()->find($id);
    }
}

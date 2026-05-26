<?php

namespace App\Repositories\Contracts;

use App\Models\Company;

interface CompanyRepositoryInterface
{
    public function findBySecretKey(string $secretKey): ?Company;

    public function findById(int $id): ?Company;
}

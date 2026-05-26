<?php

namespace App\Repositories\Contracts;

use App\Models\Department;

interface DepartmentRepositoryInterface
{
    public function findByExternalId(int $companyId, string $externalId): ?Department;
}

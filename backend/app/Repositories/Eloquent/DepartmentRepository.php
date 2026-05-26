<?php

namespace App\Repositories\Eloquent;

use App\Models\Department;
use App\Repositories\Contracts\DepartmentRepositoryInterface;

class DepartmentRepository implements DepartmentRepositoryInterface
{
    public function findByExternalId(int $companyId, string $externalId): ?Department
    {
        return Department::query()
            ->where('company_id', $companyId)
            ->where('external_id', $externalId)
            ->first();
    }
}

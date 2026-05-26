<?php

namespace App\Repositories\Eloquent;

use App\Models\Employee;
use App\Repositories\Contracts\EmployeeRepositoryInterface;

class EmployeeRepository implements EmployeeRepositoryInterface
{
    public function findByExternalId(int $companyId, string $externalId): ?Employee
    {
        return Employee::query()
            ->where('company_id', $companyId)
            ->where('external_id', $externalId)
            ->first();
    }

    public function upsertFromCheckIn(
        int $companyId,
        string $externalId,
        ?string $email,
        ?string $name,
        ?int $departmentId = null,
    ): Employee {
        return Employee::query()->updateOrCreate(
            [
                'company_id' => $companyId,
                'external_id' => $externalId,
            ],
            array_filter([
                'email' => $email,
                'name' => $name,
                'department_id' => $departmentId,
            ], fn ($value) => $value !== null),
        );
    }

    public function findByIdForCompany(int $employeeId, int $companyId): ?Employee
    {
        return Employee::query()
            ->where('company_id', $companyId)
            ->whereKey($employeeId)
            ->first();
    }
}

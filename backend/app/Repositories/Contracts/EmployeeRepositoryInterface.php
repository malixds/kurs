<?php

namespace App\Repositories\Contracts;

use App\Models\Employee;

interface EmployeeRepositoryInterface
{
    public function findByExternalId(int $companyId, string $externalId): ?Employee;

    public function upsertFromCheckIn(
        int $companyId,
        string $externalId,
        ?string $email,
        ?string $name,
        ?int $departmentId = null,
    ): Employee;

    public function findByIdForCompany(int $employeeId, int $companyId): ?Employee;
}

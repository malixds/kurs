<?php

namespace App\Integrations\Support;

use App\Models\Employee;
use Illuminate\Support\Collection;

class IntegrationEmployeeMatcher
{
    /**
     * Сопоставление только по точному совпадению email (без эвристик по имени/логину).
     *
     * @param  Collection<int, Employee>  $employees
     */
    public function find(
        Collection $employees,
        ?string $externalEmail,
        ?string $displayName = null,
        ?string $assigneeKey = null,
    ): ?Employee {
        if ($externalEmail === null || $externalEmail === '') {
            return null;
        }

        return $employees->first(
            fn (Employee $e) => strcasecmp($e->email ?? '', $externalEmail) === 0,
        );
    }
}

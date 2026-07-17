<?php

namespace Database\Factories;

use App\Models\CheckIn;
use App\Models\Company;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CheckIn>
 */
class CheckInFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'employee_id' => Employee::factory(),
            'survey_id' => null,
            'check_in_date' => now()->toDateString(),
            'completed_at' => now(),
        ];
    }
}

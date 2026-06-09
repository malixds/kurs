<?php

namespace Database\Factories;

use App\Models\Survey;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Survey>
 */
class SurveyFactory extends Factory
{
    public function definition(): array
    {
        return [
            // Null company_id = global survey served to every company.
            'company_id' => null,
            'title' => 'Daily wellbeing check-in',
            'is_active' => true,
        ];
    }
}

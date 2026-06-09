<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Employee;
use App\Models\SurveyAnswer;
use App\Models\SurveyQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SurveyAnswer>
 */
class SurveyAnswerFactory extends Factory
{
    public function definition(): array
    {
        $score = fake()->numberBetween(1, 5);

        return [
            'company_id' => Company::factory(),
            'employee_id' => Employee::factory(),
            'survey_question_id' => SurveyQuestion::factory(),
            'answer' => (string) $score,
            'score' => $score,
            'check_in_date' => now()->toDateString(),
        ];
    }
}

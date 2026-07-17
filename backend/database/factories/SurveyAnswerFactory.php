<?php

namespace Database\Factories;

use App\Models\CheckIn;
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
            'check_in_id' => CheckIn::factory(),
            // Denormalized copies, kept consistent with the parent check-in
            // unless explicitly overridden.
            'company_id' => fn (array $attrs) => CheckIn::find($attrs['check_in_id'])->company_id,
            'employee_id' => fn (array $attrs) => CheckIn::find($attrs['check_in_id'])->employee_id,
            'check_in_date' => fn (array $attrs) => CheckIn::find($attrs['check_in_id'])->check_in_date->toDateString(),
            'survey_question_id' => SurveyQuestion::factory(),
            'answer' => (string) $score,
            'score' => $score,
        ];
    }
}

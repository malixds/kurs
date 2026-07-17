<?php

namespace Database\Factories;

use App\Enums\QuestionType;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SurveyQuestion>
 */
class SurveyQuestionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'survey_id' => Survey::factory(),
            'question' => fake()->sentence().'?',
            'type' => QuestionType::Scale,
            'sort_order' => fake()->numberBetween(0, 10),
            'options' => ['min' => 1, 'max' => 5],
        ];
    }

    public function boolean(): static
    {
        return $this->state(fn () => ['type' => QuestionType::Boolean, 'options' => null]);
    }

    public function text(): static
    {
        return $this->state(fn () => ['type' => QuestionType::Text, 'options' => null]);
    }
}

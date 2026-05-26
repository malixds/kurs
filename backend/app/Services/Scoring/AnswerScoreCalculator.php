<?php

namespace App\Services\Scoring;

use App\Enums\QuestionType;
use App\Models\SurveyQuestion;

class AnswerScoreCalculator
{
    public function calculate(SurveyQuestion $question, string $answer): ?float
    {
        return match ($question->type) {
            QuestionType::Scale => $this->normalizeScale($answer, $question),
            QuestionType::Boolean => $this->normalizeBoolean($answer),
            QuestionType::Text => null,
        };
    }

    private function normalizeScale(string $answer, SurveyQuestion $question): ?float
    {
        if (! is_numeric($answer)) {
            return null;
        }

        $value = (float) $answer;
        $min = (float) ($question->options['min'] ?? 1);
        $max = (float) ($question->options['max'] ?? 5);

        if ($value < $min || $value > $max) {
            return null;
        }

        return round($value, 2);
    }

    private function normalizeBoolean(string $answer): ?float
    {
        $normalized = strtolower(trim($answer));

        return match ($normalized) {
            '1', 'true', 'yes', 'да' => 5.0,
            '0', 'false', 'no', 'нет' => 1.0,
            default => null,
        };
    }
}

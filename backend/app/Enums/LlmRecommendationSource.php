<?php

namespace App\Enums;

enum LlmRecommendationSource: string
{
    case Analysis = 'analysis';
    case DeepAnalysis = 'deep_analysis';

    public function label(): string
    {
        return match ($this) {
            self::Analysis => 'Анализ (LLM)',
            self::DeepAnalysis => 'Глубокий анализ',
        };
    }
}

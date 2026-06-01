<?php

namespace App\Http\Requests\Dashboard;

use App\Support\DeepAnalysisPromptCatalog;
use Illuminate\Validation\Rule;

class DeepAnalysisRequest extends AnalysisPeriodRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'providers' => ['nullable', 'array'],
            'providers.*' => ['string', Rule::in(array_keys(config('integrations.providers', [])))],
            'sync_first' => ['nullable', 'boolean'],
            // Глубокий анализ использует deep_analysis_prompts, не analysis_prompts.
            'prompt' => ['nullable', 'string', Rule::in(array_keys(DeepAnalysisPromptCatalog::all()))],
        ]);
    }
}

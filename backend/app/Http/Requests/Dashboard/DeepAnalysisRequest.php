<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeepAnalysisRequest extends AnalysisPeriodRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'providers' => ['nullable', 'array'],
            'providers.*' => ['string', Rule::in(array_keys(config('integrations.providers', [])))],
            'sync_first' => ['nullable', 'boolean'],
        ]);
    }
}

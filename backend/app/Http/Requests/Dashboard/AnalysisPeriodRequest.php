<?php

namespace App\Http\Requests\Dashboard;

use App\Support\AnalysisPromptCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AnalysisPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'period' => ['nullable', 'integer', Rule::in([7, 14])],
            'from' => ['nullable', 'date', 'required_with:to'],
            'to' => ['nullable', 'date', 'after_or_equal:from', 'required_with:from'],
            'prompt' => ['nullable', 'string', Rule::in(array_keys(AnalysisPromptCatalog::all()))],
        ];
    }
}

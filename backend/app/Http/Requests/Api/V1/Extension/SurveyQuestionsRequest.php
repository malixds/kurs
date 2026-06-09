<?php

namespace App\Http\Requests\Api\V1\Extension;

use Illuminate\Foundation\Http\FormRequest;

class SurveyQuestionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // The key belongs in a header so it never lands in access logs;
        // query/body input is still accepted for older extension installs.
        if ($this->header('X-Company-Key') !== null) {
            $this->merge(['secret_key' => $this->header('X-Company-Key')]);
        }
    }

    public function rules(): array
    {
        return [
            'secret_key' => ['required', 'string', 'size:48'],
        ];
    }
}

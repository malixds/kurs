<?php

namespace App\Http\Requests\Api\V1\Extension;

use Illuminate\Foundation\Http\FormRequest;

class SurveyQuestionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'secret_key' => ['required', 'string', 'size:48'],
        ];
    }
}

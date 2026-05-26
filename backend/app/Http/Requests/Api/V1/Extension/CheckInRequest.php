<?php

namespace App\Http\Requests\Api\V1\Extension;

use Illuminate\Foundation\Http\FormRequest;

class CheckInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'secret_key' => ['required', 'string', 'size:48'],
            'employee' => ['required', 'array'],
            'employee.external_id' => ['required', 'string', 'max:255'],
            'employee.email' => ['nullable', 'email', 'max:255'],
            'employee.name' => ['nullable', 'string', 'max:255'],
            'employee.department_external_id' => ['nullable', 'string', 'max:255'],
            'answers' => ['required', 'array', 'min:1'],
            'answers.*.question_id' => ['required', 'integer', 'min:1'],
            'answers.*.answer' => ['required', 'string', 'max:5000'],
        ];
    }
}

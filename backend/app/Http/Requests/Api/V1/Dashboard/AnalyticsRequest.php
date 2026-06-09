<?php

namespace App\Http\Requests\Api\V1\Dashboard;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AnalyticsRequest extends FormRequest
{
    private const MAX_RANGE_DAYS = 366;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = $this->user()?->company_id;

        return [
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from', 'before_or_equal:'.now()->toDateString()],
            'employee_id' => [
                'nullable',
                'integer',
                Rule::exists('employees', 'id')->where('company_id', $companyId),
            ],
            'department_id' => [
                'nullable',
                'integer',
                Rule::exists('departments', 'id')->where('company_id', $companyId),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $from = $this->input('from');
            $to = $this->input('to');

            if (! is_string($from) || ! is_string($to)) {
                return;
            }

            try {
                $days = Carbon::parse($from)->diffInDays(Carbon::parse($to), absolute: true);
            } catch (\Throwable) {
                return;
            }

            if ($days > self::MAX_RANGE_DAYS) {
                $validator->errors()->add(
                    'to',
                    sprintf('The date range may not exceed %d days.', self::MAX_RANGE_DAYS),
                );
            }
        });
    }
}

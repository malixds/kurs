<?php

namespace App\Repositories\Contracts;

use App\DTOs\CheckIn\AnswerDto;
use App\Models\CheckIn;
use App\Models\Employee;

interface SurveyAnswerRepositoryInterface
{
    /**
     * Persist (or update) a single daily check-in and its answers.
     * Idempotent per (employee, day): re-submitting the same day updates the
     * existing check-in and its answers instead of creating duplicates.
     *
     * @param  list<AnswerDto>  $answers
     * @return CheckIn with its `answers` relation populated
     */
    public function storeCheckIn(
        int $companyId,
        Employee $employee,
        ?int $surveyId,
        array $answers,
        string $checkInDate,
    ): CheckIn;
}

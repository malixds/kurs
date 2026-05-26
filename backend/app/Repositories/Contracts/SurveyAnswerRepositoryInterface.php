<?php

namespace App\Repositories\Contracts;

use App\DTOs\CheckIn\AnswerDto;
use App\Models\Employee;
use Illuminate\Support\Collection;

interface SurveyAnswerRepositoryInterface
{
    /**
     * @param  list<AnswerDto>  $answers
     * @return Collection<int, \App\Models\SurveyAnswer>
     */
    public function storeAnswers(
        int $companyId,
        Employee $employee,
        array $answers,
        string $checkInDate,
    ): Collection;
}

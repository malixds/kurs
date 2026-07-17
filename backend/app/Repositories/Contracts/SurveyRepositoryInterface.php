<?php

namespace App\Repositories\Contracts;

use App\Models\Survey;
use App\Models\SurveyQuestion;
use Illuminate\Support\Collection;

interface SurveyRepositoryInterface
{
    public function getActiveSurveyForCompany(?int $companyId): ?Survey;

    /** @return Collection<int, SurveyQuestion> */
    public function getActiveQuestionsForCompany(?int $companyId): Collection;
}

<?php

namespace App\Repositories\Contracts;

use App\Models\Survey;
use Illuminate\Support\Collection;

interface SurveyRepositoryInterface
{
    public function getActiveSurveyForCompany(?int $companyId): ?Survey;

    /** @return Collection<int, \App\Models\SurveyQuestion> */
    public function getActiveQuestionsForCompany(?int $companyId): Collection;
}

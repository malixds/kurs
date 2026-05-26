<?php

namespace App\Repositories\Eloquent;

use App\Models\Survey;
use App\Repositories\Contracts\SurveyRepositoryInterface;
use Illuminate\Support\Collection;

class SurveyRepository implements SurveyRepositoryInterface
{
    public function getActiveSurveyForCompany(?int $companyId): ?Survey
    {
        return Survey::query()
            ->with('questions')
            ->where('is_active', true)
            ->where(function ($query) use ($companyId): void {
                $query->whereNull('company_id');

                if ($companyId !== null) {
                    $query->orWhere('company_id', $companyId);
                }
            })
            ->orderByRaw('company_id IS NULL ASC')
            ->first();
    }

    public function getActiveQuestionsForCompany(?int $companyId): Collection
    {
        $survey = $this->getActiveSurveyForCompany($companyId);

        return $survey?->questions ?? collect();
    }
}

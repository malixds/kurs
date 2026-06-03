<?php

namespace App\Services\Analysis;

use App\Enums\LlmRecommendationSource;
use App\Models\LlmRecommendation;
use Carbon\CarbonInterface;

class LlmRecommendationStoreService
{
    /**
     * @param  list<string>|null  $providerSlugs
     * @param  array<string, mixed>|null  $summary
     */
    public function store(
        int $companyId,
        ?int $userId,
        LlmRecommendationSource $source,
        string $promptId,
        string $promptLabel,
        CarbonInterface $periodFrom,
        CarbonInterface $periodTo,
        string $recommendation,
        ?array $summary = null,
        ?array $providerSlugs = null,
    ): LlmRecommendation {
        return LlmRecommendation::query()->create([
            'company_id' => $companyId,
            'user_id' => $userId,
            'source' => $source,
            'prompt_id' => $promptId,
            'prompt_label' => $promptLabel,
            'period_from' => $periodFrom,
            'period_to' => $periodTo,
            'provider_slugs' => $providerSlugs !== [] ? $providerSlugs : null,
            'recommendation' => $recommendation,
            'summary' => $summary,
            'llm_model' => config('llm.openai.model'),
        ]);
    }
}

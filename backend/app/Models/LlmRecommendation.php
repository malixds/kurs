<?php

namespace App\Models;

use App\Enums\LlmRecommendationSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LlmRecommendation extends Model
{
    protected $fillable = [
        'company_id',
        'user_id',
        'source',
        'prompt_id',
        'prompt_label',
        'period_from',
        'period_to',
        'provider_slugs',
        'recommendation',
        'summary',
        'llm_model',
    ];

    protected function casts(): array
    {
        return [
            'source' => LlmRecommendationSource::class,
            'period_from' => 'date',
            'period_to' => 'date',
            'provider_slugs' => 'array',
            'summary' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function periodLabel(): string
    {
        return $this->period_from->format('d.m.Y').' — '.$this->period_to->format('d.m.Y');
    }

    public function providersLabel(): ?string
    {
        if ($this->provider_slugs === null || $this->provider_slugs === []) {
            return null;
        }

        return collect($this->provider_slugs)
            ->map(fn (string $slug) => config("integrations.providers.{$slug}.name", $slug))
            ->implode(', ');
    }

    public function excerpt(int $limit = 220): string
    {
        $text = preg_replace('/^\d+[\.\)]\s*/mu', '', $this->recommendation) ?? $this->recommendation;

        return \Illuminate\Support\Str::limit(trim($text), $limit);
    }
}

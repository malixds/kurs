<?php

namespace App\Support;

class DeepAnalysisPromptCatalog
{
    public static function all(): array
    {
        $prompts = config('deep_analysis_prompts');

        if (is_array($prompts) && $prompts !== []) {
            return $prompts;
        }

        $path = config_path('deep_analysis_prompts.php');

        if (is_readable($path)) {
            $loaded = require $path;

            return is_array($loaded) ? $loaded : [];
        }

        return [];
    }

    public static function options(): array
    {
        return collect(self::all())->map(fn (array $prompt, string $id) => [
            'id' => $id,
            'label' => $prompt['label'] ?? $id,
            'description' => $prompt['description'] ?? '',
        ])->values()->all();
    }
}

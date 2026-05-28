<?php

namespace App\Support;

class AnalysisPromptCatalog
{
    public static function all(): array
    {
        $prompts = config('analysis_prompts');

        if (is_array($prompts) && $prompts !== []) {
            return $prompts;
        }

        $path = config_path('analysis_prompts.php');

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

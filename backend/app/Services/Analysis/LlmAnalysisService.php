<?php

namespace App\Services\Analysis;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class LlmAnalysisService
{
    public function analyze(string $systemPrompt, array $responsesPayload): string
    {
        $apiKey = config('llm.openai.api_key');

        if (empty($apiKey)) {
            throw new RuntimeException(
                'OPENAI_API_KEY не задан. Добавьте ключ в backend/.env для получения рекомендаций от LLM.',
            );
        }

        $userMessage = "Данные check-in сотрудников (JSON):\n\n"
            .json_encode($responsesPayload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $response = Http::baseUrl(rtrim(config('llm.openai.base_url'), '/'))
            ->withToken($apiKey)
            ->timeout(config('llm.openai.timeout'))
            ->post('/chat/completions', [
                'model' => config('llm.openai.model'),
                'max_tokens' => config('llm.openai.max_tokens'),
                'temperature' => 0.4,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userMessage],
                ],
            ]);

        if (! $response->successful()) {
            $error = $response->json('error.message') ?? $response->body();

            throw new RuntimeException('Ошибка LLM API: '.$error);
        }

        $content = $response->json('choices.0.message.content');

        if (! is_string($content) || trim($content) === '') {
            throw new RuntimeException('LLM вернул пустой ответ.');
        }

        return trim($content);
    }

    public function analyzeCombined(string $systemPrompt, array $combinedPayload): string
    {
        $apiKey = config('llm.openai.api_key');

        if (empty($apiKey)) {
            throw new RuntimeException(
                'OPENAI_API_KEY не задан. Добавьте ключ в backend/.env для получения рекомендаций от LLM.',
            );
        }

        $userMessage = "Объединённые данные для глубокого анализа (JSON):\n\n"
            .json_encode($combinedPayload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $response = Http::baseUrl(rtrim(config('llm.openai.base_url'), '/'))
            ->withToken($apiKey)
            ->timeout(config('llm.openai.timeout'))
            ->post('/chat/completions', [
                'model' => config('llm.openai.model'),
                'max_tokens' => config('llm.openai.max_tokens'),
                'temperature' => 0.4,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userMessage],
                ],
            ]);

        if (! $response->successful()) {
            $error = $response->json('error.message') ?? $response->body();

            throw new RuntimeException('Ошибка LLM API: '.$error);
        }

        $content = $response->json('choices.0.message.content');

        if (! is_string($content) || trim($content) === '') {
            throw new RuntimeException('LLM вернул пустой ответ.');
        }

        return trim($content);
    }
}

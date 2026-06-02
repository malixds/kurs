<?php

namespace App\Services\Analysis;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class LlmAnalysisService
{
    public function analyze(string $systemPrompt, array $responsesPayload): string
    {
        return $this->requestLlm(
            $systemPrompt,
            "Сводка wellbeing по сотрудникам (JSON). Используй ТОЛЬКО employee_wellbeing_summary:\n\n"
            .json_encode($responsesPayload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            ."\n\nВ каждом пункте ответа укажи name из summary и цифры (avg_mood / avg_stress / team_support_yes_rate). "
            .'Не пиши «сотрудник» или «двое» без конкретного name.',
        );
    }

    public function analyzeCombined(string $systemPrompt, array $combinedPayload): string
    {
        return $this->requestLlm(
            $systemPrompt,
            "Объединённые данные для глубокого анализа (JSON):\n\n"
            .json_encode($combinedPayload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            ."\n\nГлавный источник — employee_delivery_matrix. "
            .'В каждой рекомендации: поле name (как в JSON), цифры wellbeing/tasks, ключи overdue_issues. '
            .'Запрещены формулировки без имени («сотрудник с низким настроением»).',
            deepAnalysis: true,
        );
    }

    private function requestLlm(string $systemPrompt, string $userMessage, bool $deepAnalysis = false): string
    {
        $apiKey = config('llm.openai.api_key');

        if (empty($apiKey)) {
            throw new RuntimeException(
                'OPENAI_API_KEY не задан. Добавьте ключ в backend/.env для получения рекомендаций от LLM.',
            );
        }

        $response = Http::baseUrl(rtrim(config('llm.openai.base_url'), '/'))
            ->withToken($apiKey)
            ->timeout(config('llm.openai.timeout'))
            ->post('/chat/completions', [
                'model' => config('llm.openai.model'),
                'max_tokens' => config('llm.openai.max_tokens'),
                'temperature' => 0.2,
                'messages' => [
                    ['role' => 'system', 'content' => $this->buildSystemPrompt($systemPrompt, $deepAnalysis)],
                    [
                        'role' => 'user',
                        'content' => $userMessage."\n\nОтветь только нумерованным списком рекомендаций на русском.",
                    ],
                ],
            ]);

        if (! $response->successful()) {
            $error = $response->json('error.message') ?? $response->body();

            throw new RuntimeException('Ошибка LLM API: '.$error);
        }

        $content = $this->extractContent($response->json());

        if (! is_string($content) || trim($content) === '') {
            $body = $response->json();
            $bodyPreview = is_array($body)
                ? mb_substr(json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '', 0, 600)
                : mb_substr((string) $response->body(), 0, 600);

            throw new RuntimeException(
                'LLM вернул пустой ответ. Проверьте OPENAI_MODEL или формат ответа провайдера. Body: '.$bodyPreview,
            );
        }

        return $this->normalizeRecommendation(trim($content));
    }

    private function buildSystemPrompt(string $rolePrompt, bool $deepAnalysis = false): string
    {
        $key = $deepAnalysis ? 'llm.deep_analysis_output_constraints' : 'llm.output_constraints';
        $constraints = config($key);

        if (! is_string($constraints) || trim($constraints) === '') {
            return $rolePrompt;
        }

        return trim($rolePrompt)."\n\n".trim($constraints);
    }

    /**
     * @param  array<string, mixed>|null  $json
     */
    private function extractContent(?array $json): ?string
    {
        if (! is_array($json)) {
            return null;
        }

        $content = $json['choices'][0]['message']['content'] ?? null;
        if (is_string($content) && trim($content) !== '') {
            return $content;
        }

        // Some OpenAI-compatible providers return content as parts array.
        if (is_array($content)) {
            $parts = collect($content)
                ->map(fn ($part) => is_array($part) ? ($part['text'] ?? null) : null)
                ->filter(fn ($part) => is_string($part) && trim($part) !== '')
                ->implode("\n");

            if ($parts !== '') {
                return $parts;
            }
        }

        $text = $json['choices'][0]['text'] ?? $json['output_text'] ?? null;
        if (is_string($text) && trim($text) !== '') {
            return $text;
        }

        return null;
    }

    private function normalizeRecommendation(string $content): string
    {
        $content = preg_replace('/\*\*(.+?)\*\*/s', '$1', $content) ?? $content;
        $content = preg_replace('/^#+\s*/m', '', $content) ?? $content;
        $content = trim($content);

        preg_match_all('/(?:^|\n)\s*(?:\d+[\.\)]\s+|[-•*]\s+)(.+)/u', $content, $matches);

        if ($matches[1] === []) {
            return $content;
        }

        $items = collect($matches[1])
            ->map(fn (string $item) => trim($item))
            ->filter(fn (string $item) => $item !== ''
                && ! preg_match('/^(yes\.?|final polish|correct format|example)/iu', $item)
                && preg_match('/[а-яё]/iu', $item))
            ->values()
            ->map(fn (string $item) => $this->ensureSentenceEnd($item))
            ->all();

        if ($items === []) {
            return $content;
        }

        return collect($items)
            ->map(fn (string $item, int $index) => ($index + 1).'. '.$item)
            ->implode("\n");
    }

    private function ensureSentenceEnd(string $text): string
    {
        $text = rtrim($text, " \t");

        if ($text === '' || preg_match('/[.!?…]$/u', $text)) {
            return $text;
        }

        if (preg_match('/^(.+[,:;])\s+\S+$/u', $text, $matches)) {
            return rtrim($matches[1], ',;:').'.';
        }

        return $text.'.';
    }
}

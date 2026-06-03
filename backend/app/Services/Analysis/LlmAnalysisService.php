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
            ."\n\nДай развёрнутые рекомендации: в каждом пункте name из summary, все доступные цифры, оценка риска, 2–3 шага со сроками. "
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
            .'Развёрнутые пункты: name, цифры wellbeing/tasks, ключи overdue_issues, риск, шаги, сроки. '
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

        $timeoutSeconds = (int) config('llm.openai.timeout', 300);

        $response = Http::baseUrl(rtrim(config('llm.openai.base_url'), '/'))
            ->withToken($apiKey)
            ->connectTimeout(30)
            ->timeout($timeoutSeconds)
            ->post('/chat/completions', [
                'model' => config('llm.openai.model'),
                'max_tokens' => config('llm.openai.max_tokens'),
                'temperature' => 0.2,
                'messages' => [
                    ['role' => 'system', 'content' => $this->buildSystemPrompt($systemPrompt, $deepAnalysis)],
                    [
                        'role' => 'user',
                        'content' => $userMessage."\n\nОтветь развёрнутыми рекомендациями на русском: 3–6 отдельных блоков без нумерации (без «1.», «2.»), между блоками — пустая строка, в каждом блоке 3–5 предложений.",
                    ],
                ],
            ]);

        if (! $response->successful()) {
            $error = $response->json('error.message') ?? $response->body();

            throw new RuntimeException('Ошибка LLM API: '.$error);
        }

        $body = $response->json();
        $content = $this->extractContent($body);

        if (! is_string($content) || trim($content) === '') {
            throw new RuntimeException($this->describeEmptyLlmResponse($body, $response->body()));
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

    /**
     * @param  array<string, mixed>|null  $json
     */
    private function describeEmptyLlmResponse(?array $json, string $rawBody): string
    {
        $choice = is_array($json) ? ($json['choices'][0] ?? null) : null;
        $message = is_array($choice) ? ($choice['message'] ?? null) : null;
        $finishReason = is_array($choice) ? ($choice['finish_reason'] ?? null) : null;
        $hasReasoning = is_array($message)
            && is_string($message['reasoning'] ?? null)
            && trim($message['reasoning']) !== '';

        if ($finishReason === 'length' && $hasReasoning) {
            return 'LLM исчерпал лимит токенов (finish_reason=length): модель '
                .config('llm.openai.model')
                .' потратила ответ на внутренние рассуждения (поле reasoning), а content остался пустым. '
                .'Увеличьте OPENAI_MAX_TOKENS (например 4096–8192) или смените OPENAI_MODEL на модель без thinking '
                .'(например gpt-4o-mini, gemini-flash).';
        }

        if ($finishReason === 'length') {
            return 'LLM обрезал ответ по лимиту токенов (finish_reason=length). Увеличьте OPENAI_MAX_TOKENS в .env.';
        }

        if ($hasReasoning) {
            return 'LLM вернул только reasoning без content. Модель '
                .config('llm.openai.model')
                .' не выдала итоговый текст — смените OPENAI_MODEL или увеличьте OPENAI_MAX_TOKENS.';
        }

        $bodyPreview = is_array($json)
            ? mb_substr(json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '', 0, 600)
            : mb_substr($rawBody, 0, 600);

        return 'LLM вернул пустой ответ. Проверьте OPENAI_MODEL или формат ответа провайдера. Body: '.$bodyPreview;
    }

    private function normalizeRecommendation(string $content): string
    {
        $content = preg_replace('/\*\*(.+?)\*\*/s', '$1', $content) ?? $content;
        $content = preg_replace('/^#+\s*/m', '', $content) ?? $content;
        $content = trim($content);

        if (! preg_match('/^\d+[\.\)]\s/mu', $content)) {
            return $content;
        }

        $blocks = preg_split('/\n+(?=\d+[\.\)]\s)/u', $content) ?: [];
        $normalized = [];

        foreach ($blocks as $block) {
            $block = trim($block);
            if ($block === '' || ! preg_match('/^(\d+)[\.\)]\s*([\s\S]+)$/u', $block, $matches)) {
                continue;
            }

            $body = trim($matches[2]);
            if ($body === ''
                || preg_match('/^(yes\.?|final polish|correct format|example)/iu', $body)
                || ! preg_match('/[а-яё]/iu', $body)) {
                continue;
            }

            $normalized[] = $body;
        }

        if ($normalized === []) {
            return $content;
        }

        return implode("\n\n", $normalized);
    }
}

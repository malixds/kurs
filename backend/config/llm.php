<?php

return [
    'provider' => env('LLM_PROVIDER', 'openai'),

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
        'timeout' => (int) env('OPENAI_TIMEOUT', 300),
        'max_tokens' => (int) env('OPENAI_MAX_TOKENS', 800),
    ],

    /*
    | Сжатие JSON для «Глубокого анализа» перед отправкой в LLM.
    */
    'deep_analysis' => [
        'max_overdue_issue_keys' => (int) env('LLM_DEEP_ANALYSIS_MAX_OVERDUE_KEYS', 10),
    ],

    /*
    | Appended to every analysis system prompt (not shown in the UI).
    */
    'output_constraints' => <<<'PROMPT'
Строгие правила формата ответа (обязательны):
1. Выводи ТОЛЬКО нумерованный список рекомендаций на русском языке (3–5 пунктов, максимум 6).
2. Каждый пункт — одно законченное предложение с точкой в конце.
3. КАЖДЫЙ пункт обязан содержать:
   - точное поле name из employee_wellbeing_summary (как в JSON, без замены на «сотрудник», «человек», «двое»);
   - минимум одну цифру из avg_mood, avg_stress или team_support_yes_rate для этого же человека.
4. ЗАПРЕЩЕНО без имени: «сотрудник с низким настроением», «двух сотрудников», «сотрудники, у которых», «критически низкие показатели» без указания name.
5. Если в одном пункте два человека — напиши оба name и цифры по каждому.
6. Запрещено: вступления, markdown, английский, пересказ JSON, общие советы без имён и цифр.

Пример правильного ответа:
1. Провести 1:1 с Иван Петров (avg_mood 2.1, team_support_yes_rate 0%) в течение двух рабочих дней из-за риска выгорания.
2. Обсудить с Мария Сидорова (avg_stress 4.3, avg_mood 2.8) снижение нагрузки на ближайшую неделю.
PROMPT,

    /*
    | Только для «Глубокого анализа» (wellbeing + Jira).
    */
    'deep_analysis_output_constraints' => <<<'PROMPT'
Строгие правила для глубокого анализа (обязательны):
1. Выводи ТОЛЬКО нумерованный список из 3–6 рекомендаций на русском языке.
2. Каждый пункт — одно законченное предложение с точкой в конце.
3. КАЖДАЯ рекомендация — только по employee_delivery_matrix:
   - обязательно поле name (дословно из JSON);
   - минимум одна цифра wellbeing (avg_mood, avg_stress, team_support_yes_rate) и/или tasks (закрыто, просрочено, открыто);
   - при просрочках — ключи из overdue_issues.
4. ЗАПРЕЩЕНО без name: «сотрудник», «двух сотрудников», «команда», «критически низкие показатели».
5. Один пункт = один человек (исключение: mapping_note — тогда укажи name из unmapped, если есть).
6. Запрещено: общие советы, вступления, markdown, английский.
PROMPT,
];

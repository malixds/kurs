@extends('layouts.app')

@section('title', 'Анализ')

@section('content')
    @include('dashboard.partials.nav')

    <div class="mb-8">
        <h1 class="text-3xl font-semibold">AI-анализ wellbeing</h1>
        <p class="mt-1 ds-text-secondary">
            @if ($activeCompany ?? null)
                {{ $activeCompany->name }} —
            @endif
            выберите сценарий, период и получите рекомендации от LLM
        </p>
    </div>

    <form id="analysis-form" class="space-y-10">
        @csrf
        <input type="hidden" name="prompt" id="prompt" value="{{ $defaultPrompt }}">

        <section>
            <h2 class="text-sm font-medium uppercase tracking-wider ds-text-muted">Сценарий анализа</h2>
            <p class="mt-1 text-sm ds-text-secondary">Выберите одну из ролей — от неё зависит фокус рекомендаций LLM.</p>

            <div class="mt-4 grid gap-4 md:grid-cols-3" id="prompt-cards" role="radiogroup" aria-label="Сценарий анализа">
                @forelse ($prompts as $prompt)
                    <button type="button"
                            class="prompt-card group flex h-full flex-col ds-panel p-5 text-left transition hover:border-[rgba(111,99,255,0.25)]
                                   {{ $defaultPrompt === $prompt['id'] ? 'prompt-card--active' : '' }}"
                            data-prompt-id="{{ $prompt['id'] }}"
                            aria-pressed="{{ $defaultPrompt === $prompt['id'] ? 'true' : 'false' }}">
                        <span class="prompt-card__title text-lg font-semibold text-[#111827] group-hover:text-[#4E44E5]">
                            {{ $prompt['label'] }}
                        </span>
                        <span class="prompt-card__desc mt-3 text-sm leading-relaxed ds-text-secondary group-hover:text-[#5F6473]">
                            {{ $prompt['description'] }}
                        </span>
                    </button>
                @empty
                    <p class="col-span-3 rounded-xl border border-dashed border-black/10 p-6 text-center ds-text-muted">
                        Сценарии не загружены. Выполните <code class="text-[#6F63FF]">php artisan config:clear</code>
                    </p>
                @endforelse
            </div>
        </section>

        <section>
            <h2 class="text-sm font-medium uppercase tracking-wider ds-text-muted">Период данных</h2>
            <p class="mt-1 text-sm ds-text-secondary">Укажите диапазон дат для анализа ответов сотрудников.</p>

            <div class="mt-4 ds-panel p-5">
                <div class="flex flex-wrap gap-2">
                    <button type="button" data-preset-days="7"
                            class="date-preset rounded-lg border border-black/[0.08] px-3 py-1.5 text-xs text-[#5F6473] hover:bg-[#F7F8FC]">
                        7 дней
                    </button>
                    <button type="button" data-preset-days="14"
                            class="date-preset rounded-lg border border-black/[0.08] px-3 py-1.5 text-xs text-[#5F6473] hover:bg-[#F7F8FC]">
                        14 дней
                    </button>
                    <button type="button" data-preset-days="30"
                            class="date-preset rounded-lg border border-black/[0.08] px-3 py-1.5 text-xs text-[#5F6473] hover:bg-[#F7F8FC]">
                        30 дней
                    </button>
                </div>

                <div class="mt-5">
                    @include('dashboard.partials.date-range', [
                        'fromValue' => $defaultFrom,
                        'toValue' => $defaultTo,
                        'fromName' => 'from',
                        'toName' => 'to',
                        'fromMax' => $defaultTo,
                        'toMax' => now()->toDateString(),
                    ])
                </div>
                <p id="date-range-label" class="mt-3 text-sm ds-text-muted"></p>
            </div>

            <div class="mt-5 flex flex-wrap gap-3">
                <button type="button" id="btn-export"
                        class="rounded-lg ds-btn-secondary !min-h-0 !px-4 !py-2 text-sm">
                    Показать JSON данных
                </button>
                <button type="button" id="btn-recommend"
                        class="rounded-lg ds-btn-primary !min-h-0 !px-4 !py-2 text-sm">
                    Получить рекомендации
                </button>
            </div>
        </section>

        <section class="space-y-4">
            <div id="status" class="ds-status hidden" role="status" aria-live="polite"></div>

            <div id="recommendation-panel" class="ds-recommendation-panel hidden">
                <div class="ds-recommendation-panel__header">
                    <span class="ds-badge">AI</span>
                    <h2 class="ds-recommendation-panel__title">Рекомендации</h2>
                </div>
                <div id="recommendation-meta" class="ds-recommendation-panel__meta"></div>
                <p id="recommendation-history" class="mt-1 hidden text-xs">
                    <a id="recommendation-history-link" href="{{ route('dashboard.recommendations.index') }}"
                       class="font-medium text-[#6F63FF] hover:underline">Открыть в истории →</a>
                </p>
                <div id="recommendation-body" class="ds-recommendation-panel__body"></div>
            </div>

            <div id="json-panel" class="hidden ds-panel p-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-lg font-medium">JSON для LLM</h2>
                    <div class="flex gap-2">
                        <button type="button" id="btn-copy-json"
                                class="rounded-lg ds-btn-secondary !min-h-0 !px-3 !py-1.5 text-xs">
                            Копировать
                        </button>
                        <button type="button" id="btn-hide-json"
                                class="rounded-lg ds-btn-secondary !min-h-0 !px-3 !py-1.5 text-xs">
                            Скрыть
                        </button>
                    </div>
                </div>
                <pre id="json-output" class="mt-4 max-h-[32rem] overflow-auto rounded-xl bg-[#F7F8FC] p-4 text-xs text-[#4E44E5]"></pre>
            </div>
        </section>
    </form>

    <style>
        .date-preset--active {
            border-color: rgba(111, 99, 255, 0.45);
            background: rgba(111, 99, 255, 0.12);
            color: #4e44e5;
        }

        .prompt-card:focus-visible {
            outline: 2px solid rgb(139 92 246);
            outline-offset: 2px;
        }
    </style>

    <script>
        const promptInput = document.getElementById('prompt');
        const promptCards = document.querySelectorAll('.prompt-card');
        const dateFrom = document.getElementById('date-from');
        const dateTo = document.getElementById('date-to');
        const dateRangeLabel = document.getElementById('date-range-label');
        const datePresets = document.querySelectorAll('.date-preset');
        const statusEl = document.getElementById('status');
        const btnExport = document.getElementById('btn-export');
        const btnHideJson = document.getElementById('btn-hide-json');
        const jsonPanel = document.getElementById('json-panel');
        const jsonOutput = document.getElementById('json-output');
        const recommendationPanel = document.getElementById('recommendation-panel');
        const recommendationBody = document.getElementById('recommendation-body');
        const recommendationMeta = document.getElementById('recommendation-meta');
        const csrf = document.querySelector('input[name="_token"]')?.value;

        let showStatus = (message, type = 'info') => {
            statusEl.classList.remove('hidden');
            statusEl.textContent = message;
        };

        function selectPrompt(promptId) {
            promptInput.value = promptId;
            promptCards.forEach((card) => {
                const isActive = card.dataset.promptId === promptId;
                card.classList.toggle('prompt-card--active', isActive);
                card.classList.toggle('ds-prompt-card--active', isActive);
                card.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });
        }

        promptCards.forEach((card) => {
            card.addEventListener('click', () => selectPrompt(card.dataset.promptId));
        });

        if (promptInput.value) {
            selectPrompt(promptInput.value);
        }

        function formatDateRu(iso) {
            if (!iso) return '';
            const [y, m, d] = iso.split('-');
            return `${d}.${m}.${y}`;
        }

        function daysBetween(from, to) {
            const a = new Date(from + 'T00:00:00');
            const b = new Date(to + 'T00:00:00');
            return Math.round((b - a) / 86400000) + 1;
        }

        function updateDateConstraints() {
            if (dateFrom.value > dateTo.value) {
                dateTo.value = dateFrom.value;
                dateTo.dispatchEvent(new Event('change', { bubbles: true }));
            }
            dateTo.min = dateFrom.value;
            dateFrom.max = dateTo.value;
            updateDateRangeLabel();
            updatePresetHighlight();
        }

        function updateDateRangeLabel() {
            if (!dateFrom.value || !dateTo.value) {
                dateRangeLabel.textContent = '';
                return;
            }
            const days = daysBetween(dateFrom.value, dateTo.value);
            dateRangeLabel.textContent = `${formatDateRu(dateFrom.value)} — ${formatDateRu(dateTo.value)} · ${days} ${days === 1 ? 'день' : days < 5 ? 'дня' : 'дней'}`;
        }

        function updatePresetHighlight() {
            const today = new Date().toISOString().slice(0, 10);
            datePresets.forEach((btn) => {
                const presetDays = Number(btn.dataset.presetDays);
                const expectedFrom = new Date();
                expectedFrom.setDate(expectedFrom.getDate() - (presetDays - 1));
                const expectedFromStr = expectedFrom.toISOString().slice(0, 10);
                const isActive = dateFrom.value === expectedFromStr && dateTo.value === today;
                btn.classList.toggle('date-preset--active', isActive);
            });
        }

        function applyPreset(days) {
            const to = new Date();
            const from = new Date();
            from.setDate(from.getDate() - (days - 1));
            dateTo.value = to.toISOString().slice(0, 10);
            dateFrom.value = from.toISOString().slice(0, 10);
            dateFrom.dispatchEvent(new Event('change', { bubbles: true }));
        }

        dateFrom.addEventListener('change', updateDateConstraints);
        dateTo.addEventListener('change', updateDateConstraints);
        datePresets.forEach((btn) => {
            btn.addEventListener('click', () => applyPreset(Number(btn.dataset.presetDays)));
        });

        updateDateConstraints();

        function validateDates() {
            if (!dateFrom.value || !dateTo.value) {
                throw new Error('Укажите даты начала и конца периода.');
            }
            if (dateFrom.value > dateTo.value) {
                throw new Error('Дата «С» не может быть позже даты «По».');
            }
        }

        function renderRecommendationText(el, text) {
            if (window.DsDashboard?.renderRecommendation) {
                window.DsDashboard.renderRecommendation(el, text);
                return;
            }

            el.textContent = text;
        }

        function queryParams() {
            return new URLSearchParams({
                from: dateFrom.value,
                to: dateTo.value,
                prompt: promptInput.value,
            });
        }

        function requestBody() {
            return {
                from: dateFrom.value,
                to: dateTo.value,
                prompt: promptInput.value,
            };
        }

        async function fetchExport() {
            if (!promptInput.value) {
                throw new Error('Выберите сценарий анализа.');
            }
            validateDates();

            showStatus('Загрузка данных…', 'loading');
            const response = await fetch(`{{ route('dashboard.analysis.responses') }}?${queryParams()}`, {
                headers: { 'Accept': 'application/json' },
            });
            const payload = await response.json();
            if (!response.ok) {
                throw new Error(payload.message ?? 'Ошибка загрузки данных');
            }
            return payload.data;
        }

        function isJsonPanelVisible() {
            return !jsonPanel.classList.contains('hidden');
        }

        function setJsonPanelVisible(visible) {
            jsonPanel.classList.toggle('hidden', !visible);
            btnExport.textContent = visible ? 'Скрыть JSON' : 'Показать JSON данных';
        }

        function hideJsonPanel() {
            setJsonPanelVisible(false);
        }

        function bindAnalysisActions() {
            showStatus = window.DsDashboard.createShowStatus(statusEl);

            btnHideJson.addEventListener('click', hideJsonPanel);

            btnExport.addEventListener('click', async () => {
                if (isJsonPanelVisible()) {
                    hideJsonPanel();
                    return;
                }

                try {
                    const data = await fetchExport();
                    jsonOutput.textContent = JSON.stringify(data, null, 2);
                    setJsonPanelVisible(true);
                    showStatus(`Загружено: ${data.summary.total_answers} ответов, ${data.summary.employees_with_data} сотрудников.`, 'success');
                } catch (e) {
                    showStatus(e.message, 'error');
                }
            });

            document.getElementById('btn-copy-json').addEventListener('click', async () => {
                await navigator.clipboard.writeText(jsonOutput.textContent);
                showStatus('JSON скопирован в буфер обмена.', 'success');
            });

            document.getElementById('btn-recommend').addEventListener('click', async () => {
                const btn = document.getElementById('btn-recommend');
                btn.disabled = true;
                showStatus('Отправка данных в LLM… Это может занять до минуту.', 'loading');
                recommendationPanel.classList.add('hidden');

                try {
                    if (!promptInput.value) {
                        throw new Error('Выберите сценарий анализа.');
                    }
                    validateDates();

                    const response = await fetch('{{ route('dashboard.analysis.recommend') }}', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                        },
                        body: JSON.stringify(requestBody()),
                    });

                    const payload = await response.json();

                    if (!response.ok) {
                        throw new Error(payload.message ?? 'Ошибка LLM');
                    }

                    recommendationMeta.textContent = `${payload.data.prompt_label} · ${payload.data.period.from} — ${payload.data.period.to} · ответов: ${payload.data.summary.total_answers}`;
                    renderRecommendationText(recommendationBody, payload.data.recommendation);
                    const historyWrap = document.getElementById('recommendation-history');
                    const historyLink = document.getElementById('recommendation-history-link');
                    if (payload.data.history_url && historyWrap && historyLink) {
                        historyLink.href = payload.data.history_url;
                        historyWrap.classList.remove('hidden');
                    } else if (historyWrap) {
                        historyWrap.classList.add('hidden');
                    }
                    recommendationPanel.classList.remove('hidden');
                    showStatus('Рекомендации получены и сохранены в истории.', 'success');
                } catch (e) {
                    showStatus(e.message, 'error');
                } finally {
                    btn.disabled = false;
                }
            });
        }

        let analysisActionsBound = false;

        function bootAnalysisPage() {
            if (analysisActionsBound || !window.DsDashboard?.createShowStatus) {
                return;
            }

            analysisActionsBound = true;
            bindAnalysisActions();
        }

        window.addEventListener('ds-dashboard-ready', bootAnalysisPage, { once: true });

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', bootAnalysisPage, { once: true });
        } else {
            bootAnalysisPage();
        }
    </script>
@endsection

@extends('layouts.app')

@section('title', 'Глубокий анализ')

@section('content')
    @include('dashboard.partials.nav')

    <div class="mb-8">
        <h1 class="text-3xl font-semibold">Глубокий анализ</h1>
        <p class="mt-1 ds-text-secondary">
            @if ($activeCompany ?? null)
                {{ $activeCompany->name }} —
            @endif
            wellbeing + прогресс задач из трекеров → рекомендации LLM
        </p>
        <div class="ds-steps mt-6">
            <div class="ds-step-card">
                <span class="ds-step-card__num">1</span>
                <span class="ds-step-card__title">Синхронизировать</span>
                <span class="ds-step-card__desc">Загрузить задачи из Jira в базу</span>
            </div>
            <div class="ds-step-card">
                <span class="ds-step-card__num">2</span>
                <span class="ds-step-card__title">Данные трекера</span>
                <span class="ds-step-card__desc">Посмотреть снимок (без LLM)</span>
            </div>
            <div class="ds-step-card">
                <span class="ds-step-card__num">3</span>
                <span class="ds-step-card__title">JSON для LLM</span>
                <span class="ds-step-card__desc">wellbeing + work_progress целиком</span>
            </div>
            <div class="ds-step-card">
                <span class="ds-step-card__num">4</span>
                <span class="ds-step-card__title">Рекомендации</span>
                <span class="ds-step-card__desc">Отправить JSON в LLM</span>
            </div>
            <a href="{{ route('dashboard.integrations') }}" class="ds-step-card ds-step-card--link sm:col-span-2 lg:col-span-1">
                <span class="ds-step-card__num">→</span>
                <span class="ds-step-card__title">Настроить интеграции</span>
                <span class="ds-step-card__desc">Подключить Jira и другие трекеры</span>
            </a>
        </div>
    </div>

    <form id="deep-analysis-form" class="space-y-10">
        @csrf
        <input type="hidden" name="prompt" id="prompt" value="{{ $defaultPrompt }}">

        <section>
            <h2 class="text-sm font-medium uppercase tracking-wider ds-text-muted">Сценарий</h2>
            <p class="mt-1 text-sm ds-text-secondary">Фокус анализа с учётом delivery и wellbeing.</p>
            <div class="mt-4 grid gap-4 md:grid-cols-3" id="prompt-cards" role="radiogroup">
                @foreach ($prompts as $prompt)
                    <button type="button"
                            class="prompt-card group flex h-full flex-col ds-panel p-5 text-left transition hover:border-[rgba(111,99,255,0.25)]
                                   {{ $defaultPrompt === $prompt['id'] ? 'prompt-card--active ds-prompt-card--active' : '' }}"
                            data-prompt-id="{{ $prompt['id'] }}"
                            aria-pressed="{{ $defaultPrompt === $prompt['id'] ? 'true' : 'false' }}">
                        <span class="prompt-card__title text-lg font-semibold text-[#111827]">{{ $prompt['label'] }}</span>
                        <span class="prompt-card__desc mt-3 text-sm leading-relaxed ds-text-secondary">{{ $prompt['description'] }}</span>
                    </button>
                @endforeach
            </div>
        </section>

        <section>
            <h2 class="text-sm font-medium uppercase tracking-wider ds-text-muted">Источники задач</h2>
            @if ($connectedIntegrations->isEmpty())
                <p class="mt-2 text-sm text-amber-400/90">Нет подключённых интеграций. <a href="{{ route('dashboard.integrations') }}" class="underline">Подключите трекер</a>.</p>
            @else
                <div class="ds-provider-grid mt-4" role="radiogroup" aria-label="Источник задач">
                    @foreach ($connectedIntegrations as $integration)
                        <label class="ds-provider-option">
                            <input
                                type="radio"
                                name="provider"
                                value="{{ $integration->provider_slug }}"
                                class="ds-provider-option__input provider-radio"
                                @checked($loop->first)
                            >
                            <span class="ds-provider-option__box">
                                <span class="ds-provider-option__name">
                                    {{ config("integrations.providers.{$integration->provider_slug}.name", $integration->provider_slug) }}
                                </span>
                                @if ($integration->last_sync_at)
                                    <span class="ds-provider-option__meta">
                                        Синхронизация {{ $integration->last_sync_at->diffForHumans() }}
                                    </span>
                                @else
                                    <span class="ds-provider-option__meta">Ещё не синхронизировался</span>
                                @endif
                            </span>
                        </label>
                    @endforeach
                </div>
            @endif
        </section>

        <section>
            <h2 class="text-sm font-medium uppercase tracking-wider ds-text-muted">Период</h2>
            <div class="mt-4 ds-panel p-5">
                <div class="flex flex-wrap gap-2">
                    <button type="button" data-preset-days="7" class="date-preset rounded-lg border border-black/[0.08] px-3 py-1.5 text-xs text-[#5F6473] hover:bg-[#F7F8FC]">7 дней</button>
                    <button type="button" data-preset-days="14" class="date-preset rounded-lg border border-black/[0.08] px-3 py-1.5 text-xs text-[#5F6473] hover:bg-[#F7F8FC]">14 дней</button>
                    <button type="button" data-preset-days="30" class="date-preset rounded-lg border border-black/[0.08] px-3 py-1.5 text-xs text-[#5F6473] hover:bg-[#F7F8FC]">30 дней</button>
                </div>
                <div class="mt-5">
                    @include('dashboard.partials.date-range', [
                        'fromValue' => $defaultFrom,
                        'toValue' => $defaultTo,
                        'fromMax' => $defaultTo,
                        'toMax' => now()->toDateString(),
                    ])
                </div>
                <p id="date-range-label" class="mt-3 text-sm ds-text-muted"></p>
            </div>

            <div class="mt-5 flex flex-wrap gap-3">
                <button type="button" id="btn-sync"
                        class="rounded-lg ds-btn-secondary !min-h-0 !px-4 !py-2 text-sm">
                    1. Синхронизировать трекеры
                </button>
                <button type="button" id="btn-trackers"
                        class="rounded-lg ds-btn-secondary !min-h-0 !px-4 !py-2 text-sm">
                    2. Данные трекера
                </button>
                <button type="button" id="btn-export"
                        class="rounded-lg ds-btn-secondary !min-h-0 !px-4 !py-2 text-sm">
                    3. JSON для LLM
                </button>
                <button type="button" id="btn-recommend"
                        class="rounded-lg ds-btn-primary !min-h-0 !px-4 !py-2 text-sm">
                    4. Получить рекомендации (LLM)
                </button>
            </div>
        </section>

        <section class="space-y-4">
            <div id="status" class="ds-status hidden" role="status" aria-live="polite"></div>
            <div id="tracker-panel" class="ds-panel hidden p-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-[#111827]">Данные трекера</h2>
                        <p id="tracker-llm-note" class="mt-1 text-xs ds-text-muted"></p>
                    </div>
                    <button type="button" id="btn-hide-trackers" class="rounded-lg ds-btn-secondary !min-h-0 !px-3 !py-1.5 text-xs">Скрыть</button>
                </div>
                <div id="tracker-summary" class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3"></div>
                <p id="tracker-warnings" class="mt-3 hidden rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800"></p>
                <pre id="tracker-json" class="mt-4 max-h-80 overflow-auto rounded-xl bg-[#F7F8FC] p-4 text-xs text-[#4E44E5]"></pre>
            </div>
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
                    <div>
                        <h2 class="text-lg font-medium">JSON для LLM (сжатый)</h2>
                        <p class="mt-1 text-xs ds-text-muted">employee_delivery_matrix + сводки — то же, что уходит в LLM на шаге 4</p>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" id="btn-export-full" class="rounded-lg ds-btn-secondary !min-h-0 !px-3 !py-1.5 text-xs">Полный JSON</button>
                        <button type="button" id="btn-copy-json" class="rounded-lg ds-btn-secondary !min-h-0 !px-3 !py-1.5 text-xs">Копировать</button>
                        <button type="button" id="btn-hide-json" class="rounded-lg ds-btn-secondary !min-h-0 !px-3 !py-1.5 text-xs">Скрыть</button>
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
    </style>

    <script>
        const promptInput = document.getElementById('prompt');
        const promptCards = document.querySelectorAll('.prompt-card');
        const dateFrom = document.getElementById('date-from');
        const dateTo = document.getElementById('date-to');
        const statusEl = document.getElementById('status');
        const jsonPanel = document.getElementById('json-panel');
        const jsonOutput = document.getElementById('json-output');
        const btnExport = document.getElementById('btn-export');
        const btnTrackers = document.getElementById('btn-trackers');
        const trackerPanel = document.getElementById('tracker-panel');
        const csrf = document.querySelector('input[name="_token"]')?.value;

        let showStatus = (message, type = 'info') => {
            statusEl.classList.remove('hidden');
            statusEl.textContent = message;
        };

        function selectedProviders() {
            const selected = document.querySelector('.provider-radio:checked');

            return selected ? [selected.value] : [];
        }

        function selectPrompt(id) {
            promptInput.value = id;
            promptCards.forEach((c) => {
                const on = c.dataset.promptId === id;
                c.classList.toggle('prompt-card--active', on);
                c.classList.toggle('ds-prompt-card--active', on);
                c.setAttribute('aria-pressed', on ? 'true' : 'false');
            });
        }
        promptCards.forEach((c) => c.addEventListener('click', () => selectPrompt(c.dataset.promptId)));
        if (promptInput.value) selectPrompt(promptInput.value);

        function body() {
            return { from: dateFrom.value, to: dateTo.value, prompt: promptInput.value, providers: selectedProviders() };
        }

        function renderRecommendationText(el, text) {
            if (window.DsDashboard?.renderRecommendation) {
                window.DsDashboard.renderRecommendation(el, text);
                return;
            }

            el.textContent = text;
        }

        function formatSyncSummary(summary) {
            if (!summary || Object.keys(summary).length === 0) return '';
            return Object.entries(summary).map(([slug, s]) =>
                `${slug}: ${s.issues_fetched ?? 0} задач, ${s.contributors} исп., закрыто ${s.tasks_closed}`
            ).join(' · ');
        }

        function renderTrackerPanel(data) {
            const note = data.llm_usage?.description ?? '';
            document.getElementById('tracker-llm-note').textContent = note;
            const summaryEl = document.getElementById('tracker-summary');
            const wp = data.work_progress ?? {};
            const team = wp.team_summary ?? {};
            const jiraRaw = data.by_provider?.jira?.raw ?? {};
            const cards = [
                { label: 'Источники', value: (wp.sources ?? []).join(', ') || '—' },
                { label: 'Задач загружено из Jira', value: team.issues_fetched ?? jiraRaw.team_summary?.issues_fetched ?? '—' },
                { label: 'Закрыто / просрочено', value: `${team.tasks_closed ?? 0} / ${team.overdue ?? 0}` },
                { label: 'Исполнителей в снимке', value: (wp.employees ?? []).length },
                { label: 'Снимок в БД', value: data.has_data ? 'да' : 'нет' },
            ];
            if (jiraRaw.meta?.jql) {
                cards.push({ label: 'JQL', value: jiraRaw.meta.jql });
            }
            summaryEl.innerHTML = cards.map((c) =>
                `<div class="rounded-xl border border-black/[0.06] bg-[#F7F8FC]/60 px-4 py-3">
                    <div class="text-xs ds-text-muted">${c.label}</div>
                    <div class="mt-1 text-lg font-semibold text-[#111827]">${c.value}</div>
                </div>`
            ).join('');
            const warnEl = document.getElementById('tracker-warnings');
            const warnings = data.integration_warnings ?? [];
            if (warnings.length) {
                warnEl.textContent = warnings.join(' ');
                warnEl.classList.remove('hidden');
            } else {
                warnEl.classList.add('hidden');
            }
            document.getElementById('tracker-json').textContent = JSON.stringify(data, null, 2);
            trackerPanel.classList.remove('hidden');
        }

        function bindDeepAnalysisActions() {
            showStatus = window.DsDashboard.createShowStatus(statusEl);

            document.getElementById('btn-sync').addEventListener('click', async () => {
                showStatus('Синхронизация с трекерами…', 'loading');
                try {
                    const r = await fetch('{{ route('dashboard.deep-analysis.sync') }}', {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                        body: JSON.stringify(body()),
                    });
                    const p = await r.json();
                    if (!r.ok && r.status !== 207) throw new Error(p.message ?? 'Ошибка');
                    const extra = formatSyncSummary(p.data?.summary);
                    showStatus(p.message + (extra ? ' — ' + extra : '') + (p.data?.errors?.length ? ' · ' + p.data.errors.join('; ') : ''), r.ok ? 'success' : 'error');
                } catch (e) { showStatus(e.message, 'error'); }
            });

            btnTrackers.addEventListener('click', async () => {
                if (!trackerPanel.classList.contains('hidden')) {
                    trackerPanel.classList.add('hidden');
                    btnTrackers.textContent = '2. Данные трекера';
                    return;
                }
                showStatus('Загрузка данных трекера…', 'loading');
                try {
                    const q = new URLSearchParams({ from: dateFrom.value, to: dateTo.value });
                    selectedProviders().forEach((p) => q.append('providers[]', p));
                    const r = await fetch(`{{ route('dashboard.deep-analysis.trackers') }}?${q}`);
                    const p = await r.json();
                    if (!r.ok) throw new Error(p.message ?? 'Ошибка');
                    renderTrackerPanel(p.data);
                    btnTrackers.textContent = 'Скрыть данные трекера';
                    showStatus(p.data.has_data ? 'Данные трекера загружены (LLM не вызывался).' : 'Нет снимка — сначала синхронизируйте трекеры.', p.data.has_data ? 'success' : 'error');
                } catch (e) { showStatus(e.message, 'error'); }
            });

            document.getElementById('btn-hide-trackers').addEventListener('click', () => {
                trackerPanel.classList.add('hidden');
                btnTrackers.textContent = '2. Данные трекера';
            });

            function setJsonVisible(v) {
                jsonPanel.classList.toggle('hidden', !v);
                btnExport.textContent = v ? 'Скрыть JSON' : '3. JSON для LLM';
            }

            async function loadPreviewJson(full = false) {
                showStatus('Загрузка…', 'loading');
                const q = new URLSearchParams({ from: dateFrom.value, to: dateTo.value, prompt: promptInput.value });
                if (full) q.set('full', '1');
                selectedProviders().forEach((p) => q.append('providers[]', p));
                const r = await fetch(`{{ route('dashboard.deep-analysis.preview') }}?${q}`);
                const p = await r.json();
                if (!r.ok) throw new Error(p.message ?? 'Ошибка');
                const payload = full && p.data_full ? p.data_full : p.data;
                jsonOutput.textContent = JSON.stringify(payload, null, 2);
                setJsonVisible(true);
                showStatus(full ? 'Полный экспорт (отладка).' : 'Сжатый JSON для LLM загружен.', 'success');
            }

            btnExport.addEventListener('click', async () => {
                if (!jsonPanel.classList.contains('hidden')) { setJsonVisible(false); return; }
                try { await loadPreviewJson(false); } catch (e) { showStatus(e.message, 'error'); }
            });

            document.getElementById('btn-export-full').addEventListener('click', async () => {
                try { await loadPreviewJson(true); } catch (e) { showStatus(e.message, 'error'); }
            });

            document.getElementById('btn-hide-json').addEventListener('click', () => setJsonVisible(false));
            document.getElementById('btn-copy-json').addEventListener('click', async () => {
                await navigator.clipboard.writeText(jsonOutput.textContent);
                showStatus('Скопировано.', 'success');
            });

            document.getElementById('btn-recommend').addEventListener('click', async () => {
                const btn = document.getElementById('btn-recommend');
                btn.disabled = true;
                showStatus('Отправка данных в LLM… Это может занять до минуту.', 'loading');
                document.getElementById('recommendation-panel').classList.add('hidden');
                try {
                    const r = await fetch('{{ route('dashboard.deep-analysis.recommend') }}', {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                        body: JSON.stringify({ ...body(), sync_first: false }),
                    });
                    const p = await r.json();
                    if (!r.ok) throw new Error(p.message ?? 'Ошибка');
                    document.getElementById('recommendation-meta').textContent = `${p.data.prompt_label} · ${p.data.period.from} — ${p.data.period.to}`;
                    renderRecommendationText(
                        document.getElementById('recommendation-body'),
                        p.data.recommendation,
                    );
                    const historyWrap = document.getElementById('recommendation-history');
                    const historyLink = document.getElementById('recommendation-history-link');
                    if (p.data.history_url && historyWrap && historyLink) {
                        historyLink.href = p.data.history_url;
                        historyWrap.classList.remove('hidden');
                    } else if (historyWrap) {
                        historyWrap.classList.add('hidden');
                    }
                    document.getElementById('recommendation-panel').classList.remove('hidden');
                    showStatus('Рекомендации получены и сохранены в истории.', 'success');
                } catch (e) { showStatus(e.message, 'error'); }
                finally { btn.disabled = false; }
            });
        }

        let deepAnalysisActionsBound = false;

        function bootDeepAnalysisPage() {
            if (deepAnalysisActionsBound || !window.DsDashboard?.createShowStatus) {
                return;
            }

            deepAnalysisActionsBound = true;
            bindDeepAnalysisActions();
        }

        window.addEventListener('ds-dashboard-ready', bootDeepAnalysisPage, { once: true });

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', bootDeepAnalysisPage, { once: true });
        } else {
            bootDeepAnalysisPage();
        }
    </script>
@endsection

@extends('layouts.app')

@section('title', 'Глубокий анализ')

@section('content')
    @include('dashboard.partials.nav')

    <div class="mb-8">
        <h1 class="text-3xl font-semibold">Глубокий анализ</h1>
        <p class="mt-1 text-slate-400">
            @if ($activeCompany ?? null)
                {{ $activeCompany->name }} —
            @endif
            wellbeing + прогресс задач из трекеров → рекомендации LLM
        </p>
        <ol class="mt-3 list-inside list-decimal text-sm text-slate-400">
            <li><strong class="font-medium text-slate-300">Синхронизировать</strong> — загрузить задачи из Jira в базу</li>
            <li><strong class="font-medium text-slate-300">Данные трекера</strong> — посмотреть снимок (без LLM)</li>
            <li><strong class="font-medium text-slate-300">JSON для LLM</strong> — wellbeing + work_progress целиком</li>
            <li><strong class="font-medium text-slate-300">Рекомендации</strong> — отправить JSON в LLM</li>
        </ol>
        <p class="mt-2 text-sm">
            <a href="{{ route('dashboard.integrations') }}" class="text-indigo-400 hover:text-indigo-300">Настроить интеграции →</a>
        </p>
    </div>

    <form id="deep-analysis-form" class="space-y-10">
        @csrf
        <input type="hidden" name="prompt" id="prompt" value="{{ $defaultPrompt }}">

        <section>
            <h2 class="text-sm font-medium uppercase tracking-wider text-slate-500">Сценарий</h2>
            <p class="mt-1 text-sm text-slate-400">Фокус анализа с учётом delivery и wellbeing.</p>
            <div class="mt-4 grid gap-4 md:grid-cols-3" id="prompt-cards" role="radiogroup">
                @foreach ($prompts as $prompt)
                    <button type="button"
                            class="prompt-card group flex h-full flex-col rounded-2xl border border-slate-800 bg-slate-900/60 p-5 text-left transition hover:border-slate-600 hover:bg-slate-900
                                   {{ $defaultPrompt === $prompt['id'] ? 'prompt-card--active' : '' }}"
                            data-prompt-id="{{ $prompt['id'] }}"
                            aria-pressed="{{ $defaultPrompt === $prompt['id'] ? 'true' : 'false' }}">
                        <span class="prompt-card__title text-lg font-semibold text-white">{{ $prompt['label'] }}</span>
                        <span class="prompt-card__desc mt-3 text-sm leading-relaxed text-slate-400">{{ $prompt['description'] }}</span>
                    </button>
                @endforeach
            </div>
        </section>

        <section>
            <h2 class="text-sm font-medium uppercase tracking-wider text-slate-500">Источники задач</h2>
            @if ($connectedIntegrations->isEmpty())
                <p class="mt-2 text-sm text-amber-400/90">Нет подключённых интеграций. <a href="{{ route('dashboard.integrations') }}" class="underline">Подключите трекер</a>.</p>
            @else
                <div class="mt-3 flex flex-wrap gap-4">
                    @foreach ($connectedIntegrations as $integration)
                        <label class="flex items-center gap-2 text-sm text-slate-300">
                            <input type="checkbox" name="providers[]" value="{{ $integration->provider_slug }}"
                                   class="provider-checkbox rounded border-slate-600 bg-slate-950 text-indigo-600" checked>
                            {{ config("integrations.providers.{$integration->provider_slug}.name", $integration->provider_slug) }}
                            @if ($integration->last_sync_at)
                                <span class="text-xs text-slate-500">({{ $integration->last_sync_at->diffForHumans() }})</span>
                            @endif
                        </label>
                    @endforeach
                </div>
            @endif
        </section>

        <section>
            <h2 class="text-sm font-medium uppercase tracking-wider text-slate-500">Период</h2>
            <div class="mt-4 rounded-2xl border border-slate-800 bg-slate-900 p-5">
                <div class="flex flex-wrap gap-2">
                    <button type="button" data-preset-days="7" class="date-preset rounded-lg border border-slate-700 px-3 py-1.5 text-xs text-slate-300 hover:bg-slate-800">7 дней</button>
                    <button type="button" data-preset-days="14" class="date-preset rounded-lg border border-slate-700 px-3 py-1.5 text-xs text-slate-300 hover:bg-slate-800">14 дней</button>
                    <button type="button" data-preset-days="30" class="date-preset rounded-lg border border-slate-700 px-3 py-1.5 text-xs text-slate-300 hover:bg-slate-800">30 дней</button>
                </div>
                <div class="mt-5 flex flex-wrap items-end gap-4">
                    <div>
                        <label for="date-from" class="mb-1 block text-xs text-slate-400">С</label>
                        <input type="date" id="date-from" value="{{ $defaultFrom }}" max="{{ $defaultTo }}"
                               class="rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white [color-scheme:dark]">
                    </div>
                    <div>
                        <label for="date-to" class="mb-1 block text-xs text-slate-400">По</label>
                        <input type="date" id="date-to" value="{{ $defaultTo }}" max="{{ now()->toDateString() }}"
                               class="rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white [color-scheme:dark]">
                    </div>
                </div>
                <p id="date-range-label" class="mt-3 text-sm text-slate-500"></p>
            </div>

            <div class="mt-5 flex flex-wrap gap-3">
                <button type="button" id="btn-sync"
                        class="rounded-lg border border-slate-600 px-4 py-2 text-sm hover:bg-slate-800">
                    1. Синхронизировать трекеры
                </button>
                <button type="button" id="btn-trackers"
                        class="rounded-lg border border-emerald-700/60 px-4 py-2 text-sm text-emerald-100 hover:bg-emerald-950/40">
                    2. Данные трекера
                </button>
                <button type="button" id="btn-export"
                        class="rounded-lg border border-slate-600 px-4 py-2 text-sm hover:bg-slate-800">
                    3. JSON для LLM
                </button>
                <button type="button" id="btn-recommend"
                        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold hover:bg-indigo-500">
                    4. Получить рекомендации (LLM)
                </button>
            </div>
        </section>

        <section class="space-y-4">
            <div id="status" class="hidden rounded-xl border px-4 py-3 text-sm"></div>
            <div id="tracker-panel" class="hidden rounded-2xl border border-emerald-800/40 bg-slate-900 p-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-medium text-emerald-200">Данные трекера</h2>
                        <p id="tracker-llm-note" class="mt-1 text-xs text-slate-500"></p>
                    </div>
                    <button type="button" id="btn-hide-trackers" class="rounded-lg border border-slate-600 px-3 py-1.5 text-xs hover:bg-slate-800">Скрыть</button>
                </div>
                <div id="tracker-summary" class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3"></div>
                <p id="tracker-warnings" class="mt-3 hidden text-sm text-amber-300/90"></p>
                <pre id="tracker-json" class="mt-4 max-h-80 overflow-auto rounded-xl bg-slate-950 p-4 text-xs text-emerald-100"></pre>
            </div>
            <div id="recommendation-panel" class="hidden rounded-2xl border border-indigo-500/30 bg-slate-900 p-6">
                <h2 class="text-lg font-medium text-indigo-200">Рекомендации</h2>
                <div id="recommendation-meta" class="mt-1 text-xs text-slate-500"></div>
                <div id="recommendation-body" class="mt-4 whitespace-pre-wrap text-sm leading-relaxed text-slate-200"></div>
            </div>
            <div id="json-panel" class="hidden rounded-2xl border border-slate-800 bg-slate-900 p-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-medium">JSON для LLM (сжатый)</h2>
                        <p class="mt-1 text-xs text-slate-500">employee_delivery_matrix + сводки — то же, что уходит в LLM на шаге 4</p>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" id="btn-export-full" class="rounded-lg border border-slate-600 px-3 py-1.5 text-xs hover:bg-slate-800">Полный JSON</button>
                        <button type="button" id="btn-copy-json" class="rounded-lg border border-slate-600 px-3 py-1.5 text-xs hover:bg-slate-800">Копировать</button>
                        <button type="button" id="btn-hide-json" class="rounded-lg border border-slate-600 px-3 py-1.5 text-xs hover:bg-slate-800">Скрыть</button>
                    </div>
                </div>
                <pre id="json-output" class="mt-4 max-h-[32rem] overflow-auto rounded-xl bg-slate-950 p-4 text-xs text-violet-100"></pre>
            </div>
        </section>
    </form>

    <style>
        .date-preset--active { border-color: rgb(99 102 241); background: rgb(99 102 241 / 0.15); color: rgb(199 210 254); }
        .prompt-card--active { border-color: rgb(139 92 246 / 0.7); background: linear-gradient(145deg, rgb(46 16 101 / 0.5), rgb(15 15 30 / 0.9)); box-shadow: 0 0 0 1px rgb(139 92 246 / 0.3); }
        .prompt-card--active .prompt-card__title { color: rgb(221 214 254); }
        .prompt-card--active .prompt-card__desc { color: rgb(196 181 253 / 0.85); }
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

        function selectedProviders() {
            return [...document.querySelectorAll('.provider-checkbox:checked')].map((el) => el.value);
        }

        function selectPrompt(id) {
            promptInput.value = id;
            promptCards.forEach((c) => {
                const on = c.dataset.promptId === id;
                c.classList.toggle('prompt-card--active', on);
                c.setAttribute('aria-pressed', on ? 'true' : 'false');
            });
        }
        promptCards.forEach((c) => c.addEventListener('click', () => selectPrompt(c.dataset.promptId)));
        if (promptInput.value) selectPrompt(promptInput.value);

        function body() {
            return { from: dateFrom.value, to: dateTo.value, prompt: promptInput.value, providers: selectedProviders() };
        }

        function showStatus(msg, type = 'info') {
            statusEl.classList.remove('hidden');
            statusEl.className = 'rounded-xl border px-4 py-3 text-sm ' + (
                type === 'error' ? 'border-red-800 bg-red-950/40 text-red-200' :
                type === 'loading' ? 'border-indigo-800 bg-indigo-950/40 text-indigo-200' :
                type === 'success' ? 'border-emerald-800 bg-emerald-950/30 text-emerald-200' :
                'border-slate-700 bg-slate-800/40 text-slate-300'
            );
            statusEl.textContent = msg;
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
                `<div class="rounded-xl border border-slate-800 bg-slate-950/60 px-4 py-3">
                    <div class="text-xs text-slate-500">${c.label}</div>
                    <div class="mt-1 text-lg font-semibold text-white">${c.value}</div>
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
            showStatus('LLM…', 'loading');
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
                document.getElementById('recommendation-body').textContent = p.data.recommendation;
                document.getElementById('recommendation-panel').classList.remove('hidden');
                showStatus('Готово.', 'success');
            } catch (e) { showStatus(e.message, 'error'); }
            finally { btn.disabled = false; }
        });
    </script>
@endsection

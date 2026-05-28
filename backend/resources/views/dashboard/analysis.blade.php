@extends('layouts.app')

@section('title', 'Анализ')

@section('content')
    @include('dashboard.partials.nav')

    <div class="mb-8">
        <h1 class="text-3xl font-semibold">AI-анализ wellbeing</h1>
        <p class="mt-1 text-slate-400">
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
            <h2 class="text-sm font-medium uppercase tracking-wider text-slate-500">Сценарий анализа</h2>
            <p class="mt-1 text-sm text-slate-400">Выберите одну из ролей — от неё зависит фокус рекомендаций LLM.</p>

            <div class="mt-4 grid gap-4 md:grid-cols-3" id="prompt-cards" role="radiogroup" aria-label="Сценарий анализа">
                @forelse ($prompts as $prompt)
                    <button type="button"
                            class="prompt-card group flex h-full flex-col rounded-2xl border border-slate-800 bg-slate-900/60 p-5 text-left transition hover:border-slate-600 hover:bg-slate-900
                                   {{ $defaultPrompt === $prompt['id'] ? 'prompt-card--active' : '' }}"
                            data-prompt-id="{{ $prompt['id'] }}"
                            aria-pressed="{{ $defaultPrompt === $prompt['id'] ? 'true' : 'false' }}">
                        <span class="prompt-card__title text-lg font-semibold text-white group-hover:text-violet-100">
                            {{ $prompt['label'] }}
                        </span>
                        <span class="prompt-card__desc mt-3 text-sm leading-relaxed text-slate-400 group-hover:text-slate-300">
                            {{ $prompt['description'] }}
                        </span>
                    </button>
                @empty
                    <p class="col-span-3 rounded-xl border border-dashed border-slate-700 p-6 text-center text-slate-500">
                        Сценарии не загружены. Выполните <code class="text-violet-300">php artisan config:clear</code>
                    </p>
                @endforelse
            </div>
        </section>

        <section>
            <h2 class="text-sm font-medium uppercase tracking-wider text-slate-500">Период данных</h2>
            <p class="mt-1 text-sm text-slate-400">Укажите диапазон дат для анализа ответов сотрудников.</p>

            <div class="mt-4 rounded-2xl border border-slate-800 bg-slate-900 p-5">
                <div class="flex flex-wrap gap-2">
                    <button type="button" data-preset-days="7"
                            class="date-preset rounded-lg border border-slate-700 px-3 py-1.5 text-xs text-slate-300 hover:bg-slate-800">
                        7 дней
                    </button>
                    <button type="button" data-preset-days="14"
                            class="date-preset rounded-lg border border-slate-700 px-3 py-1.5 text-xs text-slate-300 hover:bg-slate-800">
                        14 дней
                    </button>
                    <button type="button" data-preset-days="30"
                            class="date-preset rounded-lg border border-slate-700 px-3 py-1.5 text-xs text-slate-300 hover:bg-slate-800">
                        30 дней
                    </button>
                </div>

                <div class="mt-5 flex flex-wrap items-end gap-4">
                    <div>
                        <label for="date-from" class="mb-1 block text-xs text-slate-400">С</label>
                        <input type="date" id="date-from" name="from" value="{{ $defaultFrom }}"
                               max="{{ $defaultTo }}"
                               class="rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white [color-scheme:dark]">
                    </div>
                    <div>
                        <label for="date-to" class="mb-1 block text-xs text-slate-400">По</label>
                        <input type="date" id="date-to" name="to" value="{{ $defaultTo }}"
                               max="{{ now()->toDateString() }}"
                               class="rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white [color-scheme:dark]">
                    </div>
                </div>
                <p id="date-range-label" class="mt-3 text-sm text-slate-500"></p>
            </div>

            <div class="mt-5 flex flex-wrap gap-3">
                <button type="button" id="btn-export"
                        class="rounded-lg border border-slate-600 px-4 py-2 text-sm hover:bg-slate-800">
                    Показать JSON данных
                </button>
                <button type="button" id="btn-recommend"
                        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold hover:bg-indigo-500">
                    Получить рекомендации
                </button>
            </div>
        </section>

        <section class="space-y-4">
            <div id="status" class="hidden rounded-xl border px-4 py-3 text-sm"></div>

            <div id="recommendation-panel" class="hidden rounded-2xl border border-indigo-500/30 bg-slate-900 p-6">
                <h2 class="text-lg font-medium text-indigo-200">Рекомендации</h2>
                <div id="recommendation-meta" class="mt-1 text-xs text-slate-500"></div>
                <div id="recommendation-body" class="mt-4 whitespace-pre-wrap text-sm leading-relaxed text-slate-200"></div>
            </div>

            <div id="json-panel" class="hidden rounded-2xl border border-slate-800 bg-slate-900 p-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-lg font-medium">JSON для LLM</h2>
                    <div class="flex gap-2">
                        <button type="button" id="btn-copy-json"
                                class="rounded-lg border border-slate-600 px-3 py-1.5 text-xs hover:bg-slate-800">
                            Копировать
                        </button>
                        <button type="button" id="btn-hide-json"
                                class="rounded-lg border border-slate-600 px-3 py-1.5 text-xs hover:bg-slate-800">
                            Скрыть
                        </button>
                    </div>
                </div>
                <pre id="json-output" class="mt-4 max-h-[32rem] overflow-auto rounded-xl bg-slate-950 p-4 text-xs text-violet-100"></pre>
            </div>
        </section>
    </form>

    <style>
        .date-preset--active {
            border-color: rgb(99 102 241);
            background: rgb(99 102 241 / 0.15);
            color: rgb(199 210 254);
        }

        .prompt-card--active {
            border-color: rgb(139 92 246 / 0.7);
            background: linear-gradient(145deg, rgb(46 16 101 / 0.5), rgb(15 15 30 / 0.9));
            box-shadow: 0 0 0 1px rgb(139 92 246 / 0.3);
        }

        .prompt-card--active .prompt-card__title {
            color: rgb(221 214 254);
        }

        .prompt-card--active .prompt-card__desc {
            color: rgb(196 181 253 / 0.85);
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

        function selectPrompt(promptId) {
            promptInput.value = promptId;
            promptCards.forEach((card) => {
                const isActive = card.dataset.promptId === promptId;
                card.classList.toggle('prompt-card--active', isActive);
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
            updateDateConstraints();
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

        function showStatus(message, type = 'info') {
            statusEl.classList.remove('hidden', 'border-red-800', 'bg-red-950/40', 'text-red-200', 'border-indigo-800', 'bg-indigo-950/40', 'text-indigo-200', 'border-emerald-800', 'bg-emerald-950/30', 'text-emerald-200', 'border-slate-700', 'bg-slate-800/40', 'text-slate-300');
            if (type === 'error') {
                statusEl.classList.add('border-red-800', 'bg-red-950/40', 'text-red-200');
            } else if (type === 'loading') {
                statusEl.classList.add('border-indigo-800', 'bg-indigo-950/40', 'text-indigo-200');
            } else if (type === 'success') {
                statusEl.classList.add('border-emerald-800', 'bg-emerald-950/30', 'text-emerald-200');
            } else {
                statusEl.classList.add('border-slate-700', 'bg-slate-800/40', 'text-slate-300');
            }
            statusEl.textContent = message;
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
                recommendationBody.textContent = payload.data.recommendation;
                recommendationPanel.classList.remove('hidden');
                showStatus('Рекомендации получены.', 'success');
            } catch (e) {
                showStatus(e.message, 'error');
            } finally {
                btn.disabled = false;
            }
        });
    </script>
@endsection

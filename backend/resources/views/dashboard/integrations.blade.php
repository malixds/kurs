@extends('layouts.app')

@section('title', 'Интеграции')

@section('content')
    @include('dashboard.partials.nav')

    <div class="mb-8">
        <h1 class="text-3xl font-semibold">Интеграции</h1>
        <p class="mt-1 ds-text-secondary">
            @if ($activeCompany ?? null)
                {{ $activeCompany->name }} —
            @endif
            подключите трекеры задач для глубокого анализа
        </p>
    </div>

    @if (session('status'))
        <div class="mb-6 rounded-xl border border-emerald-800 bg-emerald-950/30 px-4 py-3 text-sm text-emerald-200">
            {{ session('status') }}
        </div>
    @endif

    <div class="space-y-6">
        @foreach ($providers as $provider)
            @php
                $integration = $integrations->get($provider['slug']);
                $connected = $integration?->isConnected();
                $creds = $integration?->credentials ?? [];
            @endphp
            <div class="ds-panel p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-medium">{{ $provider['name'] }}</h2>
                        <p class="mt-1 text-sm ds-text-secondary">
                            @if ($connected)
                                <span class="text-emerald-400">Подключено</span>
                                @if ($integration->last_sync_at)
                                    · синхр. {{ $integration->last_sync_at->diffForHumans() }}
                                @endif
                            @else
                                <span class="ds-text-muted">Не подключено</span>
                            @endif
                        </p>
                        @if ($integration?->hasStaleEncryptedCredentials())
                            <p class="mt-2 text-sm text-amber-300">
                                Не удалось прочитать сохранённые токены (часто после смены <code class="text-amber-200">APP_KEY</code>).
                                Введите API token заново и нажмите «Сохранить».
                            </p>
                        @endif
                        @if ($integration?->last_error)
                            <p class="mt-2 text-sm text-red-400">{{ $integration->last_error }}</p>
                        @endif
                    </div>
                    @if ($connected)
                        <form method="POST" action="{{ route('dashboard.integrations.destroy', $provider['slug']) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded-lg ds-btn-secondary !min-h-0 !px-3 !py-1.5 text-xs">
                                Отключить
                            </button>
                        </form>
                    @endif
                </div>

                <form method="POST" action="{{ route('dashboard.integrations.store', $provider['slug']) }}"
                      class="integration-form mt-5 space-y-4" data-provider="{{ $provider['slug'] }}">
                    @csrf
                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach ($provider['config_schema'] as $field)
                            <div class="{{ ($field['type'] ?? '') === 'select' ? 'sm:col-span-2' : '' }}">
                                <label class="mb-1 block text-xs ds-text-secondary">{{ $field['label'] }}</label>
                                @if (($field['type'] ?? 'text') === 'select')
                                    <select name="{{ $field['key'] }}"
                                            class="w-full rounded-lg ds-input !min-h-0 !py-2 text-sm">
                                        @foreach ($field['options'] ?? [] as $val => $label)
                                            <option value="{{ $val }}" @selected(($creds[$field['key']] ?? '') === $val)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <input type="{{ $field['type'] ?? 'text' }}"
                                           name="{{ $field['key'] }}"
                                           value="{{ $field['type'] === 'password' ? '' : ($creds[$field['key']] ?? '') }}"
                                           placeholder="{{ $field['type'] === 'password' && $connected ? '••••••••' : '' }}"
                                           class="w-full rounded-lg ds-input !min-h-0 !py-2 text-sm"
                                           @if ($field['required'] ?? false) required @endif>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <button type="button"
                                class="btn-test rounded-lg ds-btn-secondary !min-h-0 !px-4 !py-2 text-sm">
                            Проверить подключение
                        </button>
                        <button type="submit" class="rounded-lg ds-btn-primary !min-h-0 !px-4 !py-2 text-sm">
                            Сохранить
                        </button>
                    </div>
                    <p class="test-result hidden text-sm"></p>
                </form>
            </div>
        @endforeach
    </div>

    <div class="mt-10 ds-panel p-6">
        <h2 class="text-lg font-medium">Маппинг сотрудников</h2>
        <p class="mt-1 text-sm ds-text-secondary">Связи создаются автоматически при синхронизации по email. Ниже — текущие привязки.</p>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="ds-text-secondary">
                    <tr>
                        <th class="px-3 py-2">Сотрудник</th>
                        <th class="px-3 py-2">Email</th>
                        <th class="px-3 py-2">Интеграции</th>
                    </tr>
                </thead>
                <tbody class="text-[#5F6473]">
                    @forelse ($employees as $employee)
                        <tr class="border-t border-black/[0.06]">
                            <td class="px-3 py-2">{{ $employee->name }}</td>
                            <td class="px-3 py-2">{{ $employee->email ?? '—' }}</td>
                            <td class="px-3 py-2 text-xs ds-text-muted">
                                @php $empIds = $identities->get($employee->id) ?? collect(); @endphp
                                @forelse ($empIds as $identity)
                                    {{ $identity->companyIntegration->provider_slug }} ({{ $identity->external_login ?? $identity->external_user_id }})
                                    @if (! $loop->last), @endif
                                @empty
                                    —
                                @endforelse
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-3 py-4 ds-text-muted">Нет сотрудников с ответами.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <script>
        document.querySelectorAll('.integration-form').forEach((form) => {
            const resultEl = form.querySelector('.test-result');
            form.querySelector('.btn-test')?.addEventListener('click', async () => {
                const provider = form.dataset.provider;
                const data = Object.fromEntries(new FormData(form));
                resultEl.classList.remove('hidden', 'text-red-400', 'text-emerald-400');
                resultEl.textContent = 'Проверка…';
                try {
                    const response = await fetch(`{{ url('/dashboard/integrations') }}/${provider}/test`, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify(data),
                    });
                    const payload = await response.json();
                    resultEl.textContent = payload.message ?? (payload.ok ? 'OK' : 'Ошибка');
                    resultEl.classList.toggle('text-emerald-400', payload.ok);
                    resultEl.classList.toggle('text-red-400', !payload.ok);
                } catch (e) {
                    resultEl.textContent = e.message;
                    resultEl.classList.add('text-red-400');
                }
            });
        });
    </script>
@endsection

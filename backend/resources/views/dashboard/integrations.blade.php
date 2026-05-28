@extends('layouts.app')

@section('title', 'Интеграции')

@section('content')
    @include('dashboard.partials.nav')

    <div class="mb-8">
        <h1 class="text-3xl font-semibold">Интеграции</h1>
        <p class="mt-1 text-slate-400">
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
            <div class="rounded-2xl border border-slate-800 bg-slate-900 p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-medium">{{ $provider['name'] }}</h2>
                        <p class="mt-1 text-sm text-slate-400">
                            @if ($connected)
                                <span class="text-emerald-400">Подключено</span>
                                @if ($integration->last_sync_at)
                                    · синхр. {{ $integration->last_sync_at->diffForHumans() }}
                                @endif
                            @else
                                <span class="text-slate-500">Не подключено</span>
                            @endif
                        </p>
                        @if ($integration?->last_error)
                            <p class="mt-2 text-sm text-red-400">{{ $integration->last_error }}</p>
                        @endif
                    </div>
                    @if ($connected)
                        <form method="POST" action="{{ route('dashboard.integrations.destroy', $provider['slug']) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded-lg border border-slate-600 px-3 py-1.5 text-xs hover:bg-slate-800">
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
                                <label class="mb-1 block text-xs text-slate-400">{{ $field['label'] }}</label>
                                @if (($field['type'] ?? 'text') === 'select')
                                    <select name="{{ $field['key'] }}"
                                            class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white [color-scheme:dark]">
                                        @foreach ($field['options'] ?? [] as $val => $label)
                                            <option value="{{ $val }}" @selected(($creds[$field['key']] ?? '') === $val)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <input type="{{ $field['type'] ?? 'text' }}"
                                           name="{{ $field['key'] }}"
                                           value="{{ $field['type'] === 'password' ? '' : ($creds[$field['key']] ?? '') }}"
                                           placeholder="{{ $field['type'] === 'password' && $connected ? '••••••••' : '' }}"
                                           class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
                                           @if ($field['required'] ?? false) required @endif>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <button type="button"
                                class="btn-test rounded-lg border border-slate-600 px-4 py-2 text-sm hover:bg-slate-800">
                            Проверить подключение
                        </button>
                        <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold hover:bg-indigo-500">
                            Сохранить
                        </button>
                    </div>
                    <p class="test-result hidden text-sm"></p>
                </form>
            </div>
        @endforeach
    </div>

    <div class="mt-10 rounded-2xl border border-slate-800 bg-slate-900 p-6">
        <h2 class="text-lg font-medium">Маппинг сотрудников</h2>
        <p class="mt-1 text-sm text-slate-400">Связи создаются автоматически при синхронизации по email. Ниже — текущие привязки.</p>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="text-slate-400">
                    <tr>
                        <th class="px-3 py-2">Сотрудник</th>
                        <th class="px-3 py-2">Email</th>
                        <th class="px-3 py-2">Интеграции</th>
                    </tr>
                </thead>
                <tbody class="text-slate-300">
                    @forelse ($employees as $employee)
                        <tr class="border-t border-slate-800">
                            <td class="px-3 py-2">{{ $employee->name }}</td>
                            <td class="px-3 py-2">{{ $employee->email ?? '—' }}</td>
                            <td class="px-3 py-2 text-xs text-slate-500">
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
                            <td colspan="3" class="px-3 py-4 text-slate-500">Нет сотрудников с ответами.</td>
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

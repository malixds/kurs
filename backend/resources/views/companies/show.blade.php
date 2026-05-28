@extends('layouts.app')

@section('title', $company->name)

@section('content')
    <div class="mb-6">
        <a href="{{ route('companies.index') }}" class="text-sm text-indigo-400 hover:text-indigo-300">
            ← Все компании
        </a>
    </div>

    <div class="max-w-2xl">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-3xl font-semibold">{{ $company->name }}</h1>
                <p class="mt-2 text-sm text-slate-400">
                    Изолированное рабочее пространство. Данные не связаны с другими компаниями.
                </p>
            </div>
            @if ($isActive)
                <span class="rounded-full bg-emerald-500/15 px-3 py-1 text-xs font-medium text-emerald-300">
                    Активная
                </span>
            @endif
        </div>

        <div class="mt-8 rounded-2xl border border-violet-500/20 bg-violet-950/20 p-5">
            <p class="text-xs uppercase tracking-wider text-violet-300/80">Secret key для расширения</p>
            <div class="mt-3 flex flex-col gap-3 sm:flex-row">
                <code id="company-secret-key" class="flex-1 break-all rounded-xl border border-slate-800 bg-slate-950 px-4 py-3 font-mono text-sm text-violet-100">
                    {{ $company->secret_key }}
                </code>
                <button type="button" id="copy-company-key"
                        class="rounded-xl border border-violet-500/30 bg-violet-600/20 px-4 py-3 text-sm font-medium text-violet-100 hover:bg-violet-600/30">
                    Копировать
                </button>
            </div>
            <p id="copy-company-status" class="mt-2 hidden text-sm text-emerald-300">Скопировано</p>
        </div>

        <div class="mt-6 rounded-2xl border border-slate-800 bg-slate-900 p-5 text-sm text-slate-400">
            <p class="font-medium text-slate-200">Как использовать</p>
            <ol class="mt-3 list-decimal space-y-2 pl-5">
                <li>Скопируйте secret key в настройки расширения Chrome</li>
                <li>Укажите Employee ID каждого сотрудника</li>
                <li>Открывайте dashboard только этой компании через кнопку ниже</li>
            </ol>
        </div>

        <div class="mt-8 flex flex-wrap gap-3">
            @if (! $isActive)
                <form method="POST" action="{{ route('companies.switch', $company) }}">
                    @csrf
                    <button type="submit"
                            class="rounded-xl bg-indigo-600 px-6 py-3 text-sm font-semibold hover:bg-indigo-500">
                        Сделать активной и открыть dashboard
                    </button>
                </form>
            @else
                <a href="{{ route('dashboard.index') }}"
                   class="rounded-xl bg-indigo-600 px-6 py-3 text-sm font-semibold hover:bg-indigo-500">
                    Перейти в dashboard
                </a>
            @endif
        </div>
    </div>

    <script>
        document.getElementById('copy-company-key')?.addEventListener('click', async () => {
            const key = document.getElementById('company-secret-key')?.textContent?.trim();
            if (!key) return;
            await navigator.clipboard.writeText(key);
            document.getElementById('copy-company-status')?.classList.remove('hidden');
        });
    </script>
@endsection

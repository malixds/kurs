@extends('layouts.app')

@section('title', $company->name)

@section('content')
    <div class="mb-6">
        <a href="{{ route('companies.index') }}" class="ds-link text-sm">
            ← Все компании
        </a>
    </div>

    <div class="max-w-2xl">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-[#111827]">{{ $company->name }}</h1>
                <p class="mt-2 text-sm ds-text-secondary">
                    Изолированное рабочее пространство. Данные не связаны с другими компаниями.
                </p>
            </div>
            @if ($isActive)
                <span class="ds-badge !text-emerald-700 !bg-emerald-50 !border-emerald-200">
                    Активная
                </span>
            @endif
        </div>

        <div class="ds-card mt-8">
            <p class="text-xs font-medium uppercase tracking-wider ds-text-muted">Secret key для расширения</p>
            <div class="mt-3 flex flex-col gap-3 sm:flex-row">
                <code id="company-secret-key" class="ds-input flex-1 break-all font-mono text-sm !bg-[#F7F8FC] text-[#4E44E5]">
                    {{ $company->secret_key }}
                </code>
                <button type="button" id="copy-company-key" class="ds-btn-secondary shrink-0">
                    Копировать
                </button>
            </div>
            <p id="copy-company-status" class="mt-2 hidden text-sm font-medium text-emerald-600">Скопировано</p>
        </div>

        <div class="ds-panel mt-6 p-5 text-sm ds-text-secondary">
            <p class="font-semibold text-[#111827]">Как использовать</p>
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
                    <button type="submit" class="ds-btn-primary">
                        Сделать активной и открыть dashboard
                    </button>
                </form>
            @else
                <a href="{{ route('dashboard.index') }}" class="ds-btn-primary">
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

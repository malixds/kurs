@extends('layouts.onboarding')

@section('title', 'Компания подключена')

@section('content')
    <div class="mx-auto max-w-2xl pt-8 lg:pt-12">
        <div class="ds-card text-center">
            <p class="ds-badge mx-auto">Готово</p>
            <h1 class="mt-4 text-3xl font-bold tracking-tight text-[#111827]">Компания «{{ $companyName }}» создана</h1>
            <p class="mt-3 text-sm leading-relaxed text-[#5F6473]">
                Сохраните секретный ключ для расширения. Его всегда можно посмотреть на странице «Мои компании».
            </p>
        </div>

        <div class="ds-card mt-8">
            <label class="mb-2 block text-xs font-medium uppercase tracking-wider text-[#8C92A3]">Secret key</label>
            <div class="flex flex-col gap-3 sm:flex-row">
                <code id="secret-key" class="ds-input flex-1 break-all font-mono text-sm !bg-[#F7F8FC]">
                    {{ $secretKey }}
                </code>
                <button type="button" id="copy-key" class="ds-btn-secondary shrink-0">
                    Копировать
                </button>
            </div>
            <p id="copy-status" class="mt-2 hidden text-sm font-medium text-emerald-600">Скопировано в буфер обмена</p>
        </div>

        <div class="ds-card mt-8 text-sm text-[#5F6473]">
            <p class="font-semibold text-[#111827]">Следующие шаги</p>
            <ol class="mt-3 list-decimal space-y-2 pl-5">
                <li>Установите расширение Wellbeing Check-in в Chrome</li>
                <li>Откройте настройки расширения и вставьте secret key</li>
                <li>Укажите Employee ID сотрудника и сохраните</li>
            </ol>
        </div>

        <div class="mt-8 flex flex-col items-center gap-3 sm:flex-row sm:justify-center">
            <a href="{{ route('dashboard.index') }}" class="ds-btn-primary">
                Перейти в dashboard
            </a>
            <a href="{{ route('companies.index') }}" class="ds-btn-secondary">
                Все компании
            </a>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.getElementById('copy-key')?.addEventListener('click', async () => {
            const key = document.getElementById('secret-key')?.textContent?.trim();

            if (!key) {
                return;
            }

            await navigator.clipboard.writeText(key);

            const status = document.getElementById('copy-status');
            status?.classList.remove('hidden');
        });
    </script>
@endpush

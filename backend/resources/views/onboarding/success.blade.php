@extends('layouts.onboarding')

@section('title', 'Компания подключена')

@section('content')
    <div class="mx-auto max-w-2xl">
        <div class="rounded-2xl border border-emerald-500/20 bg-emerald-950/20 p-6 text-center">
            <p class="text-sm font-medium uppercase tracking-wider text-emerald-300">Готово</p>
            <h1 class="mt-2 text-3xl font-semibold text-white">Компания «{{ $companyName }}» создана</h1>
            <p class="mt-3 text-sm text-slate-400">
                Сохраните секретный ключ для расширения. Его всегда можно посмотреть на странице «Мои компании».
            </p>
        </div>

        <div class="mt-8 rounded-2xl border border-violet-500/20 bg-gradient-to-b from-violet-950/30 to-black/50 p-6">
            <label class="mb-2 block text-xs uppercase tracking-wider text-violet-300/80">Secret key</label>
            <div class="flex flex-col gap-3 sm:flex-row">
                <code id="secret-key" class="flex-1 break-all rounded-xl border border-white/10 bg-black/50 px-4 py-3 font-mono text-sm text-violet-100">
                    {{ $secretKey }}
                </code>
                <button type="button" id="copy-key"
                        class="rounded-xl border border-violet-500/30 bg-violet-600/20 px-4 py-3 text-sm font-medium text-violet-100 transition hover:bg-violet-600/30">
                    Копировать
                </button>
            </div>
            <p id="copy-status" class="mt-2 hidden text-sm text-emerald-300">Скопировано в буфер обмена</p>
        </div>

        <div class="mt-8 rounded-2xl border border-white/5 bg-black/30 p-5 text-sm text-slate-400">
            <p class="font-medium text-slate-200">Следующие шаги</p>
            <ol class="mt-3 list-decimal space-y-2 pl-5">
                <li>Установите расширение Wellbeing Check-in в Chrome</li>
                <li>Откройте настройки расширения и вставьте secret key</li>
                <li>Укажите Employee ID сотрудника и сохраните</li>
            </ol>
        </div>

        <div class="mt-8 flex flex-col items-center gap-3 sm:flex-row sm:justify-center">
            <a href="{{ route('dashboard.index') }}"
               class="inline-flex rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-8 py-3 font-semibold text-white transition hover:from-violet-500 hover:to-indigo-500">
                Перейти в dashboard
            </a>
            <a href="{{ route('companies.index') }}"
               class="inline-flex rounded-xl border border-white/10 px-8 py-3 font-semibold text-slate-200 transition hover:border-violet-500/40 hover:bg-violet-500/10">
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

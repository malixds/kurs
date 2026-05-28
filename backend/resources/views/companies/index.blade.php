@extends('layouts.app')

@section('title', 'Мои компании')

@section('content')
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-semibold">Мои компании</h1>
            <p class="mt-1 text-slate-400">
                Каждая компания независима — отдельный ключ, сотрудники и аналитика.
            </p>
        </div>
        <a href="{{ route('onboarding.company.create') }}"
           class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold hover:bg-indigo-500">
            <span class="text-lg leading-none">+</span>
            Подключить компанию
        </a>
    </div>

    @if ($companies->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-700 bg-slate-900/50 p-10 text-center">
            <p class="text-slate-300">У вас пока нет подключённых компаний.</p>
            <a href="{{ route('onboarding.company.create') }}"
               class="mt-4 inline-flex rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold hover:bg-indigo-500">
                Подключить первую компанию
            </a>
        </div>
    @else
        <div class="grid gap-4 lg:grid-cols-2">
            @foreach ($companies as $company)
                <article class="rounded-2xl border border-slate-800 bg-slate-900 p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-xl font-semibold text-white">{{ $company['name'] }}</h2>
                            <p class="mt-1 text-xs text-slate-500">
                                Подключена {{ $company['connected_at']?->format('d.m.Y') ?? '—' }}
                            </p>
                        </div>
                        @if ($company['is_active'])
                            <span class="rounded-full bg-emerald-500/15 px-2.5 py-1 text-xs font-medium text-emerald-300">
                                Активна
                            </span>
                        @endif
                    </div>

                    <div class="mt-4 rounded-xl border border-slate-800 bg-slate-950 p-3">
                        <p class="text-xs uppercase tracking-wider text-slate-500">Secret key</p>
                        <code class="mt-2 block break-all font-mono text-sm text-violet-200">{{ $company['secret_key'] }}</code>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <a href="{{ route('companies.show', $company['id']) }}"
                           class="rounded-lg border border-slate-700 px-3 py-2 text-sm hover:bg-slate-800">
                            Подробнее
                        </a>
                        @if (! $company['is_active'])
                            <form method="POST" action="{{ route('companies.switch', $company['id']) }}">
                                @csrf
                                <button type="submit"
                                        class="rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium hover:bg-indigo-500">
                                    Открыть dashboard
                                </button>
                            </form>
                        @else
                            <a href="{{ route('dashboard.index') }}"
                               class="rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium hover:bg-indigo-500">
                                Dashboard
                            </a>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    @endif
@endsection

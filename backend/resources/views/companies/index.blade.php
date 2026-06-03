@extends('layouts.app')

@section('title', 'Мои компании')

@section('content')
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-[#111827]">Мои компании</h1>
            <p class="mt-1 ds-text-secondary">
                Каждая компания независима — отдельный ключ, сотрудники и аналитика.
            </p>
        </div>
        <a href="{{ route('onboarding.company.create') }}" class="ds-btn-primary !min-h-0 !px-4 !py-2 text-sm">
            <span class="text-lg leading-none">+</span>
            Подключить компанию
        </a>
    </div>

    @if ($companies->isEmpty())
        <div class="ds-panel border-dashed p-10 text-center">
            <p class="ds-text-secondary">У вас пока нет подключённых компаний.</p>
            <a href="{{ route('onboarding.company.create') }}" class="ds-btn-primary mt-4 !min-h-0 !px-5 !py-2.5 text-sm">
                Подключить первую компанию
            </a>
        </div>
    @else
        <div class="grid gap-4 lg:grid-cols-2">
            @foreach ($companies as $company)
                <article class="ds-card">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-xl font-semibold text-[#111827]">{{ $company['name'] }}</h2>
                            <p class="mt-1 text-xs ds-text-muted">
                                Подключена {{ $company['connected_at']?->format('d.m.Y') ?? '—' }}
                            </p>
                        </div>
                        @if ($company['is_active'])
                            <span class="ds-badge !text-emerald-700 !bg-emerald-50 !border-emerald-200">
                                Активна
                            </span>
                        @endif
                    </div>

                    <div class="mt-4 rounded-xl border border-black/[0.06] bg-[#F7F8FC] p-3">
                        <p class="text-xs font-medium uppercase tracking-wider ds-text-muted">Secret key</p>
                        <code class="mt-2 block break-all font-mono text-sm text-[#4E44E5]">{{ $company['secret_key'] }}</code>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <a href="{{ route('companies.show', $company['id']) }}" class="ds-btn-secondary !min-h-0 !px-3 !py-2 text-sm">
                            Подробнее
                        </a>
                        @if (! $company['is_active'])
                            <form method="POST" action="{{ route('companies.switch', $company['id']) }}">
                                @csrf
                                <button type="submit" class="ds-btn-primary !min-h-0 !px-3 !py-2 text-sm">
                                    Открыть dashboard
                                </button>
                            </form>
                        @else
                            <a href="{{ route('dashboard.index') }}" class="ds-btn-primary !min-h-0 !px-3 !py-2 text-sm">
                                Dashboard
                            </a>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    @endif
@endsection

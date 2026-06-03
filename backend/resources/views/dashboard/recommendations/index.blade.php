@extends('layouts.app')

@section('title', 'История рекомендаций')

@section('content')
    @include('dashboard.partials.nav')

    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-semibold">История рекомендаций</h1>
            <p class="mt-1 ds-text-secondary">
                @if ($activeCompany ?? null)
                    {{ $activeCompany->name }} —
                @endif
                сохранённые ответы LLM по анализу и глубокому анализу
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('dashboard.recommendations.index') }}"
               class="rounded-lg border px-3 py-1.5 text-xs font-medium transition {{ empty($sourceFilter) ? 'border-[rgba(111,99,255,0.35)] bg-[rgba(111,99,255,0.08)] text-[#111827]' : 'border-black/[0.08] ds-text-secondary hover:bg-[#F7F8FC]' }}">
                Все
            </a>
            @foreach ($sources as $source)
                <a href="{{ route('dashboard.recommendations.index', ['source' => $source->value]) }}"
                   class="rounded-lg border px-3 py-1.5 text-xs font-medium transition {{ $sourceFilter === $source->value ? 'border-[rgba(111,99,255,0.35)] bg-[rgba(111,99,255,0.08)] text-[#111827]' : 'border-black/[0.08] ds-text-secondary hover:bg-[#F7F8FC]' }}">
                    {{ $source->label() }}
                </a>
            @endforeach
        </div>
    </div>

    @if ($recommendations->isEmpty())
        <div class="ds-panel p-8 text-center">
            <p class="text-sm ds-text-secondary">Пока нет сохранённых рекомендаций.</p>
            <p class="mt-2 text-sm ds-text-muted">
                Получите рекомендации на странице
                <a href="{{ route('dashboard.analysis') }}" class="font-medium text-[#6F63FF] hover:underline">Анализ (LLM)</a>
                или
                <a href="{{ route('dashboard.deep-analysis') }}" class="font-medium text-[#6F63FF] hover:underline">Глубокий анализ</a>.
            </p>
        </div>
    @else
        <div class="space-y-3">
            @foreach ($recommendations as $item)
                <a href="{{ route('dashboard.recommendations.show', $item) }}"
                   class="ds-panel block p-5 transition hover:border-[rgba(111,99,255,0.25)] hover:shadow-[0_8px_24px_rgba(111,99,255,0.08)]">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-md bg-[rgba(111,99,255,0.1)] px-2 py-0.5 text-xs font-semibold text-[#6F63FF]">
                                    {{ $item->source->label() }}
                                </span>
                                <span class="text-sm font-semibold text-[#111827]">{{ $item->prompt_label }}</span>
                            </div>
                            <p class="mt-2 text-sm ds-text-secondary">
                                Период: {{ $item->periodLabel() }}
                                @if ($item->providersLabel())
                                    · {{ $item->providersLabel() }}
                                @endif
                            </p>
                            <p class="mt-2 line-clamp-2 text-sm ds-text-muted">
                                {{ $item->excerpt() }}
                            </p>
                        </div>
                        <div class="shrink-0 text-right text-xs ds-text-muted">
                            <time datetime="{{ $item->created_at->toIso8601String() }}">
                                {{ $item->created_at->format('d.m.Y H:i') }}
                            </time>
                            @if ($item->user)
                                <p class="mt-1">{{ $item->user->name }}</p>
                            @endif
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        @if ($recommendations->hasPages())
            <div class="mt-6 flex flex-wrap items-center justify-between gap-3 text-sm">
                <p class="ds-text-muted">
                    {{ $recommendations->firstItem() }}–{{ $recommendations->lastItem() }} из {{ $recommendations->total() }}
                </p>
                <div class="flex gap-2">
                    @if ($recommendations->onFirstPage())
                        <span class="rounded-lg border border-black/[0.06] px-3 py-1.5 text-xs ds-text-muted">Назад</span>
                    @else
                        <a href="{{ $recommendations->previousPageUrl() }}"
                           class="rounded-lg ds-btn-secondary !min-h-0 !px-3 !py-1.5 text-xs">Назад</a>
                    @endif
                    @if ($recommendations->hasMorePages())
                        <a href="{{ $recommendations->nextPageUrl() }}"
                           class="rounded-lg ds-btn-secondary !min-h-0 !px-3 !py-1.5 text-xs">Далее</a>
                    @else
                        <span class="rounded-lg border border-black/[0.06] px-3 py-1.5 text-xs ds-text-muted">Далее</span>
                    @endif
                </div>
            </div>
        @endif
    @endif
@endsection

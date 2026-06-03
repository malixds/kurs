@extends('layouts.app')

@section('title', 'Рекомендация')

@section('content')
    @include('dashboard.partials.nav')

    <div class="mb-6">
        <a href="{{ route('dashboard.recommendations.index', request()->only('source')) }}"
           class="text-sm font-medium text-[#6F63FF] hover:underline">
            ← К истории
        </a>
    </div>

    <div class="mb-6">
        <div class="flex flex-wrap items-center gap-2">
            <span class="rounded-md bg-[rgba(111,99,255,0.1)] px-2 py-0.5 text-xs font-semibold text-[#6F63FF]">
                {{ $record->source->label() }}
            </span>
            <h1 class="text-2xl font-semibold text-[#111827]">{{ $record->prompt_label }}</h1>
        </div>
        <p class="mt-2 text-sm ds-text-secondary">
            Период: {{ $record->periodLabel() }}
            @if ($record->providersLabel())
                · Трекеры: {{ $record->providersLabel() }}
            @endif
        </p>
        <p class="mt-1 text-xs ds-text-muted">
            Сохранено {{ $record->created_at->format('d.m.Y H:i') }}
            @if ($record->user)
                · {{ $record->user->name }}
            @endif
            @if ($record->llm_model)
                · модель {{ $record->llm_model }}
            @endif
        </p>
    </div>

    @if (is_array($record->summary) && $record->summary !== [])
        <div class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @if (isset($record->summary['total_answers']))
                <div class="ds-panel p-4">
                    <p class="text-xs ds-text-muted">Ответов check-in</p>
                    <p class="mt-1 text-xl font-bold text-[#111827]">{{ $record->summary['total_answers'] }}</p>
                </div>
            @endif
            @if (isset($record->summary['employees_with_data']))
                <div class="ds-panel p-4">
                    <p class="text-xs ds-text-muted">Сотрудников с данными</p>
                    <p class="mt-1 text-xl font-bold text-[#111827]">{{ $record->summary['employees_with_data'] }}</p>
                </div>
            @endif
        </div>
    @endif

    <div class="ds-recommendation-panel">
        <div class="ds-recommendation-panel__header">
            <h2 class="ds-recommendation-panel__title">Текст рекомендаций</h2>
        </div>
        <div id="recommendation-body" class="ds-recommendation-panel__body"></div>
    </div>

    <script type="application/json" id="recommendation-json">@json($record->recommendation)</script>
    <script>
        function renderSavedRecommendation() {
            const el = document.getElementById('recommendation-body');
            const raw = document.getElementById('recommendation-json')?.textContent ?? '';
            let text = '';
            try {
                text = JSON.parse(raw);
            } catch {
                text = raw;
            }
            if (window.DsDashboard?.renderRecommendation) {
                window.DsDashboard.renderRecommendation(el, text);
            } else {
                el.textContent = text;
            }
        }

        window.addEventListener('ds-dashboard-ready', renderSavedRecommendation, { once: true });
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', renderSavedRecommendation, { once: true });
        } else {
            renderSavedRecommendation();
        }
    </script>
@endsection

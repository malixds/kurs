@extends('layouts.app')

@section('title', $employee->name ?? 'Employee')

@section('content')
    @include('dashboard.partials.nav')
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <a href="{{ route('dashboard.index') }}" class="text-sm ds-link">← Back to dashboard</a>
            <h1 class="mt-2 text-3xl font-semibold">{{ $employee->name ?? 'Employee' }}</h1>
            <p class="mt-1 ds-text-secondary">{{ $employee->email }} · {{ $employee->external_id }}</p>
        </div>
        <form method="GET" class="flex flex-wrap items-center gap-3">
            @include('dashboard.partials.date-range', [
                'fromId' => 'employee-from',
                'toId' => 'employee-to',
                'fromValue' => $filters['from'],
                'toValue' => $filters['to'],
                'fromName' => 'from',
                'toName' => 'to',
            ])
            <button type="submit" class="ds-btn-primary !min-h-0 !px-4 !py-2 text-sm">
                Apply
            </button>
        </form>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <div class="ds-panel p-5">
            <p class="text-sm ds-text-secondary">Burnout risk</p>
            <p class="mt-2 text-2xl font-semibold capitalize">{{ $history['burnout_risk']['level'] }}</p>
        </div>
        <div class="ds-panel p-5">
            <p class="text-sm ds-text-secondary">7-day average</p>
            <p class="mt-2 text-2xl font-semibold">{{ $history['burnout_risk']['current_average'] ?? '—' }}</p>
        </div>
        <div class="ds-panel p-5">
            <p class="text-sm ds-text-secondary">Trend</p>
            <p class="mt-2 text-2xl font-semibold capitalize">{{ $history['burnout_risk']['trend'] }}</p>
        </div>
    </div>

    <div class="mt-8 ds-panel p-5">
        <h2 class="text-lg font-medium">История ответов</h2>
        <p class="mt-1 text-sm ds-text-secondary">Нажмите на дату, чтобы раскрыть ответы за день.</p>

        <div class="mt-4 divide-y divide-black/[0.06] border-y border-black/[0.06]">
            @forelse ($history['history'] as $day)
                @php
                    $answerCount = count($day['answers']);
                    $dateLabel = \Carbon\Carbon::parse($day['date'])->format('d.m.Y');
                @endphp
                <details class="check-in-day">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 py-4 text-base [&::-webkit-details-marker]:hidden">
                        <span class="flex min-w-0 items-center gap-4">
                            <span class="check-in-day__chevron shrink-0 ds-text-secondary" aria-hidden="true">▼</span>
                            <span class="text-lg font-medium text-white">{{ $dateLabel }}</span>
                        </span>
                        <span class="flex shrink-0 flex-wrap items-center justify-end gap-x-4 gap-y-1 text-base ds-text-secondary">
                            <span>{{ $answerCount }} {{ $answerCount === 1 ? 'ответ' : ($answerCount < 5 ? 'ответа' : 'ответов') }}</span>
                            <span class="ds-text-muted">·</span>
                            <span>Средний балл: <span class="text-[#5F6473]">{{ $day['average_score'] ?? '—' }}</span></span>
                        </span>
                    </summary>
                    <div class="check-in-day__panel">
                        <div class="check-in-day__panel-inner border-t border-black/[0.06] pb-4 pl-9 pr-0 pt-3">
                            <ul class="space-y-4 text-base text-[#5F6473]">
                                @foreach ($day['answers'] as $answer)
                                    <li class="border-b border-black/[0.06] pb-4 last:border-b-0 last:pb-0">
                                        <p class="text-sm font-medium ds-text-muted">{{ $answer['question'] }}</p>
                                        <p class="mt-1.5">{{ $answer['answer'] }}</p>
                                        @if ($answer['score'] !== null)
                                            <p class="mt-1.5 text-sm ds-text-muted">Оценка: {{ $answer['score'] }}</p>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </details>
            @empty
                <p class="py-8 text-center text-base ds-text-secondary">
                    За выбранный период нет ответов.
                </p>
            @endforelse
        </div>
    </div>

    <style>
        .check-in-day__chevron {
            display: inline-block;
            font-size: 1.125rem;
            line-height: 1;
            transition: transform 0.2s ease;
        }

        .check-in-day[open] .check-in-day__chevron {
            transform: rotate(180deg);
        }

        .check-in-day__panel {
            display: grid;
            grid-template-rows: 0fr;
            transition: grid-template-rows 0.25s ease-out;
        }

        .check-in-day[open] .check-in-day__panel {
            grid-template-rows: 1fr;
        }

        .check-in-day__panel-inner {
            overflow: hidden;
            opacity: 0;
            transition: opacity 0.2s ease-out;
        }

        .check-in-day[open] .check-in-day__panel-inner {
            opacity: 1;
        }
    </style>
@endsection

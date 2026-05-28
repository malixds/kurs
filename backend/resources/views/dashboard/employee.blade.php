@extends('layouts.app')

@section('title', $employee->name ?? 'Employee')

@section('content')
    @include('dashboard.partials.nav')
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <a href="{{ route('dashboard.index') }}" class="text-sm text-indigo-400 hover:text-indigo-300">← Back to dashboard</a>
            <h1 class="mt-2 text-3xl font-semibold">{{ $employee->name ?? 'Employee' }}</h1>
            <p class="mt-1 text-slate-400">{{ $employee->email }} · {{ $employee->external_id }}</p>
        </div>
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="mb-1 block text-xs text-slate-400">From</label>
                <input type="date" name="from" value="{{ $filters['from'] }}"
                       class="rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs text-slate-400">To</label>
                <input type="date" name="to" value="{{ $filters['to'] }}"
                       class="rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-sm">
            </div>
            <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm hover:bg-indigo-500">
                Apply
            </button>
        </form>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-2xl border border-slate-800 bg-slate-900 p-5">
            <p class="text-sm text-slate-400">Burnout risk</p>
            <p class="mt-2 text-2xl font-semibold capitalize">{{ $history['burnout_risk']['level'] }}</p>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900 p-5">
            <p class="text-sm text-slate-400">7-day average</p>
            <p class="mt-2 text-2xl font-semibold">{{ $history['burnout_risk']['current_average'] ?? '—' }}</p>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900 p-5">
            <p class="text-sm text-slate-400">Trend</p>
            <p class="mt-2 text-2xl font-semibold capitalize">{{ $history['burnout_risk']['trend'] }}</p>
        </div>
    </div>

    <div class="mt-8 rounded-2xl border border-slate-800 bg-slate-900 p-5">
        <h2 class="text-lg font-medium">История ответов</h2>
        <p class="mt-1 text-sm text-slate-400">Нажмите на дату, чтобы раскрыть ответы за день.</p>

        <div class="mt-4 divide-y divide-slate-800 border-y border-slate-800">
            @forelse ($history['history'] as $day)
                @php
                    $answerCount = count($day['answers']);
                    $dateLabel = \Carbon\Carbon::parse($day['date'])->format('d.m.Y');
                @endphp
                <details class="check-in-day">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 py-4 text-base [&::-webkit-details-marker]:hidden">
                        <span class="flex min-w-0 items-center gap-4">
                            <span class="check-in-day__chevron shrink-0 text-slate-400" aria-hidden="true">▼</span>
                            <span class="text-lg font-medium text-white">{{ $dateLabel }}</span>
                        </span>
                        <span class="flex shrink-0 flex-wrap items-center justify-end gap-x-4 gap-y-1 text-base text-slate-400">
                            <span>{{ $answerCount }} {{ $answerCount === 1 ? 'ответ' : ($answerCount < 5 ? 'ответа' : 'ответов') }}</span>
                            <span class="text-slate-500">·</span>
                            <span>Средний балл: <span class="text-slate-300">{{ $day['average_score'] ?? '—' }}</span></span>
                        </span>
                    </summary>
                    <div class="check-in-day__panel">
                        <div class="check-in-day__panel-inner border-t border-slate-800 pb-4 pl-9 pr-0 pt-3">
                            <ul class="space-y-4 text-base text-slate-300">
                                @foreach ($day['answers'] as $answer)
                                    <li class="border-b border-slate-800 pb-4 last:border-b-0 last:pb-0">
                                        <p class="text-sm font-medium text-slate-500">{{ $answer['question'] }}</p>
                                        <p class="mt-1.5">{{ $answer['answer'] }}</p>
                                        @if ($answer['score'] !== null)
                                            <p class="mt-1.5 text-sm text-slate-500">Оценка: {{ $answer['score'] }}</p>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </details>
            @empty
                <p class="py-8 text-center text-base text-slate-400">
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

@extends('layouts.app')

@section('title', $employee->name ?? 'Employee')

@section('content')
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
        <h2 class="text-lg font-medium">Daily history</h2>
        <div class="mt-4 space-y-4">
            @forelse ($history['history'] as $day)
                <div class="rounded-xl border border-slate-800 bg-slate-950 p-4">
                    <div class="flex items-center justify-between">
                        <p class="font-medium">{{ $day['date'] }}</p>
                        <p class="text-sm text-slate-400">Avg: {{ $day['average_score'] ?? '—' }}</p>
                    </div>
                    <ul class="mt-3 space-y-2 text-sm text-slate-300">
                        @foreach ($day['answers'] as $answer)
                            <li>
                                <span class="text-slate-400">{{ $answer['question'] }}:</span>
                                {{ $answer['answer'] }}
                                @if ($answer['score'] !== null)
                                    <span class="text-slate-500">({{ $answer['score'] }})</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @empty
                <p class="text-slate-400">No check-ins for this period.</p>
            @endforelse
        </div>
    </div>
@endsection

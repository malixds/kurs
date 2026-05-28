@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    @include('dashboard.partials.nav')

    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-semibold">Team wellbeing overview</h1>
            <p class="mt-1 text-slate-400">
                @if ($activeCompany ?? null)
                    {{ $activeCompany->name }} — aggregated mood metrics and burnout indicators
                @else
                    Aggregated mood metrics and burnout indicators
                @endif
            </p>
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

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-slate-800 bg-slate-900 p-5">
            <p class="text-sm text-slate-400">Average mood score</p>
            <p class="mt-2 text-3xl font-semibold">
                {{ $overview['average_mood_score'] ?? '—' }}
            </p>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900 p-5">
            <p class="text-sm text-slate-400">Burnout risk</p>
            <p class="mt-2 text-3xl font-semibold capitalize">{{ $overview['burnout_risk']['level'] }}</p>
            <p class="mt-1 text-sm text-slate-400">{{ $overview['burnout_risk']['label'] }}</p>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900 p-5">
            <p class="text-sm text-slate-400">Total responses</p>
            <p class="mt-2 text-3xl font-semibold">{{ $overview['weekly_summary']['total_responses'] }}</p>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900 p-5">
            <p class="text-sm text-slate-400">Trend</p>
            <p class="mt-2 text-3xl font-semibold capitalize">{{ $overview['burnout_risk']['trend'] }}</p>
        </div>
    </div>

    <div class="mt-8 grid gap-6 xl:grid-cols-3">
        <div class="rounded-2xl border border-slate-800 bg-slate-900 p-5 xl:col-span-2">
            <h2 class="text-lg font-medium">Mood trend</h2>
            <canvas id="moodTrendChart" height="120" class="mt-4"></canvas>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900 p-5">
            <h2 class="text-lg font-medium">Departments</h2>
            <canvas id="departmentChart" height="220" class="mt-4"></canvas>
        </div>
    </div>

    <div class="mt-8 rounded-2xl border border-slate-800 bg-slate-900 p-5">
        <h2 class="text-lg font-medium">Employees</h2>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="text-slate-400">
                    <tr>
                        <th class="px-3 py-2">Name</th>
                        <th class="px-3 py-2">Average score</th>
                        <th class="px-3 py-2">Responses</th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($overview['employee_summaries'] as $employee)
                        <tr class="border-t border-slate-800">
                            <td class="px-3 py-3">{{ $employee['name'] ?? 'Unknown' }}</td>
                            <td class="px-3 py-3">{{ $employee['average_score'] ?? '—' }}</td>
                            <td class="px-3 py-3">{{ $employee['responses'] }}</td>
                            <td class="px-3 py-3">
                                <a href="{{ route('dashboard.employee', $employee['employee_id']) }}"
                                   class="text-indigo-400 hover:text-indigo-300">Details</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-3 py-6 text-slate-400">No employee data for selected period.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <script type="application/json" id="dashboard-data">
        @json([
            'trends' => $overview['trends'],
            'departments' => $overview['department_overview'],
        ])
    </script>
@endsection

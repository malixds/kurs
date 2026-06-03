@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    @include('dashboard.partials.nav')

    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-[#111827]">Team wellbeing overview</h1>
            <p class="mt-1 ds-text-secondary">
                @if ($activeCompany ?? null)
                    {{ $activeCompany->name }} — aggregated mood metrics and burnout indicators
                @else
                    Aggregated mood metrics and burnout indicators
                @endif
            </p>
        </div>
        <form method="GET" class="flex flex-wrap items-center gap-3">
            @include('dashboard.partials.date-range', [
                'fromId' => 'filter-from',
                'toId' => 'filter-to',
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

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="ds-panel p-5">
            <p class="text-sm ds-text-secondary">Average mood score</p>
            <p class="mt-2 text-3xl font-bold text-[#111827]">
                {{ $overview['average_mood_score'] ?? '—' }}
            </p>
        </div>
        <div class="ds-panel p-5">
            <p class="text-sm ds-text-secondary">Burnout risk</p>
            <p class="mt-2 text-3xl font-bold capitalize text-[#111827]">{{ $overview['burnout_risk']['level'] }}</p>
            <p class="mt-1 text-sm ds-text-muted">{{ $overview['burnout_risk']['label'] }}</p>
        </div>
        <div class="ds-panel p-5">
            <p class="text-sm ds-text-secondary">Total responses</p>
            <p class="mt-2 text-3xl font-bold text-[#111827]">{{ $overview['weekly_summary']['total_responses'] }}</p>
        </div>
        <div class="ds-panel p-5">
            <p class="text-sm ds-text-secondary">Trend</p>
            <p class="mt-2 text-3xl font-bold capitalize text-[#111827]">{{ $overview['burnout_risk']['trend'] }}</p>
        </div>
    </div>

    <div class="mt-8 ds-panel p-5">
        <h2 class="text-lg font-semibold text-[#111827]">Mood trend</h2>
        <canvas id="moodTrendChart" height="120" class="mt-4"></canvas>
    </div>

    <div class="mt-8 ds-panel p-5">
        <h2 class="text-lg font-semibold text-[#111827]">Employees</h2>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="ds-text-muted">
                    <tr>
                        <th class="px-3 py-2">Name</th>
                        <th class="px-3 py-2">Average score</th>
                        <th class="px-3 py-2">Responses</th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($overview['employee_summaries'] as $employee)
                        <tr class="border-t border-black/[0.06]">
                            <td class="px-3 py-3 text-[#111827]">{{ $employee['name'] ?? 'Unknown' }}</td>
                            <td class="px-3 py-3">{{ $employee['average_score'] ?? '—' }}</td>
                            <td class="px-3 py-3">{{ $employee['responses'] }}</td>
                            <td class="px-3 py-3">
                                <a href="{{ route('dashboard.employee', $employee['employee_id']) }}" class="ds-link">
                                    Details
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-3 py-6 ds-text-muted">No employee data for selected period.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <script type="application/json" id="dashboard-data">
        @json([
            'trends' => $overview['trends'],
        ])
    </script>
@endsection

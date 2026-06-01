<?php

namespace App\Integrations\Support;

class ProviderMetricsMerger
{
    /**
     * @param  array<string, mixed>  $existing
     * @param  array<string, mixed>  $incoming
     * @return array<string, mixed>
     */
    public function merge(array $existing, array $incoming): array
    {
        $merged = $existing;

        foreach (['tasks_closed', 'tasks_created', 'tasks_updated', 'tasks_open_at_period_end', 'overdue_count'] as $key) {
            $merged[$key] = ($merged[$key] ?? 0) + ($incoming[$key] ?? 0);
        }

        foreach (['sample_issues', 'closed_issues', 'open_issues', 'overdue_issues'] as $key) {
            $merged[$key] = array_values(array_unique(array_merge(
                $merged[$key] ?? [],
                $incoming[$key] ?? [],
            )));
        }

        $merged['by_status'] = $this->mergeByStatus($merged['by_status'] ?? [], $incoming['by_status'] ?? []);

        $incomingAvg = $incoming['avg_resolution_days'] ?? null;
        if ($incomingAvg !== null) {
            $merged['avg_resolution_days'] = $merged['avg_resolution_days'] ?? $incomingAvg;
        }

        return $merged;
    }

    /**
     * @param  array<string, int>  $a
     * @param  array<string, int>  $b
     * @return array<string, int>
     */
    private function mergeByStatus(array $a, array $b): array
    {
        foreach ($b as $status => $count) {
            $a[$status] = ($a[$status] ?? 0) + $count;
        }

        return $a;
    }
}

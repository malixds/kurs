<?php

namespace App\Integrations\Connectors;

use App\Integrations\Contracts\WorkTrackerConnector;
use App\Integrations\DTO\WorkProgressCollection;
use App\Integrations\DTO\WorkProgressRequest;
use App\Integrations\Enums\IntegrationProvider;
use App\Integrations\Support\IssueProgressAggregator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class YandexTrackerConnector implements WorkTrackerConnector
{
    public function provider(): IntegrationProvider
    {
        return IntegrationProvider::YandexTracker;
    }

    public function testConnection(array $credentials, array $settings = []): bool
    {
        $response = $this->client($credentials)
            ->get('/v3/myself');

        return $response->successful();
    }

    public function fetchWorkProgress(WorkProgressRequest $request): WorkProgressCollection
    {
        $filter = [
            'updated' => [
                'from' => $request->from->startOfDay()->format('Y-m-d'),
                'to' => $request->to->endOfDay()->format('Y-m-d'),
            ],
        ];

        $queueKeys = $this->parseList($request->settings['queue_keys'] ?? $request->credentials['queue_keys'] ?? '');
        if ($queueKeys !== []) {
            $filter['queue'] = $queueKeys;
        }

        $issues = $this->searchIssues($request->credentials, $filter);
        $aggregator = new IssueProgressAggregator;

        foreach ($issues as $issue) {
            $assignee = $issue['assignee'] ?? null;
            $displayName = is_array($assignee) ? ($assignee['display'] ?? $assignee['id'] ?? 'Unassigned') : 'Unassigned';
            $login = is_array($assignee) ? ($assignee['id'] ?? null) : null;
            $status = $issue['status']['key'] ?? $issue['status']['display'] ?? null;
            $createdAt = isset($issue['createdAt']) ? Carbon::parse($issue['createdAt']) : null;
            $updatedAt = isset($issue['updatedAt']) ? Carbon::parse($issue['updatedAt']) : null;
            $resolvedAt = isset($issue['resolvedAt']) ? Carbon::parse($issue['resolvedAt']) : null;
            $dueAt = isset($issue['deadline']) ? Carbon::parse($issue['deadline']) : null;

            $aggregator->addIssue(
                assigneeKey: $login ? (string) $login : null,
                displayName: (string) $displayName,
                email: null,
                issueKey: (string) ($issue['key'] ?? $issue['id']),
                status: is_string($status) ? $status : null,
                createdAt: $createdAt,
                updatedAt: $updatedAt,
                resolvedAt: $resolvedAt,
                dueAt: $dueAt,
                periodFrom: $request->from,
                periodTo: $request->to,
            );
        }

        return new WorkProgressCollection(
            provider: $this->provider(),
            employees: $aggregator->toDtos(),
            warnings: count($issues) >= config('integrations.max_issues_per_sync')
                ? ['Достигнут лимит задач при загрузке из Яндекс Трекера.']
                : [],
        );
    }

    private function searchIssues(array $credentials, array $filter): array
    {
        $max = config('integrations.max_issues_per_sync', 500);
        $perPage = 50;
        $page = 1;
        $all = [];

        do {
            $response = $this->client($credentials)
                ->post('/v3/issues/_search?perPage='.$perPage.'&page='.$page, ['filter' => $filter]);

            if (! $response->successful()) {
                throw new RuntimeException('Яндекс Трекер: '.$response->body());
            }

            $batch = $response->json();
            if (! is_array($batch)) {
                break;
            }

            $all = array_merge($all, $batch);
            $page++;
        } while (count($batch) === $perPage && count($all) < $max);

        return array_slice($all, 0, $max);
    }

    private function client(array $credentials): \Illuminate\Http\Client\PendingRequest
    {
        $token = $credentials['oauth_token'] ?? '';
        $orgId = $credentials['org_id'] ?? '';
        $orgHeader = ($credentials['org_header'] ?? 'x_org') === 'x_cloud' ? 'X-Cloud-Org-ID' : 'X-Org-ID';

        return Http::baseUrl('https://api.tracker.yandex.net')
            ->withHeaders([
                'Authorization' => 'OAuth '.$token,
                $orgHeader => $orgId,
            ])
            ->timeout(30)
            ->acceptJson();
    }

    private function parseList(string $value): array
    {
        if (trim($value) === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $value))));
    }
}

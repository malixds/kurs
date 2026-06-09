<?php

namespace App\Integrations\Connectors;

use App\Integrations\Contracts\WorkTrackerConnector;
use App\Integrations\DTO\WorkProgressCollection;
use App\Integrations\DTO\WorkProgressRequest;
use App\Integrations\Enums\IntegrationProvider;
use App\Integrations\Support\IssueProgressAggregator;
use Carbon\Carbon;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class LinearConnector implements WorkTrackerConnector
{
    public function provider(): IntegrationProvider
    {
        return IntegrationProvider::Linear;
    }

    public function testConnection(array $credentials, array $settings = []): bool
    {
        $response = $this->client($credentials)->post('', [
            'query' => '{ viewer { id name } }',
        ]);

        return $response->successful() && empty($response->json('errors'));
    }

    public function fetchWorkProgress(WorkProgressRequest $request): WorkProgressCollection
    {
        $from = $request->from->toIso8601String();
        $to = $request->to->endOfDay()->toIso8601String();

        $query = <<<GQL
query(\$after: String) {
  issues(filter: { updatedAt: { gte: "{$from}", lte: "{$to}" } }, first: 50, after: \$after) {
    pageInfo { hasNextPage endCursor }
    nodes {
      identifier
      title
      createdAt
      updatedAt
      completedAt
      dueDate
      state { name }
      assignee { id name email }
    }
  }
}
GQL;
        $issues = $this->fetchAllIssues($request->credentials, $query);
        $aggregator = new IssueProgressAggregator;

        foreach ($issues as $issue) {
            $assignee = $issue['assignee'] ?? null;
            $displayName = is_array($assignee) ? ($assignee['name'] ?? 'Unassigned') : 'Unassigned';
            $email = is_array($assignee) ? ($assignee['email'] ?? null) : null;
            $id = is_array($assignee) ? ($assignee['id'] ?? null) : null;

            $aggregator->addIssue(
                assigneeKey: $id,
                displayName: (string) $displayName,
                email: $email,
                issueKey: (string) ($issue['identifier'] ?? ''),
                status: $issue['state']['name'] ?? null,
                createdAt: isset($issue['createdAt']) ? Carbon::parse($issue['createdAt']) : null,
                updatedAt: isset($issue['updatedAt']) ? Carbon::parse($issue['updatedAt']) : null,
                resolvedAt: isset($issue['completedAt']) ? Carbon::parse($issue['completedAt']) : null,
                dueAt: isset($issue['dueDate']) ? Carbon::parse($issue['dueDate']) : null,
                periodFrom: $request->from,
                periodTo: $request->to,
            );
        }

        return new WorkProgressCollection(
            provider: $this->provider(),
            employees: $aggregator->toDtos(),
            warnings: [],
        );
    }

    private function fetchAllIssues(array $credentials, string $query): array
    {
        $max = config('integrations.max_issues_per_sync', 500);
        $after = null;
        $all = [];

        do {
            $response = $this->client($credentials)->post('', [
                'query' => $query,
                'variables' => ['after' => $after],
            ]);

            if (! $response->successful() || $response->json('errors')) {
                throw new RuntimeException('Linear: '.json_encode($response->json('errors') ?? $response->body()));
            }

            $data = $response->json('data.issues');
            $nodes = $data['nodes'] ?? [];
            $all = array_merge($all, $nodes);
            $after = $data['pageInfo']['hasNextPage'] ?? false ? $data['pageInfo']['endCursor'] : null;
        } while ($after !== null && count($all) < $max);

        return array_slice($all, 0, $max);
    }

    private function client(array $credentials): PendingRequest
    {
        return Http::baseUrl('https://api.linear.app')
            ->withToken($credentials['api_key'] ?? '')
            ->timeout(30)
            ->acceptJson();
    }
}

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

class GithubIssuesConnector implements WorkTrackerConnector
{
    public function provider(): IntegrationProvider
    {
        return IntegrationProvider::Github;
    }

    public function testConnection(array $credentials, array $settings = []): bool
    {
        $owner = $credentials['owner'] ?? '';
        $repo = $credentials['repo'] ?? '';

        $response = $this->client($credentials)->get("/repos/{$owner}/{$repo}");

        return $response->successful();
    }

    public function fetchWorkProgress(WorkProgressRequest $request): WorkProgressCollection
    {
        $owner = $request->credentials['owner'] ?? '';
        $repo = $request->credentials['repo'] ?? '';
        $since = $request->from->toIso8601String();

        $issues = $this->fetchIssues($request->credentials, $owner, $repo, $since);
        $aggregator = new IssueProgressAggregator;

        foreach ($issues as $issue) {
            if (isset($issue['pull_request'])) {
                continue;
            }

            $assignee = $issue['assignee'] ?? ($issue['user'] ?? null);
            $displayName = is_array($assignee) ? ($assignee['login'] ?? 'Unassigned') : 'Unassigned';
            $email = null;
            $login = is_array($assignee) ? ($assignee['login'] ?? null) : null;
            $status = ($issue['state'] ?? 'open') === 'closed' ? 'closed' : 'open';
            $createdAt = isset($issue['created_at']) ? Carbon::parse($issue['created_at']) : null;
            $updatedAt = isset($issue['updated_at']) ? Carbon::parse($issue['updated_at']) : null;
            $resolvedAt = $status === 'closed' && $updatedAt ? $updatedAt : null;

            $aggregator->addIssue(
                assigneeKey: $login,
                displayName: (string) $displayName,
                email: $email,
                issueKey: '#'.$issue['number'],
                status: $status,
                createdAt: $createdAt,
                updatedAt: $updatedAt,
                resolvedAt: $resolvedAt,
                dueAt: null,
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

    private function fetchIssues(array $credentials, string $owner, string $repo, string $since): array
    {
        $max = config('integrations.max_issues_per_sync', 500);
        $page = 1;
        $all = [];

        do {
            $response = $this->client($credentials)->get("/repos/{$owner}/{$repo}/issues", [
                'state' => 'all',
                'since' => $since,
                'per_page' => 100,
                'page' => $page,
            ]);

            if (! $response->successful()) {
                throw new RuntimeException('GitHub: '.$response->body());
            }

            $batch = $response->json();
            if (! is_array($batch)) {
                break;
            }

            $all = array_merge($all, $batch);
            $page++;
        } while (count($batch) === 100 && count($all) < $max);

        return array_slice($all, 0, $max);
    }

    private function client(array $credentials): PendingRequest
    {
        return Http::baseUrl('https://api.github.com')
            ->withToken($credentials['token'] ?? '')
            ->withHeaders(['Accept' => 'application/vnd.github+json'])
            ->timeout(30);
    }
}

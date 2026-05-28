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

class JiraCloudConnector implements WorkTrackerConnector
{
    public function provider(): IntegrationProvider
    {
        return IntegrationProvider::Jira;
    }

    public function testConnection(array $credentials, array $settings = []): bool
    {
        $response = $this->client($credentials)->get('/rest/api/3/myself');

        return $response->successful();
    }

    public function fetchWorkProgress(WorkProgressRequest $request): WorkProgressCollection
    {
        $from = $request->from->format('Y-m-d');
        $to = $request->to->format('Y-m-d');
        $projects = $this->parseList($request->settings['project_keys'] ?? $request->credentials['project_keys'] ?? '');

        $jql = "updated >= '{$from}' AND updated <= '{$to}'";
        if ($projects !== []) {
            $quoted = implode(',', array_map(fn (string $p) => '"'.addslashes($p).'"', $projects));
            $jql .= ' AND project IN ('.$quoted.')';
        }

        $issues = $this->searchIssues($request->credentials, $jql);
        $aggregator = new IssueProgressAggregator;

        foreach ($issues as $issue) {
            $fields = $issue['fields'] ?? [];
            $assignee = $fields['assignee'] ?? null;
            $displayName = is_array($assignee) ? ($assignee['displayName'] ?? 'Unassigned') : 'Unassigned';
            $email = is_array($assignee) ? ($assignee['emailAddress'] ?? null) : null;
            $accountId = is_array($assignee) ? ($assignee['accountId'] ?? null) : null;
            $status = $fields['status']['name'] ?? null;
            $createdAt = isset($fields['created']) ? Carbon::parse($fields['created']) : null;
            $updatedAt = isset($fields['updated']) ? Carbon::parse($fields['updated']) : null;
            $resolvedAt = isset($fields['resolutiondate']) ? Carbon::parse($fields['resolutiondate']) : null;
            $dueAt = isset($fields['duedate']) ? Carbon::parse($fields['duedate']) : null;

            $aggregator->addIssue(
                assigneeKey: $accountId ? (string) $accountId : null,
                displayName: (string) $displayName,
                email: $email,
                issueKey: (string) ($issue['key'] ?? ''),
                status: $status,
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
                ? ['Достигнут лимит задач при загрузке из Jira.']
                : [],
        );
    }

    private function searchIssues(array $credentials, string $jql): array
    {
        $max = config('integrations.max_issues_per_sync', 500);
        $startAt = 0;
        $all = [];

        do {
            $response = $this->client($credentials)->get('/rest/api/3/search', [
                'jql' => $jql,
                'startAt' => $startAt,
                'maxResults' => 50,
                'fields' => 'summary,status,assignee,created,updated,resolutiondate,duedate',
            ]);

            if (! $response->successful()) {
                throw new RuntimeException('Jira: '.$response->body());
            }

            $issues = $response->json('issues', []);
            $all = array_merge($all, $issues);
            $startAt += count($issues);
        } while (count($issues) === 50 && count($all) < $max);

        return array_slice($all, 0, $max);
    }

    private function client(array $credentials): \Illuminate\Http\Client\PendingRequest
    {
        $site = rtrim($credentials['site_url'] ?? '', '/');
        $email = $credentials['email'] ?? '';
        $token = $credentials['api_token'] ?? '';

        return Http::baseUrl($site)
            ->withBasicAuth($email, $token)
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

<?php

namespace App\Integrations\Connectors;

use App\Integrations\Contracts\WorkTrackerConnector;
use App\Integrations\DTO\WorkProgressCollection;
use App\Integrations\DTO\WorkProgressRequest;
use App\Integrations\Enums\IntegrationProvider;
use App\Integrations\Support\IssueProgressAggregator;
use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class JiraCloudConnector implements WorkTrackerConnector
{
    private const ISSUE_FIELDS = [
        'summary',
        'status',
        'assignee',
        'created',
        'updated',
        'resolutiondate',
        'duedate',
    ];

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
        $projects = $this->parseList($request->settings['project_keys'] ?? $request->credentials['project_keys'] ?? '');
        $jql = $this->buildJql($request, $projects);

        $issues = $this->searchIssues($request->credentials, $jql);
        $aggregator = new IssueProgressAggregator;

        foreach ($issues as $issue) {
            $fields = $issue['fields'] ?? [];
            $assignee = $fields['assignee'] ?? null;
            $displayName = is_array($assignee) ? ($assignee['displayName'] ?? 'Unassigned') : 'Unassigned';
            $email = is_array($assignee) ? ($assignee['emailAddress'] ?? null) : null;
            $accountId = is_array($assignee) ? ($assignee['accountId'] ?? null) : null;
            $status = is_array($fields['status'] ?? null)
                ? ($fields['status']['name'] ?? null)
                : (is_string($fields['status'] ?? null) ? $fields['status'] : null);
            $createdAt = isset($fields['created']) ? Carbon::parse($fields['created']) : null;
            $updatedAt = isset($fields['updated']) ? Carbon::parse($fields['updated']) : null;
            $resolvedAt = isset($fields['resolutiondate']) ? Carbon::parse($fields['resolutiondate']) : null;
            $dueAt = isset($fields['duedate']) ? Carbon::parse($fields['duedate']) : null;

            $aggregator->addIssue(
                assigneeKey: $accountId ? (string) $accountId : null,
                displayName: (string) $displayName,
                email: $email,
                issueKey: (string) ($issue['key'] ?? $issue['id'] ?? ''),
                status: $status,
                createdAt: $createdAt,
                updatedAt: $updatedAt,
                resolvedAt: $resolvedAt,
                dueAt: $dueAt,
                periodFrom: $request->from,
                periodTo: $request->to,
            );
        }

        $warnings = [];
        if (count($issues) >= config('integrations.max_issues_per_sync')) {
            $warnings[] = 'Достигнут лимит задач при загрузке из Jira.';
        }

        if (count($issues) === 0) {
            $warnings[] = "Jira не вернула задач за период. JQL: {$jql}. "
                .'Проверьте ключ проекта (KAN) и что задачи создавались или обновлялись в эти даты.';
        }

        $collection = new WorkProgressCollection(
            provider: $this->provider(),
            employees: $aggregator->toDtos(),
            warnings: $warnings,
        );

        return $this->withFetchMeta($collection, count($issues), $jql);
    }

    private function buildJql(WorkProgressRequest $request, array $projects): string
    {
        $from = $request->from->format('Y-m-d');
        $toExclusive = $request->to->copy()->addDay()->format('Y-m-d');

        // Конец периода — exclusive (<), иначе последний день часто «обрезается».
        $activity = "(updated >= \"{$from}\" AND updated < \"{$toExclusive}\" "
            ."OR created >= \"{$from}\" AND created < \"{$toExclusive}\")";

        if ($projects === []) {
            return $activity;
        }

        $quoted = implode(',', array_map(fn (string $p) => '"'.addslashes($p).'"', $projects));

        return "project IN ({$quoted}) AND {$activity}";
    }

    private function searchIssues(array $credentials, string $jql): array
    {
        $max = config('integrations.max_issues_per_sync', 500);
        $pageSize = 50;
        $all = [];
        $nextPageToken = null;

        do {
            $payload = [
                'jql' => $jql,
                'maxResults' => min($pageSize, max(1, $max - count($all))),
                'fields' => self::ISSUE_FIELDS,
                'fieldsByKeys' => false,
            ];

            if ($nextPageToken !== null) {
                $payload['nextPageToken'] = $nextPageToken;
            }

            $response = $this->client($credentials)->post('/rest/api/3/search/jql', $payload);

            if (! $response->successful()) {
                throw new RuntimeException('Jira: '.$response->body());
            }

            $pageIssues = $this->extractIssuesFromSearchResponse($response);
            $all = array_merge($all, $pageIssues);
            $nextPageToken = $response->json('nextPageToken');
        } while ($nextPageToken !== null && $nextPageToken !== '' && count($all) < $max);

        $all = array_slice($all, 0, $max);

        if ($all !== [] && ! isset($all[0]['fields'])) {
            $all = $this->hydrateIssues($credentials, $all);
        }

        return $all;
    }

    /**
     * Enhanced JQL API может отдавать issues или values; иногда — только id/key без fields.
     *
     * @return list<array<string, mixed>>
     */
    private function extractIssuesFromSearchResponse(Response $response): array
    {
        $issues = $response->json('issues');
        if (is_array($issues) && $issues !== []) {
            return $issues;
        }

        $values = $response->json('values');
        if (is_array($values) && $values !== []) {
            return $values;
        }

        return [];
    }

    /**
     * @param  list<array<string, mixed>>  $stubs
     * @return list<array<string, mixed>>
     */
    private function hydrateIssues(array $credentials, array $stubs): array
    {
        $keys = [];
        foreach ($stubs as $stub) {
            if (! empty($stub['key'])) {
                $keys[] = (string) $stub['key'];
            } elseif (! empty($stub['id'])) {
                $keys[] = (string) $stub['id'];
            }
        }

        $keys = array_values(array_unique($keys));
        if ($keys === []) {
            return [];
        }

        $hydrated = [];
        foreach (array_chunk($keys, 100) as $chunk) {
            $response = $this->client($credentials)->post('/rest/api/3/issue/bulkfetch', [
                'issueIdsOrKeys' => $chunk,
                'fields' => self::ISSUE_FIELDS,
            ]);

            if (! $response->successful()) {
                throw new RuntimeException('Jira bulkfetch: '.$response->body());
            }

            foreach ($response->json('issues', []) as $issue) {
                if (is_array($issue)) {
                    $hydrated[] = $issue;
                }
            }
        }

        return $hydrated;
    }

    private function withFetchMeta(WorkProgressCollection $collection, int $issuesFetched, string $jql): WorkProgressCollection
    {
        return new WorkProgressCollection(
            provider: $collection->provider,
            employees: $collection->employees,
            warnings: $collection->warnings,
            issuesFetched: $issuesFetched,
            jql: $jql,
        );
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

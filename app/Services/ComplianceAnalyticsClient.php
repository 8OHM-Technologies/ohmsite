<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ComplianceAnalyticsClient
{
    protected string $baseUrl;
    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.compliance_analytics.url', 'http://127.0.0.1:8086'), '/');
        $this->timeout = (int) config('services.compliance_analytics.timeout', 10);
    }

    /**
     * Check if the compliance analytics service is online and healthy.
     */
    public function isHealthy(): bool
    {
        try {
            $response = Http::timeout(3)->get("{$this->baseUrl}/health");
            return $response->successful() && ($response->json('status') === 'healthy');
        } catch (\Throwable $e) {
            Log::warning('ComplianceAnalyticsClient: Health check failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get aggregated compliance summary metrics.
     *
     * @return array<string, mixed>
     */
    public function getSummary(): array
    {
        return Cache::remember('compliance_analytics_summary', 120, function () {
            try {
                $response = Http::timeout($this->timeout)->get("{$this->baseUrl}/api/v1/compliance/summary");
                if ($response->successful()) {
                    return $response->json();
                }
                Log::error('ComplianceAnalyticsClient: Summary request returned non-200', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            } catch (\Throwable $e) {
                Log::error('ComplianceAnalyticsClient: Failed to fetch summary', ['error' => $e->getMessage()]);
            }

            return [
                'total_penalties_amount' => 0.0,
                'total_enforcement_records' => 0,
                'total_popia_notices' => 0,
                'total_prudential_standards' => 0,
                'total_tribunal_decisions' => 0,
                'total_ombud_determinations' => 0,
                'records_by_regulator' => [],
                'penalties_by_year' => [],
                'top_penalties' => [],
                'recent_actions' => [],
            ];
        });
    }

    /**
     * Search compliance precedents and regulatory enforcement records.
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function searchPrecedents(array $params = []): array
    {
        try {
            $queryParams = array_filter([
                'q' => $params['q'] ?? null,
                'regulator' => $params['regulator'] ?? null,
                'category' => $params['category'] ?? null,
                'min_penalty' => isset($params['min_penalty']) && $params['min_penalty'] !== '' ? (float) $params['min_penalty'] : null,
                'offset' => isset($params['offset']) ? (int) $params['offset'] : 0,
                'limit' => isset($params['limit']) ? (int) $params['limit'] : 25,
            ], fn ($v) => $v !== null && $v !== '');

            $response = Http::timeout($this->timeout)->get("{$this->baseUrl}/api/v1/compliance/precedents", $queryParams);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('ComplianceAnalyticsClient: Precedents search failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Throwable $e) {
            Log::error('ComplianceAnalyticsClient: Precedents search exception', ['error' => $e->getMessage()]);
        }

        return [
            'total' => 0,
            'records' => [],
            'regulators' => ['FSCA', 'Prudential Authority', 'Information Regulator', 'Financial Services Tribunal', 'FAIS Ombud', 'National Financial Ombud'],
            'categories' => ['regulatory', 'tribunal', 'ombud'],
        ];
    }

    /**
     * Cross-reference a specific statute section or rule across regulators and tribunals.
     *
     * @param string $statuteSection
     * @return array<string, mixed>
     */
    public function crossReference(string $statuteSection): array
    {
        try {
            $response = Http::timeout($this->timeout)->get("{$this->baseUrl}/api/v1/compliance/cross-reference", [
                'statute_section' => $statuteSection,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('ComplianceAnalyticsClient: Cross-reference failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Throwable $e) {
            Log::error('ComplianceAnalyticsClient: Cross-reference exception', ['error' => $e->getMessage()]);
        }

        return [
            'statute_section' => $statuteSection,
            'total_occurrences' => 0,
            'breakdown_by_regulator' => [],
            'common_contraventions' => [],
            'records' => [],
        ];
    }

    /**
     * Fetch complete compliance profile for a financial institution or individual respondent.
     *
     * @param string $entityName
     * @return array<string, mixed>
     */
    public function getEntityProfile(string $entityName): array
    {
        try {
            $response = Http::timeout($this->timeout)->get("{$this->baseUrl}/api/v1/compliance/entity-profile", [
                'entity_name' => $entityName,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('ComplianceAnalyticsClient: Entity profile failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Throwable $e) {
            Log::error('ComplianceAnalyticsClient: Entity profile exception', ['error' => $e->getMessage()]);
        }

        return [
            'entity_name' => $entityName,
            'total_actions' => 0,
            'total_penalties' => 0.0,
            'involved_regulators' => [],
            'actions_timeline' => [],
        ];
    }
}

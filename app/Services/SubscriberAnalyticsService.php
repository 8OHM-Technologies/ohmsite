<?php

namespace App\Services;

use App\Models\CcmaAnalytics;
use App\Models\LegalAnalytics;
use App\Models\TargetVanity;
use Illuminate\Support\Facades\DB;

class SubscriberAnalyticsService
{
    /**
     * Return the list of available dataset filters for the selector.
     *
     * @return array<int, array{target_name: string, vanity_name: string, target_type: string}>
     */
    public function getFilters(): array
    {
        return TargetVanity::orderBy('vanity_name')
            ->get()
            ->map(fn ($v) => [
                'target_name' => $v->target_name,
                'vanity_name' => $v->vanity_name,
                'target_type' => $v->target_type,
            ])
            ->all();
    }

    /**
     * Return aggregated CCMA analytics payload for the given filter params.
     *
     * @param  array{province: string, category: string, month: string, employer: string}  $filters
     * @return array<string, mixed>
     */
    public function getCcmaPayload(array $filters = []): array
    {
        $province = $filters['province'] ?? 'All';
        $category = $filters['category'] ?? 'All';
        $month    = $filters['month'] ?? 'All';
        $employer = $filters['employer'] ?? 'All';

        $baseQuery = CcmaAnalytics::query();

        if ($province !== 'All') {
            $baseQuery->where('court_location', 'LIKE', "%{$province}%");
        }

        if ($employer !== 'All') {
            $baseQuery->where('employer', $employer);
        }

        if ($month !== 'All') {
            $monthIndex = array_search($month, ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']);
            if ($monthIndex !== false) {
                $baseQuery->whereMonth('award_date', $monthIndex + 1);
            }
        }

        // Category filter requires parsing reason_for_dismissal; apply after fetching
        $rows = $baseQuery->select([
            'employer',
            'employee',
            'court_location',
            'reason_for_dismissal',
            'award_date',
            'hearing_start',
            'hearing_end',
            'date_modified',
            'details_scraped_at',
            'title',
            'award_number',
        ])->get();

        // Client-side categorisation mapping (mirrors the Vue logic)
        $rows = $rows->map(function ($row) {
            $reason = strtolower($row->reason_for_dismissal ?? '');
            $cat = 'Other';
            if (str_contains($reason, 'misconduct')) {
                $cat = 'Misconduct';
            } elseif (str_contains($reason, 'incapacity')) {
                $cat = 'Incapacity';
            } elseif (str_contains($reason, 'unfair labour') || str_contains($reason, 'unfair labor')) {
                $cat = 'Unfair Labor Practice';
            } elseif (str_contains($reason, 'operational requirements') || str_contains($reason, 'retrenchment')) {
                $cat = 'Retrenchment';
            } elseif (str_contains($reason, 'constructive')) {
                $cat = 'Constructive Dismissal';
            } elseif (str_contains($reason, 'mutual interest')) {
                $cat = 'Mutual Interest';
            } elseif (str_contains($reason, 'unfair dismissal')) {
                $cat = 'Unfair Dismissal';
            }

            $empName = strtolower($row->employer ?? '');
            $industry = 'Other Services';
            if (str_contains($empName, 'woolworths') || str_contains($empName, 'pick n pay') || str_contains($empName, 'spar') || str_contains($empName, 'shoprite') || str_contains($empName, 'mr price') || str_contains($empName, 'truworths') || str_contains($empName, 'boxer') || str_contains($empName, 'clicks')) {
                $industry = 'Retail & Consumer Goods';
            } elseif (str_contains($empName, 'anglo american') || str_contains($empName, 'sibanye') || str_contains($empName, 'sasol') || str_contains($empName, 'impala')) {
                $industry = 'Mining & Resources';
            } elseif (str_contains($empName, 'bidvest') || str_contains($empName, 'g4s') || str_contains($empName, 'fidelity') || str_contains($empName, 'dhl')) {
                $industry = 'Security & Logistics';
            } elseif (str_contains($empName, 'transnet') || str_contains($empName, 'eskom')) {
                $industry = 'State Utilities & Transport';
            } elseif (str_contains($empName, 'mediclinic') || str_contains($empName, 'netcare') || str_contains($empName, 'unilever') || str_contains($empName, 'rainbow chicken')) {
                $industry = 'Healthcare & FMCG';
            } elseif (str_contains($empName, 'vodacom') || str_contains($empName, 'mtn') || str_contains($empName, 'standard bank') || str_contains($empName, 'capitec') || str_contains($empName, 'tsogo sun') || str_contains($empName, 'psg') || str_contains($empName, 'sun international')) {
                $industry = 'Finance, Telecoms & Leisure';
            }

            return array_merge($row->toArray(), ['category' => $cat, 'industry' => $industry]);
        });

        // Apply category filter post-mapping
        if ($category !== 'All') {
            $rows = $rows->filter(fn ($r) => $r['category'] === $category)->values();
        }

        // Build unique filter option lists from full (unfiltered) dataset
        $allRows = CcmaAnalytics::select(['court_location', 'employer', 'award_date', 'reason_for_dismissal'])->get();

        $provinces = $allRows->map(fn ($r) => $this->parseProvince($r->court_location))->filter()->unique()->sort()->values()->all();
        $employers = $allRows->map(fn ($r) => $r->employer)->filter()->unique()->sort()->values()->all();
        $months    = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        return [
            'type'       => 'ccma',
            'cases'      => $rows->values()->all(),
            'filter_options' => [
                'provinces' => $provinces,
                'employers' => $employers,
                'months'    => $months,
            ],
        ];
    }

    /**
     * Return server-side aggregated analytics payload for a given SAFLII target.
     *
     * @return array<string, mixed>
     */
    public function getLegalPayload(string $targetName): array
    {
        $vanity = TargetVanity::where('target_name', $targetName)->first();

        $base = LegalAnalytics::where('target_name', $targetName);

        $total          = (clone $base)->count();
        $withCaseNumber = (clone $base)->whereNotNull('case_number')->where('case_number', '!=', '')->count();
        $withDate       = (clone $base)->whereNotNull('document_date')->count();

        // Fetch the lightweight columns needed for aggregation
        $allRows = (clone $base)
            ->whereNotNull('document_date')
            ->select(['document_date', 'document_type', 'court'])
            ->get();

        // Volume by year — PHP-side grouping (SQLite + Postgres compatible)
        $byYear = [];
        foreach ($allRows as $row) {
            $yr = substr((string) $row->document_date, 0, 4);
            if ($yr) {
                $byYear[$yr] = ($byYear[$yr] ?? 0) + 1;
            }
        }
        ksort($byYear);

        // Volume by month name
        $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $byMonth    = [];
        foreach ($allRows as $row) {
            $mo = (int) substr((string) $row->document_date, 5, 2);
            if ($mo >= 1 && $mo <= 12) {
                $name           = $monthNames[$mo - 1];
                $byMonth[$name] = ($byMonth[$name] ?? 0) + 1;
            }
        }

        // Document type breakdown
        $byDocumentType = [];
        foreach ((clone $base)->whereNotNull('document_type')->where('document_type', '!=', '')->select('document_type')->get() as $row) {
            $byDocumentType[$row->document_type] = ($byDocumentType[$row->document_type] ?? 0) + 1;
        }
        arsort($byDocumentType);

        // Court breakdown (top 10) — PHP-side
        $courtCounts = [];
        foreach ((clone $base)->whereNotNull('court')->where('court', '!=', '')->select('court')->get() as $row) {
            $courtCounts[$row->court] = ($courtCounts[$row->court] ?? 0) + 1;
        }
        arsort($courtCounts);
        $topCourts = array_map(
            fn ($court, $count) => ['court' => $court, 'count' => $count],
            array_keys(array_slice($courtCounts, 0, 10, true)),
            array_values(array_slice($courtCounts, 0, 10, true))
        );

        // Recent records feed (last 10 by document_date)
        $recent = (clone $base)
            ->select(['title', 'case_number', 'document_date', 'court', 'source_url', 'document_type'])
            ->orderByDesc('document_date')
            ->limit(10)
            ->get()
            ->toArray();

        return [
            'type'        => 'legal',
            'target_name' => $targetName,
            'vanity_name' => $vanity?->vanity_name ?? $targetName,
            'target_type' => $vanity?->target_type ?? 'cases',
            'totals'      => [
                'total'           => $total,
                'with_case_number' => $withCaseNumber,
                'with_date'       => $withDate,
            ],
            'by_year'          => $byYear,
            'by_month'         => $byMonth,
            'by_document_type' => $byDocumentType,
            'top_courts'       => $topCourts,
            'recent'           => $recent,
        ];
    }

    /**
     * Parse a province name out of a court_location string like "Gauteng [Johannesburg]".
     */
    private function parseProvince(string $location): string
    {
        if (preg_match('/^([^\[]+)\s*\[/', $location, $m)) {
            return trim($m[1]);
        }

        return trim($location);
    }
}

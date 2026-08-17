<?php

namespace App\Services;

use App\Models\CcmaAnalytics;
use App\Models\LegalAnalytics;
use App\Models\ScrubbedRecord;
use App\Models\TargetVanity;
use Illuminate\Support\Carbon;
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
     * Return comprehensive SAFLII Courts jurisprudence analytics payload.
     *
     * @param  array{court?: string, judge?: string, year?: string, reportable?: string, search?: string}  $filters
     * @return array<string, mixed>
     */
    public function getSafliiCourtsPayload(array $filters = []): array
    {
        $courtFilter = $filters['court'] ?? 'All';
        $judgeFilter = $filters['judge'] ?? 'All';
        $yearFilter  = $filters['year'] ?? 'All';
        $reportableFilter = $filters['reportable'] ?? 'All';
        $searchFilter = trim($filters['search'] ?? '');

        // Fetch case records: first try LegalAnalytics where target_type is cases (or ZACC/ZACAC)
        $legalQuery = LegalAnalytics::query()
            ->where(function ($q) {
                $q->where('target_type', 'cases')
                  ->orWhereIn('target_name', ['ZACC', 'ZACAC', 'saflii_courts'])
                  ->orWhere('document_type', 'LIKE', '%court%');
            });

        $legalRecords = $legalQuery->get();

        $rawItems = [];

        if ($legalRecords->isNotEmpty()) {
            foreach ($legalRecords as $rec) {
                $payload = is_array($rec->data) ? $rec->data : (json_decode($rec->data ?? '{}', true) ?: []);
                $ext = is_array($payload['extracted_data'] ?? null) ? $payload['extracted_data'] : [];
                $meta = is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [];

                $judges = $ext['judges'] ?? $payload['judges'] ?? [];
                if (!is_array($judges)) {
                    $judges = $judges ? [$judges] : [];
                }
                // Filter out generic placeholder strings
                $judges = array_values(array_filter($judges, fn ($j) => !empty($j) && !str_starts_with((string)$j, '[Not explicitly')));

                $precedents = $ext['precedents_cited'] ?? $payload['precedents_cited'] ?? [];
                if (!is_array($precedents)) {
                    $precedents = [];
                }

                $hDateStr = $ext['hearing_date'] ?? $payload['hearing_date'] ?? null;
                $jDateStr = $ext['judgment_date'] ?? $meta['document_date'] ?? ($rec->document_date ? $rec->document_date->toDateString() : null);

                $durationDays = null;
                if ($hDateStr && $jDateStr) {
                    try {
                        $hDate = Carbon::parse($hDateStr);
                        $jDate = Carbon::parse($jDateStr);
                        $durationDays = max(0, $hDate->diffInDays($jDate, false));
                    } catch (\Throwable) {
                        $durationDays = null;
                    }
                }

                $rawItems[] = [
                    'id' => (string) $rec->id,
                    'extracted_record_id' => $rec->extracted_record_id,
                    'title' => $rec->title,
                    'case_number' => $rec->case_number ?: ($meta['case_number'] ?? $payload['case_number'] ?? 'N/A'),
                    'court' => $rec->court ?: ($ext['court'] ?? $rec->target_name),
                    'court_location' => $ext['court_location'] ?? $payload['court_location'] ?? 'South Africa',
                    'target_name' => $rec->target_name,
                    'document_date' => $jDateStr,
                    'hearing_date' => $hDateStr,
                    'judgment_date' => $jDateStr,
                    'duration_days' => $durationDays,
                    'reportable' => isset($ext['reportable']) ? (bool)$ext['reportable'] : (isset($payload['reportable']) ? (bool)$payload['reportable'] : true),
                    'judges' => $judges,
                    'applicant' => $rec->applicant ?: ($ext['applicant_plaintiff'] ?? 'N/A'),
                    'respondent' => $rec->respondent ?: (is_array($ext['respondent_defendant'] ?? null) ? implode(', ', $ext['respondent_defendant']) : ($ext['respondent_defendant'] ?? 'N/A')),
                    'summary' => $ext['summary'] ?? $payload['summary'] ?? null,
                    'ratio_decidendi' => $ext['ratio_decidendi'] ?? $payload['ratio_decidendi'] ?? null,
                    'obiter_dicta' => $ext['obiter_dicta'] ?? $payload['obiter_dicta'] ?? null,
                    'order' => $ext['order'] ?? $payload['order'] ?? null,
                    'precedents_cited' => $precedents,
                    'precedents_count' => count($precedents),
                    'keywords' => $ext['keywords'] ?? $payload['keywords'] ?? [],
                    'source_url' => $rec->source_url,
                ];
            }
        } else {
            // Fallback to scrubbed_records directly if local table has no cases
            try {
                $scrubbedRecords = ScrubbedRecord::query()
                    ->join('extracted_records', 'scrubbed_records.extracted_record_id', '=', 'extracted_records.id')
                    ->whereRaw("extracted_records.data->>'category' = 'cases'")
                    ->select('scrubbed_records.*', 'extracted_records.record_type', 'extracted_records.source_url as ext_source_url')
                    ->get();

                foreach ($scrubbedRecords as $s) {
                    $payload = is_array($s->data) ? $s->data : (json_decode($s->data ?? '{}', true) ?: []);
                    $ext = is_array($payload['extracted_data'] ?? null) ? $payload['extracted_data'] : [];
                    $meta = is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [];

                    $judges = $ext['judges'] ?? $payload['judges'] ?? [];
                    if (!is_array($judges)) {
                        $judges = $judges ? [$judges] : [];
                    }
                    $judges = array_values(array_filter($judges, fn ($j) => !empty($j) && !str_starts_with((string)$j, '[Not explicitly')));

                    $precedents = $ext['precedents_cited'] ?? $payload['precedents_cited'] ?? [];
                    if (!is_array($precedents)) {
                        $precedents = [];
                    }

                    $hDateStr = $ext['hearing_date'] ?? null;
                    $jDateStr = $ext['judgment_date'] ?? $meta['document_date'] ?? null;

                    $durationDays = null;
                    if ($hDateStr && $jDateStr) {
                        try {
                            $hDate = Carbon::parse($hDateStr);
                            $jDate = Carbon::parse($jDateStr);
                            $durationDays = max(0, $hDate->diffInDays($jDate, false));
                        } catch (\Throwable) {
                            $durationDays = null;
                        }
                    }

                    $rawItems[] = [
                        'id' => (string) $s->id,
                        'extracted_record_id' => $s->extracted_record_id,
                        'title' => $payload['title'] ?? 'Court Judgment',
                        'case_number' => $meta['case_number'] ?? 'N/A',
                        'court' => $ext['court'] ?? $meta['target_name'] ?? 'Superior Court',
                        'court_location' => $ext['court_location'] ?? 'South Africa',
                        'target_name' => $meta['target_name'] ?? 'saflii_courts',
                        'document_date' => $jDateStr,
                        'hearing_date' => $hDateStr,
                        'judgment_date' => $jDateStr,
                        'duration_days' => $durationDays,
                        'reportable' => isset($ext['reportable']) ? (bool)$ext['reportable'] : true,
                        'judges' => $judges,
                        'applicant' => $ext['applicant_plaintiff'] ?? 'N/A',
                        'respondent' => is_array($ext['respondent_defendant'] ?? null) ? implode(', ', $ext['respondent_defendant']) : ($ext['respondent_defendant'] ?? 'N/A'),
                        'summary' => $ext['summary'] ?? null,
                        'ratio_decidendi' => $ext['ratio_decidendi'] ?? null,
                        'obiter_dicta' => $ext['obiter_dicta'] ?? null,
                        'order' => $ext['order'] ?? null,
                        'precedents_cited' => $precedents,
                        'precedents_count' => count($precedents),
                        'keywords' => $ext['keywords'] ?? [],
                        'source_url' => $s->ext_source_url ?? null,
                    ];
                }
            } catch (\Throwable) {
                // Table doesn't exist in current connection
            }
        }

        // Global metric computations
        $totalCases = count($rawItems);
        $reportableCount = 0;
        $totalPrecedents = 0;
        $allJudges = [];
        $courtCounts = [];
        $yearsData = [];
        $durations = [];
        $precedentsFrequency = [];
        $treatmentsCount = [
            'Applied/Followed' => 0,
            'Referred' => 0,
            'Distinguished/Overruled' => 0,
            'Other' => 0,
        ];
        $densityBuckets = [
            '0' => 0,
            '1-5' => 0,
            '6-15' => 0,
            '16-30' => 0,
            '30+' => 0,
        ];
        $judgeStats = [];
        $panelSizes = [
            'Solo (1 Judge)' => 0,
            'Bench (2-3 Judges)' => 0,
            'Full Bench (4+ Judges)' => 0,
        ];

        foreach ($rawItems as $item) {
            if ($item['reportable']) {
                $reportableCount++;
            }
            $pCount = $item['precedents_count'];
            $totalPrecedents += $pCount;

            // Density bucket
            if ($pCount === 0) {
                $densityBuckets['0']++;
            } elseif ($pCount <= 5) {
                $densityBuckets['1-5']++;
            } elseif ($pCount <= 15) {
                $densityBuckets['6-15']++;
            } elseif ($pCount <= 30) {
                $densityBuckets['16-30']++;
            } else {
                $densityBuckets['30+']++;
            }

            // Precedents citations
            foreach ($item['precedents_cited'] as $prec) {
                $cName = trim((string)($prec['case_name_citation'] ?? ''));
                if ($cName) {
                    if (!isset($precedentsFrequency[$cName])) {
                        $precedentsFrequency[$cName] = [
                            'citation' => $cName,
                            'count' => 0,
                            'url' => $prec['url'] ?? null,
                            'treatment' => $prec['treatment'] ?? 'Referred',
                        ];
                    }
                    $precedentsFrequency[$cName]['count']++;
                }

                $t = $prec['treatment'] ?? 'Referred';
                if ($t === 'Applied/Followed') {
                    $treatmentsCount['Applied/Followed']++;
                } elseif ($t === 'Distinguished/Overruled') {
                    $treatmentsCount['Distinguished/Overruled']++;
                } elseif ($t === 'Referred' || $t === 'cited' || $t === 'Cited') {
                    $treatmentsCount['Referred']++;
                } else {
                    $treatmentsCount['Other']++;
                }
            }

            // Courts breakdown
            $cName = $item['court'] ?: 'Other Court';
            // Normalize court naming
            if (stripos($cName, 'constitutional') !== false) {
                $cName = 'Constitutional Court of South Africa';
            } elseif (stripos($cName, 'competition appeal') !== false) {
                $cName = 'Competition Appeal Court of South Africa';
            }
            $courtCounts[$cName] = ($courtCounts[$cName] ?? 0) + 1;

            // Timeline & duration
            if ($item['document_date']) {
                $yr = substr($item['document_date'], 0, 4);
                if ($yr) {
                    if (!isset($yearsData[$yr])) {
                        $yearsData[$yr] = ['count' => 0, 'duration_sum' => 0, 'duration_count' => 0];
                    }
                    $yearsData[$yr]['count']++;
                    if ($item['duration_days'] !== null) {
                        $yearsData[$yr]['duration_sum'] += $item['duration_days'];
                        $yearsData[$yr]['duration_count']++;
                        $durations[] = $item['duration_days'];
                    }
                }
            }

            // Judges
            $jList = $item['judges'];
            $jCount = count($jList);
            if ($jCount === 1) {
                $panelSizes['Solo (1 Judge)']++;
            } elseif ($jCount >= 2 && $jCount <= 3) {
                $panelSizes['Bench (2-3 Judges)']++;
            } elseif ($jCount >= 4) {
                $panelSizes['Full Bench (4+ Judges)']++;
            }

            foreach ($jList as $j) {
                $allJudges[$j] = true;
                if (!isset($judgeStats[$j])) {
                    $judgeStats[$j] = ['name' => $j, 'cases_count' => 0, 'precedents_sum' => 0];
                }
                $judgeStats[$j]['cases_count']++;
                $judgeStats[$j]['precedents_sum'] += $pCount;
            }
        }

        ksort($yearsData);
        $timelineYears = array_keys($yearsData);
        $timelineCounts = array_map(fn ($d) => $d['count'], array_values($yearsData));
        $timelineDurations = array_map(
            fn ($d) => $d['duration_count'] > 0 ? round($d['duration_sum'] / $d['duration_count'], 1) : 0,
            array_values($yearsData)
        );

        // Top cited authorities sorted by count desc
        uasort($precedentsFrequency, fn ($a, $b) => $b['count'] <=> $a['count']);
        $topCited = array_values(array_slice($precedentsFrequency, 0, 15));

        // Top judges sorted by cases_count desc
        uasort($judgeStats, fn ($a, $b) => $b['cases_count'] <=> $a['cases_count']);
        $topJudges = array_map(function ($j) {
            return [
                'name' => $j['name'],
                'cases_count' => $j['cases_count'],
                'avg_precedents' => $j['cases_count'] > 0 ? round($j['precedents_sum'] / $j['cases_count'], 1) : 0,
            ];
        }, array_values(array_slice($judgeStats, 0, 12)));

        arsort($courtCounts);
        $courtsBreakdown = [];
        foreach ($courtCounts as $court => $count) {
            $courtsBreakdown[] = [
                'court' => $court,
                'count' => $count,
                'percentage' => $totalCases > 0 ? round(($count / $totalCases) * 100, 1) : 0,
            ];
        }

        // Apply filters to returned cases list
        $filteredCases = array_values(array_filter($rawItems, function ($item) use ($courtFilter, $judgeFilter, $yearFilter, $reportableFilter, $searchFilter) {
            if ($courtFilter !== 'All') {
                if (stripos($item['court'], $courtFilter) === false && stripos($item['target_name'], $courtFilter) === false) {
                    return false;
                }
            }
            if ($judgeFilter !== 'All') {
                if (!in_array($judgeFilter, $item['judges'], true)) {
                    return false;
                }
            }
            if ($yearFilter !== 'All') {
                if (!$item['document_date'] || !str_starts_with($item['document_date'], $yearFilter)) {
                    return false;
                }
            }
            if ($reportableFilter !== 'All') {
                $isReportable = $reportableFilter === 'Yes';
                if ($item['reportable'] !== $isReportable) {
                    return false;
                }
            }
            if ($searchFilter !== '') {
                $needle = strtolower($searchFilter);
                $haystack = strtolower(
                    $item['title'] . ' ' .
                    $item['case_number'] . ' ' .
                    $item['applicant'] . ' ' .
                    $item['respondent'] . ' ' .
                    ($item['summary'] ?? '') . ' ' .
                    ($item['ratio_decidendi'] ?? '') . ' ' .
                    ($item['order'] ?? '')
                );
                if (strpos($haystack, $needle) === false) {
                    return false;
                }
            }
            return true;
        }));

        $avgDuration = count($durations) > 0 ? round(array_sum($durations) / count($durations), 1) : 0;

        return [
            'type' => 'saflii_courts',
            'totals' => [
                'total_cases' => $totalCases,
                'reportable_count' => $reportableCount,
                'reportable_percentage' => $totalCases > 0 ? round(($reportableCount / $totalCases) * 100, 1) : 0,
                'total_precedents' => $totalPrecedents,
                'avg_precedents_per_case' => $totalCases > 0 ? round($totalPrecedents / $totalCases, 1) : 0,
                'total_judges' => count($allJudges),
                'avg_hearing_to_judgment_days' => $avgDuration,
            ],
            'courts_breakdown' => $courtsBreakdown,
            'timeline_trend' => [
                'years' => $timelineYears,
                'counts' => $timelineCounts,
                'avg_duration_days' => $timelineDurations,
            ],
            'precedents_intelligence' => [
                'top_cited' => $topCited,
                'treatment_distribution' => $treatmentsCount,
                'density_distribution' => $densityBuckets,
            ],
            'bench_intelligence' => [
                'top_judges' => $topJudges,
                'panel_sizes' => $panelSizes,
            ],
            'cases' => $filteredCases,
            'filter_options' => [
                'courts' => array_keys($courtCounts),
                'judges' => array_values(array_keys($allJudges)),
                'years' => array_reverse($timelineYears),
            ],
        ];
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

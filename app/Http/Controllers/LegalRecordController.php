<?php

namespace App\Http\Controllers;

use App\Models\TargetVanity;
use App\Services\ComplianceAnalyticsClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class LegalRecordController extends Controller
{
    public function __construct(
        protected ComplianceAnalyticsClient $complianceClient
    ) {}

    /**
     * Display the legal record index view (defaults to cases).
     */
    public function index(Request $request): InertiaResponse
    {
        return $this->cases($request);
    }

    /**
     * Display the Case Law & Court Judgments view.
     */
    public function cases(Request $request): InertiaResponse
    {
        $filters = TargetVanity::where('target_type', 'cases')
            ->orderBy('vanity_name')
            ->get()
            ->map(function ($vanity) {
                return [
                    'target_name' => $vanity->target_name,
                    'vanity_name' => $vanity->vanity_name,
                    'target_type' => $vanity->target_type,
                ];
            });

        return Inertia::render('Subscriber/LegalRecords/Cases', [
            'filters' => $filters,
        ]);
    }

    /**
     * Display the Compliance Precedent Search & Statutory Cross-Reference view.
     */
    public function precedents(Request $request): InertiaResponse
    {
        return Inertia::render('Subscriber/LegalRecords/Precedents', [
            'initialSummary' => $this->complianceClient->getSummary(),
        ]);
    }

    /**
     * Search compliance precedents via FastAPI DuckDB microservice.
     */
    public function precedentsData(Request $request): JsonResponse
    {
        return response()->json($this->complianceClient->searchPrecedents($request->all()));
    }

    /**
     * Cross reference statute section across regulators and tribunals.
     */
    public function crossReference(Request $request): JsonResponse
    {
        $section = trim((string) $request->input('statute_section', ''));
        return response()->json($this->complianceClient->crossReference($section));
    }

    /**
     * Fetch entity compliance profile and sanctions timeline.
     */
    public function entityProfile(Request $request): JsonResponse
    {
        $entityName = trim((string) $request->input('entity_name', ''));
        return response()->json($this->complianceClient->getEntityProfile($entityName));
    }

    /**
     * Display the Law Journals & Official Gazettes view.
     */
    public function journals(Request $request): InertiaResponse
    {
        $filters = TargetVanity::whereIn('target_type', ['journals', 'gaz'])
            ->orderBy('vanity_name')
            ->get()
            ->map(function ($vanity) {
                return [
                    'target_name' => $vanity->target_name,
                    'vanity_name' => $vanity->vanity_name,
                    'target_type' => $vanity->target_type,
                ];
            });

        return Inertia::render('Subscriber/LegalRecords/Journals', [
            'filters' => $filters,
        ]);
    }

    /**
     * Display the Court Rolls & Hearing Schedules view.
     */
    public function courtRolls(Request $request): InertiaResponse
    {
        $filters = TargetVanity::where('target_type', 'other')
            ->orderBy('vanity_name')
            ->get()
            ->map(function ($vanity) {
                return [
                    'target_name' => $vanity->target_name,
                    'vanity_name' => $vanity->vanity_name,
                    'target_type' => $vanity->target_type,
                ];
            });

        return Inertia::render('Subscriber/LegalRecords/CourtRolls', [
            'filters' => $filters,
        ]);
    }

    /**
     * Return paginated JSON records directly from scrubbed_records on pgsql_coeus connection.
     */
    public function data(Request $request): JsonResponse
    {
        $offset = max(0, (int) $request->input('offset', 0));
        $limit = min(100, max(1, (int) $request->input('limit', 25)));
        $search = trim((string) $request->input('search', ''));
        $category = trim((string) $request->input('category', 'all'));
        $recordType = trim((string) $request->input('record_type', ''));
        $sortField = trim((string) $request->input('sort_field', 'document_date'));
        $rawSortOrder = $request->input('sort_order', -1);
        $sortOrder = in_array($rawSortOrder, [1, '1', 'asc', 'ASC'], true) ? 'asc' : 'desc';

        $user = auth()->user();
        $isPro = $user && ($user->isAdmin() || $user->hasLegalProAccess());

        $isPgsql = DB::connection('pgsql_coeus')->getDriverName() === 'pgsql';

        $selectData = $isPgsql
            ? DB::raw("(scrubbed_records.data - 'formatted_text' - 'full_text' - 'content' - 'body' - 'text') as data")
            : 'scrubbed_records.data';

        $query = DB::connection('pgsql_coeus')->table('scrubbed_records')
            ->join('extracted_records', 'extracted_records.id', '=', 'scrubbed_records.extracted_record_id')
            ->select([
                'scrubbed_records.id',
                'scrubbed_records.extracted_record_id',
                $selectData,
                'extracted_records.record_type',
                'extracted_records.source_url',
                'extracted_records.document_date',
                'extracted_records.requires_human_review',
                'extracted_records.review_reason',
                'scrubbed_records.created_at',
            ]);

        $useFunctionalIndex = $isPgsql && ! app()->runningUnitTests();

        // Category filter
        if ($category === 'cases') {
            // Case law records flagged for human review must never appear in the frontend Case Law module
            $query->where(function ($q) {
                $q->where('extracted_records.requires_human_review', false)
                    ->orWhereNull('extracted_records.requires_human_review');
            });

            // Exclude regulatory enforcement, prudential standards, ombud determinations, and tribunal decisions from Case Law
            $query->whereNotIn('extracted_records.record_type', [
                'fsca_enforcement_records',
                'fsca_regulatory_records',
                'pa_insurance_records',
                'popia_records',
                'nfo_cases',
                'fais_ombud_cases',
                'fais_determinations',
                'fst_cases',
                'fst_decisions',
            ]);

            if ($useFunctionalIndex) {
                $query->whereRaw("get_scrubbed_record_category(scrubbed_records.data) = 'cases'");
            } else {
                $query->where(function ($q) use ($isPgsql) {
                    if ($isPgsql) {
                        $categorySql = "COALESCE(scrubbed_records.data->'extracted_data'->>'category', scrubbed_records.data->'metadata'->>'category', scrubbed_records.data->>'category')";
                        $q->where('extracted_records.record_type', 'sabinet_ccma')
                            ->orWhereRaw("{$categorySql} = 'cases'")
                            ->orWhereRaw("{$categorySql} IS NULL AND (jsonb_exists(scrubbed_records.data, 'metadata') OR jsonb_exists(scrubbed_records.data, 'extracted_data') OR jsonb_exists(scrubbed_records.data, 'case_number')) AND NOT (jsonb_exists(scrubbed_records.data, 'formatted_text') OR jsonb_exists(scrubbed_records.data, 'roll_type'))");
                    } else {
                        $categorySql = "COALESCE(json_extract(scrubbed_records.data, '$.extracted_data.category'), json_extract(scrubbed_records.data, '$.metadata.category'), json_extract(scrubbed_records.data, '$.category'), json_extract(extracted_records.data, '$.category'))";
                        $q->where('extracted_records.record_type', 'sabinet_ccma')
                            ->orWhereRaw("{$categorySql} = 'cases'");
                    }
                });
            }
        } elseif ($category === 'regulatory') {
            if ($useFunctionalIndex) {
                $query->whereRaw("get_scrubbed_record_category(scrubbed_records.data) = 'regulatory'");
            } else {
                $query->whereIn('extracted_records.record_type', [
                    'fsca_enforcement_records',
                    'fsca_regulatory_records',
                    'pa_insurance_records',
                    'popia_records',
                ]);
            }
        } elseif ($category === 'tribunal') {
            if ($useFunctionalIndex) {
                $query->whereRaw("get_scrubbed_record_category(scrubbed_records.data) = 'tribunal'");
            } else {
                $query->whereIn('extracted_records.record_type', [
                    'fst_cases',
                    'fst_decisions',
                ]);
            }
        } elseif ($category === 'ombud') {
            if ($useFunctionalIndex) {
                $query->whereRaw("get_scrubbed_record_category(scrubbed_records.data) = 'ombud'");
            } else {
                $query->whereIn('extracted_records.record_type', [
                    'nfo_cases',
                    'fais_ombud_cases',
                    'fais_determinations',
                ]);
            }
        } elseif ($category === 'journals') {
            if ($useFunctionalIndex) {
                $query->whereRaw("get_scrubbed_record_category(scrubbed_records.data) = 'journals'");
            } else {
                $query->where(function ($q) use ($isPgsql) {
                    if ($isPgsql) {
                        $categorySql = "COALESCE(scrubbed_records.data->'extracted_data'->>'category', scrubbed_records.data->'metadata'->>'category', scrubbed_records.data->>'category')";
                        $q->whereRaw("{$categorySql} IN ('journals', 'gaz')")
                            ->orWhereRaw("jsonb_exists(scrubbed_records.data, 'formatted_text')")
                            ->orWhere('extracted_records.record_type', 'like', '%journal%')
                            ->orWhere('extracted_records.record_type', 'like', '%gaz%');
                    } else {
                        $categorySql = "COALESCE(json_extract(scrubbed_records.data, '$.extracted_data.category'), json_extract(scrubbed_records.data, '$.metadata.category'), json_extract(scrubbed_records.data, '$.category'), json_extract(extracted_records.data, '$.category'))";
                        $q->whereRaw("{$categorySql} IN ('journals', 'gaz')")
                            ->orWhere('extracted_records.record_type', 'like', '%journal%')
                            ->orWhere('extracted_records.record_type', 'like', '%gaz%');
                    }
                });
            }
        } elseif ($category === 'court_rolls') {
            if ($useFunctionalIndex) {
                $query->whereRaw("get_scrubbed_record_category(scrubbed_records.data) = 'court_rolls'");
            } else {
                $query->where(function ($q) use ($isPgsql) {
                    if ($isPgsql) {
                        $categorySql = "COALESCE(scrubbed_records.data->'extracted_data'->>'category', scrubbed_records.data->'metadata'->>'category', scrubbed_records.data->>'category')";
                        $q->whereRaw("{$categorySql} = 'other'")
                            ->orWhereRaw("(jsonb_exists(scrubbed_records.data, 'roll_type') OR jsonb_exists(scrubbed_records.data, 'rows'))")
                            ->orWhere('extracted_records.record_type', 'like', '%roll%');
                    } else {
                        $categorySql = "COALESCE(json_extract(scrubbed_records.data, '$.extracted_data.category'), json_extract(scrubbed_records.data, '$.metadata.category'), json_extract(scrubbed_records.data, '$.category'), json_extract(extracted_records.data, '$.category'))";
                        $q->whereRaw("{$categorySql} = 'other'")
                            ->orWhere('extracted_records.record_type', 'like', '%roll%');
                    }
                });
            }
        }

        // Record type / target filter
        if ($recordType !== '' && $recordType !== 'all') {
            $query->where(function ($q) use ($recordType, $isPgsql) {
                $targetSql = $isPgsql
                    ? "COALESCE(scrubbed_records.data->'metadata'->>'target_name', scrubbed_records.data->'extracted_data'->>'target_name', scrubbed_records.data->>'target_name')"
                    : "COALESCE(json_extract(scrubbed_records.data, '$.metadata.target_name'), json_extract(scrubbed_records.data, '$.extracted_data.target_name'), json_extract(extracted_records.data, '$.target_name'))";

                $q->where('extracted_records.record_type', $recordType)
                    ->orWhereRaw("{$targetSql} = ?", [$recordType]);
            });
        }

        // Search query: support exact quoted phrases or space-separated tokens across scrubbed_records JSON data
        if ($search !== '') {
            $isExactPhrase = (bool) preg_match('/^"[^"]+"$/', $search);
            if ($isExactPhrase) {
                $phrase = trim($search, '"');
                $query->where(function ($q) use ($phrase, $isPgsql) {
                    if ($isPgsql) {
                        $q->whereRaw('scrubbed_records.data::text ILIKE ?', ["%{$phrase}%"]);
                    } else {
                        $q->where(function ($sub) use ($phrase) {
                            $sub->whereRaw('scrubbed_records.data LIKE ?', ["%{$phrase}%"])
                                ->orWhereRaw('extracted_records.data LIKE ?', ["%{$phrase}%"]);
                        });
                    }
                });
            } else {
                $rawTokens = preg_split('/\s+/', $search);
                $tokens = array_slice(array_values(array_filter($rawTokens, fn ($t) => mb_strlen($t) >= 2)), 0, 6);

                if (count($tokens) <= 1) {
                    $query->where(function ($q) use ($search, $isPgsql) {
                        if ($isPgsql) {
                            $q->whereRaw('scrubbed_records.data::text ILIKE ?', ["%{$search}%"]);
                        } else {
                            $q->where(function ($sub) use ($search) {
                                $sub->whereRaw('scrubbed_records.data LIKE ?', ["%{$search}%"])
                                    ->orWhereRaw('extracted_records.data LIKE ?', ["%{$search}%"]);
                            });
                        }
                    });
                } else {
                    $query->where(function ($q) use ($tokens, $isPgsql) {
                        if ($isPgsql) {
                            foreach ($tokens as $token) {
                                $q->whereRaw('scrubbed_records.data::text ILIKE ?', ["%{$token}%"]);
                            }
                        } else {
                            foreach ($tokens as $token) {
                                $q->where(function ($sub) use ($token) {
                                    $sub->whereRaw('scrubbed_records.data LIKE ?', ["%{$token}%"])
                                        ->orWhereRaw('extracted_records.data LIKE ?', ["%{$token}%"]);
                                });
                            }
                        }
                    });
                }
            }
        }

        $cacheVersion = (int) Cache::get('legal_records:version', 1);

        $countCacheKey = "legal_records:v{$cacheVersion}:count:".md5(serialize([
            'category' => $category,
            'record_type' => $recordType,
            'search' => mb_strtolower($search),
        ]));
        $total = app()->runningUnitTests()
            ? (clone $query)->count()
            : Cache::remember($countCacheKey, 300, function () use ($query) {
                return (clone $query)->count();
            });

        $docDateSql = $isPgsql
            ? "COALESCE(
                NULLIF(scrubbed_records.data->'extracted_data'->>'judgment_date', ''),
                NULLIF(scrubbed_records.data->'extracted_data'->>'award_date', ''),
                NULLIF(scrubbed_records.data->'extracted_data'->>'hearing_date', ''),
                NULLIF(scrubbed_records.data->'metadata'->>'document_date', ''),
                NULLIF(scrubbed_records.data->'metadata'->>'hearing_date', '')
            )"
            : "COALESCE(
                json_extract(scrubbed_records.data, '$.extracted_data.judgment_date'),
                json_extract(scrubbed_records.data, '$.extracted_data.award_date'),
                json_extract(scrubbed_records.data, '$.extracted_data.hearing_date'),
                json_extract(scrubbed_records.data, '$.metadata.document_date'),
                json_extract(scrubbed_records.data, '$.metadata.hearing_date'),
                extracted_records.document_date
            )";

        if ($sortField === 'document_date') {
            $query->orderByRaw("{$docDateSql} {$sortOrder} NULLS LAST")
                ->orderBy('scrubbed_records.created_at', 'desc');
        } elseif ($sortField === 'created_at') {
            $query->orderBy('scrubbed_records.created_at', $sortOrder)
                ->orderByRaw("{$docDateSql} desc NULLS LAST");
        } elseif ($sortField === 'case_number') {
            $caseNumSql = $isPgsql
                ? "COALESCE(scrubbed_records.data->'metadata'->>'case_number', scrubbed_records.data->'extracted_data'->>'case_number', scrubbed_records.data->>'case_number', '')"
                : "COALESCE(json_extract(scrubbed_records.data, '$.metadata.case_number'), json_extract(scrubbed_records.data, '$.extracted_data.case_number'), json_extract(scrubbed_records.data, '$.case_number'), '')";
            $query->orderByRaw("{$caseNumSql} {$sortOrder}")
                ->orderByRaw("{$docDateSql} desc NULLS LAST");
        } elseif ($sortField === 'court') {
            $courtSql = $isPgsql
                ? "COALESCE(scrubbed_records.data->'extracted_data'->>'court', scrubbed_records.data->'metadata'->>'court', scrubbed_records.data->'metadata'->>'target_name', extracted_records.record_type, '')"
                : "COALESCE(json_extract(scrubbed_records.data, '$.extracted_data.court'), json_extract(scrubbed_records.data, '$.metadata.court'), json_extract(scrubbed_records.data, '$.metadata.target_name'), extracted_records.record_type, '')";
            $query->orderByRaw("{$courtSql} {$sortOrder}")
                ->orderByRaw("{$docDateSql} desc NULLS LAST");
        } else {
            $query->orderByRaw("{$docDateSql} {$sortOrder} NULLS LAST")
                ->orderBy('scrubbed_records.created_at', 'desc');
        }

        $dataCacheKey = "legal_records:v{$cacheVersion}:data:".md5(serialize([
            'offset' => $offset,
            'limit' => $limit,
            'search' => mb_strtolower($search),
            'category' => $category,
            'record_type' => $recordType,
            'sort_field' => $sortField,
            'sort_order' => $sortOrder,
            'is_pro' => $isPro,
        ]));

        $records = app()->runningUnitTests()
            ? $query->offset($offset)->limit($limit)->get()->map(fn ($row) => $this->formatScrubbedRecord($row, $isPro, false))
            : Cache::remember($dataCacheKey, 60, function () use ($query, $offset, $limit, $isPro) {
                return $query->offset($offset)->limit($limit)->get()->map(fn ($row) => $this->formatScrubbedRecord($row, $isPro, false));
            });

        return response()->json([
            'total' => $total,
            'records' => $records,
            'is_pro' => $isPro,
        ]);
    }

    /**
     * Return single record full detail payload directly from scrubbed_records.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        if (! Str::isUuid($id)) {
            abort(404, 'Record not found in scrubbed records.');
        }

        $user = auth()->user();
        $isPro = $user && ($user->isAdmin() || $user->hasLegalProAccess());

        $scrubbed = DB::connection('pgsql_coeus')->table('scrubbed_records')
            ->join('extracted_records', 'extracted_records.id', '=', 'scrubbed_records.extracted_record_id')
            ->where(function ($q) use ($id) {
                $q->where('scrubbed_records.id', $id)
                    ->orWhere('scrubbed_records.extracted_record_id', $id);
            })
            ->select([
                'scrubbed_records.id',
                'scrubbed_records.extracted_record_id',
                'scrubbed_records.data',
                'extracted_records.record_type',
                'extracted_records.source_url',
                'extracted_records.document_date',
                'extracted_records.requires_human_review',
                'extracted_records.review_reason',
                'extracted_records.data as er_data',
            ])
            ->first();

        if (! $scrubbed) {
            $extracted = DB::connection('pgsql_coeus')->table('extracted_records')
                ->where('id', $id)
                ->first();

            if ($extracted) {
                $scrubbed = (object) [
                    'id' => (string) $extracted->id,
                    'extracted_record_id' => (string) $extracted->id,
                    'data' => $extracted->data,
                    'record_type' => $extracted->record_type,
                    'source_url' => $extracted->source_url,
                    'document_date' => $extracted->document_date,
                    'requires_human_review' => (bool) ($extracted->requires_human_review ?? false),
                    'review_reason' => $extracted->review_reason ?? null,
                    'er_data' => $extracted->data,
                ];
            }
        }

        if ($scrubbed) {
            $formatted = $this->formatScrubbedRecord($scrubbed, $isPro, true);

            // Case law records flagged for human review must not appear in the frontend module
            if ((bool) ($scrubbed->requires_human_review ?? false) && ($formatted['category'] ?? '') === 'cases' && ! ($user && $user->isAdmin())) {
                abort(404, 'Record is currently undergoing human review and is unavailable.');
            }

            return response()->json([
                'id' => (string) $scrubbed->id,
                'extracted_record_id' => (string) $scrubbed->extracted_record_id,
                'source_table' => 'scrubbed',
                'record_type' => $scrubbed->record_type,
                'document_date' => $formatted['document_date'] ?? null,
                'source_url' => $formatted['source_url'] ?? null,
                'requires_human_review' => (bool) ($scrubbed->requires_human_review ?? false),
                'review_reason' => $scrubbed->review_reason ?? null,
                'is_pro' => $isPro,
                'data' => $formatted,
            ]);
        }

        abort(404, 'Record not found in scrubbed records.');
    }

    /**
     * Report a legal record as containing errors, flagging it for human review.
     */
    public function reportError(Request $request, string $id): JsonResponse
    {
        if (! Str::isUuid($id)) {
            return response()->json(['error' => 'Record not found.'], 404);
        }

        $user = auth()->user();
        $extracted = DB::connection('pgsql_coeus')->table('extracted_records')
            ->where('id', $id)
            ->first();

        if (! $extracted) {
            $scrubbedRecord = DB::connection('pgsql_coeus')->table('scrubbed_records')
                ->where('id', $id)
                ->first();

            if ($scrubbedRecord && $scrubbedRecord->extracted_record_id) {
                $extracted = DB::connection('pgsql_coeus')->table('extracted_records')
                    ->where('id', $scrubbedRecord->extracted_record_id)
                    ->first();
            }
        }

        if (! $extracted) {
            return response()->json(['error' => 'Record not found.'], 404);
        }

        $reason = 'Reported by user ('.($user?->email ?? 'subscriber').') as containing errors.';

        DB::connection('pgsql_coeus')->table('extracted_records')
            ->where('id', $extracted->id)
            ->update([
                'requires_human_review' => true,
                'review_reason' => $reason,
                'updated_at' => now(),
            ]);

        if (! Cache::has('legal_records:version')) {
            Cache::forever('legal_records:version', 2);
        } else {
            Cache::increment('legal_records:version');
        }

        return response()->json([
            'success' => true,
            'extracted_record_id' => (string) $extracted->id,
            'requires_human_review' => true,
            'review_reason' => $reason,
            'message' => 'Thank you. This record has been reported and flagged for quality review.',
        ]);
    }

    /**
     * Format a scrubbed record from pgsql_coeus into a structured dossier item.
     */
    private function formatScrubbedRecord($row, bool $isPro = true, bool $isDetail = false): array
    {
        $srData = is_array($row->data) ? $row->data : (json_decode($row->data ?? '{}', true) ?: []);
        $erData = isset($row->er_data) ? (is_array($row->er_data) ? $row->er_data : json_decode($row->er_data ?? '{}', true)) : [];
        $ext = is_array($srData['extracted_data'] ?? null) ? $srData['extracted_data'] : [];
        $meta = is_array($srData['metadata'] ?? null) ? $srData['metadata'] : (is_array($erData['metadata'] ?? null) ? $erData['metadata'] : []);

        $title = $srData['title'] ?? $erData['title'] ?? $ext['title'] ?? 'Legal Matter';
        $court = $ext['court'] ?? $meta['court'] ?? $meta['target_name'] ?? null;
        $caseNumber = $meta['case_number'] ?? $ext['case_number'] ?? $srData['case_number'] ?? $srData['award_number'] ?? null;
        if (empty($caseNumber) || preg_match('/^\[?\d{4}\]?\s*ZA/i', trim((string) $caseNumber))) {
            if (preg_match('/\(([^()]+)\)\s*\[\d{4}\]\s*ZA/i', (string) $title, $mCase)) {
                $cand = trim($mCase[1], " ;,()");
                if (preg_match('/\d/', $cand) && ! preg_match('/judgment|appeal|heard|delivered|unreported|coram/i', $cand)) {
                    $caseNumber = $cand;
                }
            }
        }
        $docDate = $ext['judgment_date'] ?? $ext['award_date'] ?? $ext['hearing_date'] ?? $meta['document_date'] ?? $meta['hearing_date'] ?? ($row->document_date ? substr((string) $row->document_date, 0, 10) : null);
        $hearingDate = $ext['hearing_date'] ?? $meta['hearing_date'] ?? null;

        $applicant = $ext['applicant_plaintiff'] ?? $srData['applicant_plaintiff'] ?? $ext['employee'] ?? $srData['employee'] ?? $meta['publisher'] ?? null;
        if (is_array($applicant)) {
            $applicant = implode(', ', $applicant);
        }

        $respondent = $ext['respondent_defendant'] ?? $srData['respondent_defendant'] ?? $ext['employer'] ?? $srData['employer'] ?? null;
        if (is_array($respondent)) {
            $respondent = implode(', ', $respondent);
        }

        $judges = $ext['judges'] ?? $srData['judges'] ?? [];
        if (! is_array($judges)) {
            $judges = $judges ? [$judges] : [];
        }
        $judges = array_values(array_filter($judges, fn ($j) => ! empty($j) && ! str_starts_with((string) $j, '[Not explicitly')));

        $precedentsCited = $ext['precedents_cited'] ?? $srData['precedents_cited'] ?? [];
        if (! is_array($precedentsCited)) {
            $precedentsCited = [];
        }

        $reportable = isset($ext['reportable']) ? (bool) $ext['reportable'] : true;
        $durationDays = isset($ext['duration_days']) ? (int) $ext['duration_days'] : null;
        $courtLocation = $ext['court_location'] ?? $meta['court_location'] ?? null;
        $ratioDecidendi = $ext['ratio_decidendi'] ?? $srData['ratio_decidendi'] ?? null;
        $obiterDicta = $ext['obiter_dicta'] ?? $srData['obiter_dicta'] ?? null;
        $order = $ext['order'] ?? $srData['order'] ?? null;
        $summary = $srData['ai_summary'] ?? $ext['summary'] ?? $srData['summary'] ?? null;
        $subjects = $ext['subjects'] ?? $srData['subjects'] ?? null;
        $outcome = $ext['result'] ?? $order ?? null;
        $sourceUrl = $row->source_url ?? $ext['source_url'] ?? $meta['source_url'] ?? null;

        $recordType = (string) ($row->record_type ?? '');
        $isCompliance = str_contains($recordType, 'fsca')
            || str_contains($recordType, 'pa_')
            || str_contains($recordType, 'popia')
            || str_contains($recordType, 'fst')
            || str_contains($recordType, 'fais')
            || str_contains($recordType, 'nfo');

        $category = $ext['category'] ?? $srData['category'] ?? $erData['category'] ?? null;
        if (! $category) {
            if ($row->record_type === 'sabinet_ccma') {
                $category = 'cases';
            } elseif (str_contains($recordType, 'gaz')) {
                $category = 'gaz';
            } elseif (str_contains($recordType, 'journal')) {
                $category = 'journals';
            } elseif (str_contains($recordType, 'roll')) {
                $category = 'court_rolls';
            } elseif (str_contains($recordType, 'fsca') || str_contains($recordType, 'pa_') || str_contains($recordType, 'popia')) {
                $category = 'regulatory';
            } elseif (str_contains($recordType, 'fst')) {
                $category = 'tribunal';
            } elseif (str_contains($recordType, 'fais') || str_starts_with($recordType, 'nfo') || str_contains($recordType, '_nfo')) {
                $category = 'ombud';
            } else {
                $category = 'cases';
            }
        }

        // Parse compliance and regulatory specific fields
        $innerSrData = is_array($srData['data'] ?? null) ? $srData['data'] : [];
        $innerErData = is_array($erData['data'] ?? null) ? $erData['data'] : [];

        $rawPenalty = $ext['penalty_amount'] ?? $ext['administrative_penalty_amount'] ?? $ext['fine_amount'] ?? $ext['award_amount'] ?? $srData['penalty_amount'] ?? $innerSrData['penalty_amount'] ?? $erData['penalty_amount'] ?? $innerErData['penalty_amount'] ?? null;
        $penaltyAmount = null;
        if ($rawPenalty !== null && $rawPenalty !== '') {
            $cleaned = is_numeric($rawPenalty) ? (float) $rawPenalty : (float) preg_replace('/[^0-9.]/', '', (string) $rawPenalty);
            $penaltyAmount = $cleaned > 0 ? $cleaned : null;
        }

        $rawSanctions = $ext['sanctions'] ?? $ext['sanction_outcome'] ?? $ext['enforcement_action'] ?? $ext['final_order'] ?? $srData['sanctions'] ?? $innerSrData['sanction_outcome'] ?? $erData['sanctions'] ?? [];
        $sanctions = is_array($rawSanctions) ? array_values(array_filter($rawSanctions)) : (is_string($rawSanctions) && trim($rawSanctions) ? [trim($rawSanctions)] : []);

        $rawContraventions = $ext['contraventions'] ?? $ext['statutory_contraventions'] ?? $ext['contravention_findings'] ?? $ext['repudiation_grounds'] ?? $srData['contraventions'] ?? $innerSrData['statutory_contraventions'] ?? $erData['contraventions'] ?? [];
        $contraventions = is_array($rawContraventions) ? array_values(array_filter($rawContraventions)) : (is_string($rawContraventions) && trim($rawContraventions) ? [trim($rawContraventions)] : []);

        $rawProvisions = $ext['key_provisions'] ?? $ext['statutory_sections_cited'] ?? $ext['keywords'] ?? $srData['key_provisions'] ?? $innerSrData['statutory_sections_cited'] ?? $erData['key_provisions'] ?? [];
        $keyProvisions = is_array($rawProvisions) ? array_values(array_filter($rawProvisions)) : (is_string($rawProvisions) && trim($rawProvisions) ? array_values(array_filter(array_map('trim', explode(',', $rawProvisions)))) : []);

        $regulator = $ext['regulator'] ?? $srData['regulator'] ?? null;
        if (! $regulator) {
            if (str_contains($recordType, 'fsca')) $regulator = 'FSCA';
            elseif (str_contains($recordType, 'pa_')) $regulator = 'Prudential Authority';
            elseif (str_contains($recordType, 'popia')) $regulator = 'Information Regulator';
            elseif (str_contains($recordType, 'fst')) $regulator = 'Financial Services Tribunal';
            elseif (str_contains($recordType, 'fais')) $regulator = 'FAIS Ombud';
            elseif (str_contains($recordType, 'nfo') || str_starts_with($recordType, 'nfo')) $regulator = 'National Financial Ombud';
        }

        $actionType = $ext['action_type'] ?? $srData['action_type'] ?? $innerSrData['document_type'] ?? $innerSrData['document_category'] ?? $innerSrData['division'] ?? $srData['document_type'] ?? null;

        if (! $respondent) {
            $respondent = $ext['respondent'] ?? $ext['respondent_party'] ?? $ext['respondent_fsp'] ?? $ext['respondent_insurer'] ?? $srData['respondent'] ?? $innerSrData['respondent'] ?? $erData['respondent'] ?? $innerErData['respondent'] ?? null;
        }

        if (! $applicant) {
            $applicant = $ext['applicant'] ?? $ext['complainant'] ?? $srData['applicant'] ?? $innerSrData['applicant'] ?? $erData['applicant'] ?? $innerErData['applicant'] ?? $regulator;
        }

        if ($title === 'Legal Matter' || empty($title)) {
            $title = $innerSrData['title'] ?? $innerErData['title'] ?? $srData['title'] ?? $erData['title'] ?? $ext['title'] ?? ($actionType ? "{$regulator}: {$actionType}" : 'Compliance Record');
        }

        if (empty($caseNumber)) {
            $caseNumber = $innerSrData['case_number'] ?? $innerErData['case_number'] ?? $srData['dataset_number'] ?? $innerSrData['dataset_number'] ?? null;
        }

        $debarment = $ext['debarment_period'] ?? $ext['debarment'] ?? null;

        // Only parse full text and heavy content when single-record detail is requested
        $fullText = null;
        $centerContent = null;
        $rollEntries = [];
        if ($isDetail) {
            $fullText = $srData['full_text'] ?? $srData['text'] ?? $srData['content'] ?? $srData['body'] ?? $erData['full_text'] ?? $erData['text'] ?? $erData['content'] ?? $ext['full_text'] ?? $ext['content'] ?? $innerSrData['scraped_text'] ?? $innerSrData['center_content'] ?? $srData['scraped_text'] ?? $innerErData['scraped_text'] ?? null;
            $centerContent = $srData['center_content'] ?? $erData['center_content'] ?? $innerSrData['center_content'] ?? null;
            $rollEntries = $ext['roll_entries'] ?? $ext['schedule'] ?? $srData['roll_entries'] ?? $srData['schedule'] ?? $srData['entries'] ?? $erData['roll_entries'] ?? $erData['entries'] ?? [];
            if (! is_array($rollEntries)) {
                $rollEntries = [];
            }
        }

        if (empty($summary)) {
            $summary = $innerSrData['subject_matter'] ?? $ext['factual_summary'] ?? $ext['ombud_findings'] ?? $ext['tribunal_reasoning'] ?? null;
        }

        $author = $ext['author'] ?? $srData['author'] ?? $meta['author'] ?? $meta['publisher'] ?? $applicant ?? null;
        $citation = $ext['citation'] ?? $srData['citation'] ?? $meta['citation'] ?? $caseNumber ?? null;

        if (! $isPro) {
            return [
                'id' => (string) $row->id,
                'extracted_record_id' => isset($row->extracted_record_id) ? (string) $row->extracted_record_id : null,
                'source_table' => 'scrubbed',
                'record_type' => $row->record_type ?? 'saflii_courts',
                'category' => $category,
                'is_locked' => true,
                'is_pro' => false,
                'document_date' => $docDate,
                'judgment_date' => $docDate,
                'hearing_date' => $hearingDate,
                'court' => $court,
                'case_number' => $caseNumber,
                'title' => $title,
                'source_url' => $sourceUrl,
                'requires_human_review' => (bool) ($row->requires_human_review ?? false),
                'review_reason' => $row->review_reason ?? null,
                'applicant' => $applicant,
                'respondent' => $respondent,
                'author' => $author ? 'Author (Locked - Pro Required)' : null,
                'citation' => $citation ? 'Citation (Locked - Pro Required)' : null,
                'subjects' => $subjects,
                'outcome' => $outcome ? 'Judicial Order (Locked - Pro Required)' : null,
                'summary' => $summary,
                'full_text' => $fullText ? (strlen($fullText) > 600 ? substr($fullText, 0, 600) : $fullText) : null,
                'center_content' => null,
                'roll_entries' => count($rollEntries) > 3 ? array_slice($rollEntries, 0, 3) : $rollEntries,
                'ratio_decidendi' => $ratioDecidendi ? 'The binding legal principles (Ratio Decidendi) and judicial reasoning for this matter are available exclusively with a Pro Case Law or Pro Analytics subscription. Upgrade your account to inspect full headnotes, cited authorities, and procedural history.' : null,
                'obiter_dicta' => $obiterDicta ? 'Judicial observations and obiter dicta are reserved for Pro Subscribers.' : null,
                'order' => $order ? 'Formal court order details and relief granted are locked. Upgrade to Pro to inspect unredacted orders.' : null,
                'judges' => count($judges) > 0 ? ['Presiding Bench (Locked - Pro Required)'] : [],
                'precedents_count' => count($precedentsCited),
                'precedents_cited' => [],
                'reportable' => (bool) $reportable,
                'duration_days' => $durationDays,
                'court_location' => $courtLocation,
                'penalty_amount' => $penaltyAmount,
                'sanctions' => count($sanctions) > 2 ? array_slice($sanctions, 0, 2) : $sanctions,
                'contraventions' => [],
                'key_provisions' => $keyProvisions,
                'regulator' => $regulator,
                'action_type' => $actionType,
                'debarment' => $debarment,
            ];
        }

        return [
            'id' => (string) $row->id,
            'extracted_record_id' => isset($row->extracted_record_id) ? (string) $row->extracted_record_id : null,
            'source_table' => 'scrubbed',
            'record_type' => $row->record_type ?? 'saflii_courts',
            'category' => $category,
            'is_locked' => false,
            'is_pro' => true,
            'document_date' => $docDate,
            'judgment_date' => $docDate,
            'hearing_date' => $hearingDate,
            'court' => $court,
            'case_number' => $caseNumber,
            'title' => $title,
            'source_url' => $sourceUrl,
            'requires_human_review' => (bool) ($row->requires_human_review ?? false),
            'review_reason' => $row->review_reason ?? null,
            'applicant' => $applicant,
            'respondent' => $respondent,
            'author' => $author,
            'citation' => $citation,
            'subjects' => $subjects,
            'outcome' => $outcome,
            'summary' => $summary,
            'full_text' => $fullText,
            'center_content' => $centerContent,
            'roll_entries' => $rollEntries,
            'ratio_decidendi' => $ratioDecidendi,
            'obiter_dicta' => $obiterDicta,
            'order' => $order,
            'judges' => $judges,
            'precedents_count' => count($precedentsCited),
            'precedents_cited' => $isDetail ? $precedentsCited : [],
            'reportable' => (bool) $reportable,
            'duration_days' => $durationDays,
            'court_location' => $courtLocation,
            'penalty_amount' => $penaltyAmount,
            'sanctions' => $sanctions,
            'contraventions' => $contraventions,
            'key_provisions' => $keyProvisions,
            'regulator' => $regulator,
            'action_type' => $actionType,
            'debarment' => $debarment,
        ];
    }
}

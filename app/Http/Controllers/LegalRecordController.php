<?php

namespace App\Http\Controllers;

use App\Models\TargetVanity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class LegalRecordController extends Controller
{
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

        // Search query: search directly across scrubbed_records JSON data using GIN trigram index
        if ($search !== '') {
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
        }

        $countCacheKey = 'legal_records:count:'.md5(serialize([
            'category' => $category,
            'record_type' => $recordType,
            'search' => $search,
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

        $rows = $query->offset($offset)->limit($limit)->get();
        $records = $rows->map(fn ($row) => $this->formatScrubbedRecord($row, $isPro, false));

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

        if ($scrubbed) {
            $formatted = $this->formatScrubbedRecord($scrubbed, $isPro, true);

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

        $category = $ext['category'] ?? $srData['category'] ?? $erData['category'] ?? null;
        if (! $category) {
            if ($row->record_type === 'sabinet_ccma') {
                $category = 'cases';
            } elseif (str_contains($row->record_type ?? '', 'gaz')) {
                $category = 'gaz';
            } elseif (str_contains($row->record_type ?? '', 'journal')) {
                $category = 'journals';
            } elseif (str_contains($row->record_type ?? '', 'roll')) {
                $category = 'court_rolls';
            } else {
                $category = 'cases';
            }
        }

        // Only parse full text and heavy content when single-record detail is requested
        $fullText = null;
        $centerContent = null;
        $rollEntries = [];
        if ($isDetail) {
            $fullText = $srData['full_text'] ?? $srData['text'] ?? $srData['content'] ?? $srData['body'] ?? $erData['full_text'] ?? $erData['text'] ?? $erData['content'] ?? $ext['full_text'] ?? $ext['content'] ?? null;
            $centerContent = $srData['center_content'] ?? $erData['center_content'] ?? null;
            $rollEntries = $ext['roll_entries'] ?? $ext['schedule'] ?? $srData['roll_entries'] ?? $srData['schedule'] ?? $srData['entries'] ?? $erData['roll_entries'] ?? $erData['entries'] ?? [];
            if (! is_array($rollEntries)) {
                $rollEntries = [];
            }
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
                'applicant' => $applicant ? 'Applicant (Locked - Pro Required)' : null,
                'respondent' => $respondent ? 'Respondent (Locked - Pro Required)' : null,
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
        ];
    }
}

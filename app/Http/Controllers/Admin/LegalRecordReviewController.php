<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExtractedRecord;
use App\Models\ParsedRecord;
use App\Models\ScrubbedRecord;
use App\Models\TargetVanity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class LegalRecordReviewController extends Controller
{
    /**
     * Display the Legal Records Human Review queue page.
     */
    public function index(Request $request): InertiaResponse
    {
        $filters = TargetVanity::orderBy('vanity_name')
            ->get()
            ->map(fn ($vanity) => [
                'target_name' => $vanity->target_name,
                'vanity_name' => $vanity->vanity_name,
                'target_type' => $vanity->target_type,
            ]);

        return Inertia::render('Admin/LegalRecords/HumanReview', [
            'filters' => $filters,
        ]);
    }

    /**
     * Return paginated records that require human review from pgsql_coeus.
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

        $isPgsql = DB::connection('pgsql_coeus')->getDriverName() === 'pgsql';

        $selectData = $isPgsql
            ? DB::raw("(scrubbed_records.data - 'formatted_text' - 'full_text' - 'content' - 'body' - 'text') as data")
            : 'scrubbed_records.data';

        $query = DB::connection('pgsql_coeus')->table('extracted_records')
            ->leftJoin('scrubbed_records', 'scrubbed_records.extracted_record_id', '=', 'extracted_records.id')
            ->leftJoin('parsed_records', 'parsed_records.extracted_record_id', '=', 'extracted_records.id')
            ->where('extracted_records.requires_human_review', true)
            ->select([
                'extracted_records.id as extracted_record_id',
                'scrubbed_records.id as scrubbed_record_id',
                'parsed_records.id as parsed_record_id',
                'extracted_records.record_type',
                'extracted_records.source_url',
                'extracted_records.document_date',
                'extracted_records.status as extracted_status',
                'extracted_records.requires_human_review',
                'extracted_records.review_reason',
                'extracted_records.scraped_at as extracted_created_at',
                'extracted_records.updated_at as extracted_updated_at',
                'scrubbed_records.created_at as scrubbed_created_at',
                $selectData,
            ]);

        // Category filter
        if ($category === 'cases') {
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
        } elseif ($category === 'journals') {
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
        } elseif ($category === 'court_rolls') {
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

        // Search query
        if ($search !== '') {
            $query->where(function ($q) use ($search, $isPgsql) {
                if ($isPgsql) {
                    $q->whereRaw('scrubbed_records.data::text ILIKE ?', ["%{$search}%"])
                        ->orWhereRaw('extracted_records.data::text ILIKE ?', ["%{$search}%"])
                        ->orWhereRaw('extracted_records.review_reason ILIKE ?', ["%{$search}%"])
                        ->orWhereRaw('extracted_records.source_url ILIKE ?', ["%{$search}%"]);
                } else {
                    $q->whereRaw('scrubbed_records.data LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('extracted_records.data LIKE ?', ["%{$search}%"])
                        ->orWhere('extracted_records.review_reason', 'LIKE', "%{$search}%")
                        ->orWhere('extracted_records.source_url', 'LIKE', "%{$search}%");
                }
            });
        }

        $total = (clone $query)->count();

        $docDateSql = $isPgsql
            ? "COALESCE(
                NULLIF(scrubbed_records.data->'extracted_data'->>'judgment_date', ''),
                NULLIF(scrubbed_records.data->'extracted_data'->>'award_date', ''),
                NULLIF(scrubbed_records.data->'extracted_data'->>'hearing_date', ''),
                NULLIF(scrubbed_records.data->'metadata'->>'document_date', ''),
                NULLIF(scrubbed_records.data->'metadata'->>'hearing_date', ''),
                extracted_records.document_date::text
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
                ->orderBy('extracted_records.updated_at', 'desc');
        } elseif ($sortField === 'created_at' || $sortField === 'updated_at') {
            $query->orderBy('extracted_records.updated_at', $sortOrder);
        } elseif ($sortField === 'case_number') {
            $caseNumSql = $isPgsql
                ? "COALESCE(scrubbed_records.data->'metadata'->>'case_number', scrubbed_records.data->'extracted_data'->>'case_number', scrubbed_records.data->>'case_number', '')"
                : "COALESCE(json_extract(scrubbed_records.data, '$.metadata.case_number'), json_extract(scrubbed_records.data, '$.extracted_data.case_number'), json_extract(scrubbed_records.data, '$.case_number'), '')";
            $query->orderByRaw("{$caseNumSql} {$sortOrder}")
                ->orderByRaw("{$docDateSql} desc NULLS LAST");
        } else {
            $query->orderByRaw("{$docDateSql} {$sortOrder} NULLS LAST")
                ->orderBy('extracted_records.updated_at', 'desc');
        }

        $rows = $query->offset($offset)->limit($limit)->get();

        $records = $rows->map(function ($row) {
            $srData = is_array($row->data) ? $row->data : (json_decode($row->data ?? '{}', true) ?: []);
            $ext = is_array($srData['extracted_data'] ?? null) ? $srData['extracted_data'] : [];
            $meta = is_array($srData['metadata'] ?? null) ? $srData['metadata'] : [];

            $title = $srData['title'] ?? $ext['title'] ?? 'Legal Matter';
            $court = $ext['court'] ?? $meta['court'] ?? $meta['target_name'] ?? $row->record_type ?? null;
            $caseNumber = $meta['case_number'] ?? $ext['case_number'] ?? $srData['case_number'] ?? $srData['award_number'] ?? null;
            $docDate = $ext['judgment_date'] ?? $ext['award_date'] ?? $ext['hearing_date'] ?? $meta['document_date'] ?? $meta['hearing_date'] ?? ($row->document_date ? substr((string) $row->document_date, 0, 10) : null);

            return [
                'id' => (string) ($row->scrubbed_record_id ?: $row->extracted_record_id),
                'extracted_record_id' => (string) $row->extracted_record_id,
                'scrubbed_record_id' => $row->scrubbed_record_id ? (string) $row->scrubbed_record_id : null,
                'parsed_record_id' => $row->parsed_record_id ? (string) $row->parsed_record_id : null,
                'record_type' => $row->record_type,
                'title' => $title,
                'court' => $court,
                'case_number' => $caseNumber,
                'document_date' => $docDate,
                'source_url' => $row->source_url,
                'requires_human_review' => (bool) $row->requires_human_review,
                'review_reason' => $row->review_reason,
                'status' => $row->extracted_status,
                'summary' => $srData['ai_summary'] ?? $ext['summary'] ?? $srData['summary'] ?? null,
                'has_scrubbed' => ! empty($row->scrubbed_record_id),
                'has_parsed' => ! empty($row->parsed_record_id),
            ];
        });

        return response()->json([
            'total' => $total,
            'records' => $records,
        ]);
    }

    /**
     * Retrieve all 3 states (extracted, parsed, scrubbed) for a single record.
     */
    public function show(string $id): JsonResponse
    {
        // $id can be either scrubbed_records.id or extracted_records.id
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
            abort(404, 'Record not found.');
        }

        $extractedRecordId = $extracted->id;

        $scrubbed = DB::connection('pgsql_coeus')->table('scrubbed_records')
            ->where('extracted_record_id', $extractedRecordId)
            ->first();

        $parsed = DB::connection('pgsql_coeus')->table('parsed_records')
            ->where('extracted_record_id', $extractedRecordId)
            ->first();

        $extractedData = is_array($extracted->data) ? $extracted->data : (json_decode($extracted->data ?? '{}', true) ?: []);
        $parsedData = $parsed ? (is_array($parsed->data) ? $parsed->data : (json_decode($parsed->data ?? '{}', true) ?: [])) : null;
        $scrubbedData = $scrubbed ? (is_array($scrubbed->data) ? $scrubbed->data : (json_decode($scrubbed->data ?? '{}', true) ?: [])) : null;

        $extData = is_array($scrubbedData['extracted_data'] ?? null) ? $scrubbedData['extracted_data'] : [];
        $metaData = is_array($scrubbedData['metadata'] ?? null) ? $scrubbedData['metadata'] : [];

        $title = $scrubbedData['title'] ?? $extData['title'] ?? $extractedData['title'] ?? 'Legal Matter';
        $caseNumber = $metaData['case_number'] ?? $extData['case_number'] ?? $scrubbedData['case_number'] ?? $scrubbedData['award_number'] ?? $extractedData['case_number'] ?? null;
        $court = $extData['court'] ?? $metaData['court'] ?? $metaData['target_name'] ?? $extracted->record_type ?? null;
        $docDate = $extData['judgment_date'] ?? $extData['award_date'] ?? $extData['hearing_date'] ?? $metaData['document_date'] ?? ($extracted->document_date ? substr((string) $extracted->document_date, 0, 10) : null);
        $hearingDate = $extData['hearing_date'] ?? $metaData['hearing_date'] ?? null;
        $courtLocation = $extData['court_location'] ?? $metaData['court_location'] ?? null;
        $applicant = $extData['applicant_plaintiff'] ?? $scrubbedData['applicant_plaintiff'] ?? $extData['employee'] ?? $scrubbedData['employee'] ?? null;
        if (is_array($applicant)) {
            $applicant = implode(', ', $applicant);
        }
        $respondent = $extData['respondent_defendant'] ?? $scrubbedData['respondent_defendant'] ?? $extData['employer'] ?? $scrubbedData['employer'] ?? null;
        if (is_array($respondent)) {
            $respondent = implode(', ', $respondent);
        }
        $judges = $extData['judges'] ?? $scrubbedData['judges'] ?? [];
        if (! is_array($judges)) {
            $judges = $judges ? [$judges] : [];
        }
        $judges = array_values(array_filter($judges, fn ($j) => ! empty($j) && ! str_starts_with((string) $j, '[Not explicitly')));

        return response()->json([
            'id' => (string) ($scrubbed ? $scrubbed->id : $extracted->id),
            'extracted_record_id' => (string) $extracted->id,
            'scrubbed_record_id' => $scrubbed ? (string) $scrubbed->id : null,
            'parsed_record_id' => $parsed ? (string) $parsed->id : null,
            'record_type' => $extracted->record_type,
            'source_url' => $extracted->source_url,
            'document_date' => $docDate,
            'requires_human_review' => (bool) $extracted->requires_human_review,
            'review_reason' => $extracted->review_reason,
            'status' => $extracted->status,

            // Structured fields for form editing
            'form' => [
                'title' => $title,
                'case_number' => $caseNumber,
                'court' => $court,
                'court_location' => $courtLocation,
                'document_date' => $docDate,
                'hearing_date' => $hearingDate,
                'applicant' => $applicant,
                'respondent' => $respondent,
                'judges' => $judges,
                'reportable' => isset($extData['reportable']) ? (bool) $extData['reportable'] : true,
                'duration_days' => $extData['duration_days'] ?? null,
                'summary' => $scrubbedData['ai_summary'] ?? $extData['summary'] ?? $scrubbedData['summary'] ?? null,
                'ratio_decidendi' => $extData['ratio_decidendi'] ?? $scrubbedData['ratio_decidendi'] ?? null,
                'obiter_dicta' => $extData['obiter_dicta'] ?? $scrubbedData['obiter_dicta'] ?? null,
                'order' => $extData['order'] ?? $scrubbedData['order'] ?? null,
                'subjects' => $extData['subjects'] ?? $scrubbedData['subjects'] ?? null,
                'source_url' => $extracted->source_url,
                'requires_human_review' => (bool) $extracted->requires_human_review,
                'review_reason' => $extracted->review_reason,
            ],

            // Complete raw payloads for all 3 states
            'states' => [
                'extracted' => [
                    'id' => (string) $extracted->id,
                    'record_type' => $extracted->record_type,
                    'source_url' => $extracted->source_url,
                    'document_date' => $extracted->document_date,
                    'status' => $extracted->status,
                    'requires_human_review' => (bool) $extracted->requires_human_review,
                    'review_reason' => $extracted->review_reason,
                    'scraped_at' => $extracted->scraped_at ?? null,
                    'detailed_at' => $extracted->detailed_at ?? null,
                    'parsed_at' => $extracted->parsed_at ?? null,
                    'scrubbed_at' => $extracted->scrubbed_at ?? null,
                    'data' => $extractedData,
                ],
                'parsed' => $parsed ? [
                    'id' => (string) $parsed->id,
                    'extracted_record_id' => (string) $parsed->extracted_record_id,
                    'created_at' => $parsed->created_at ?? null,
                    'updated_at' => $parsed->updated_at ?? null,
                    'data' => $parsedData,
                ] : null,
                'scrubbed' => $scrubbed ? [
                    'id' => (string) $scrubbed->id,
                    'extracted_record_id' => (string) $scrubbed->extracted_record_id,
                    'created_at' => $scrubbed->created_at ?? null,
                    'updated_at' => $scrubbed->updated_at ?? null,
                    'data' => $scrubbedData,
                ] : null,
            ],
        ]);
    }

    /**
     * Toggle or set the requires_human_review status of a single record.
     */
    public function toggleHumanReview(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'requires_human_review' => 'nullable|boolean',
            'review_reason' => 'nullable|string|max:1000',
        ]);

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

        $newStatus = array_key_exists('requires_human_review', $validated)
            ? (bool) $validated['requires_human_review']
            : ! (bool) $extracted->requires_human_review;

        $updateData = [
            'requires_human_review' => $newStatus,
            'updated_at' => now(),
        ];

        if (array_key_exists('review_reason', $validated)) {
            $updateData['review_reason'] = $validated['review_reason'];
        }

        DB::connection('pgsql_coeus')->table('extracted_records')
            ->where('id', $extracted->id)
            ->update($updateData);

        return response()->json([
            'success' => true,
            'extracted_record_id' => (string) $extracted->id,
            'requires_human_review' => $newStatus,
            'review_reason' => $updateData['review_reason'] ?? $extracted->review_reason,
            'message' => $newStatus
                ? 'Record successfully marked for human review.'
                : 'Record marked as reviewed/resolved.',
        ]);
    }

    /**
     * Batch mark multiple records for human review.
     */
    public function batchMarkHumanReview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|string',
            'requires_human_review' => 'nullable|boolean',
            'review_reason' => 'nullable|string|max:1000',
        ]);

        $ids = $validated['ids'];
        $newStatus = array_key_exists('requires_human_review', $validated)
            ? (bool) $validated['requires_human_review']
            : true;
        $reason = $validated['review_reason'] ?? 'Flagged in batch review by admin.';

        // IDs may be scrubbed IDs or extracted IDs; map any scrubbed IDs to extracted IDs
        $scrubbedExtractedIds = DB::connection('pgsql_coeus')->table('scrubbed_records')
            ->whereIn('id', $ids)
            ->pluck('extracted_record_id')
            ->filter()
            ->all();

        $allTargetExtractedIds = array_unique(array_merge($ids, $scrubbedExtractedIds));

        $updateData = [
            'requires_human_review' => $newStatus,
            'updated_at' => now(),
        ];
        if ($reason) {
            $updateData['review_reason'] = $reason;
        }

        $affected = DB::connection('pgsql_coeus')->table('extracted_records')
            ->whereIn('id', $allTargetExtractedIds)
            ->update($updateData);

        return response()->json([
            'success' => true,
            'affected_count' => $affected,
            'requires_human_review' => $newStatus,
            'message' => "Successfully updated {$affected} record(s).",
        ]);
    }

    /**
     * Update fields across scrubbed, parsed, and extracted record states.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            // Form fields
            'title' => 'nullable|string|max:1000',
            'case_number' => 'nullable|string|max:255',
            'court' => 'nullable|string|max:255',
            'court_location' => 'nullable|string|max:255',
            'document_date' => 'nullable|date',
            'hearing_date' => 'nullable|date',
            'applicant' => 'nullable|string|max:1000',
            'respondent' => 'nullable|string|max:1000',
            'judges' => 'nullable|array',
            'judges.*' => 'nullable|string|max:255',
            'reportable' => 'nullable|boolean',
            'duration_days' => 'nullable|integer',
            'summary' => 'nullable|string',
            'ratio_decidendi' => 'nullable|string',
            'obiter_dicta' => 'nullable|string',
            'order' => 'nullable|string',
            'subjects' => 'nullable|string',
            'source_url' => 'nullable|url|max:2048',
            'requires_human_review' => 'nullable|boolean',
            'review_reason' => 'nullable|string|max:1000',

            // Optional raw state overrides
            'raw_scrubbed_data' => 'nullable|array',
            'raw_parsed_data' => 'nullable|array',
            'raw_extracted_data' => 'nullable|array',
        ]);

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

        $extractedId = $extracted->id;

        // 1. Update ExtractedRecord
        $extractedUpdates = [
            'updated_at' => now(),
        ];
        if (array_key_exists('source_url', $validated) && $validated['source_url'] !== null) {
            $extractedUpdates['source_url'] = $validated['source_url'];
        }
        if (array_key_exists('document_date', $validated) && $validated['document_date'] !== null) {
            $extractedUpdates['document_date'] = $validated['document_date'];
        }
        if (array_key_exists('requires_human_review', $validated)) {
            $extractedUpdates['requires_human_review'] = (bool) $validated['requires_human_review'];
        }
        if (array_key_exists('review_reason', $validated)) {
            $extractedUpdates['review_reason'] = $validated['review_reason'];
        }
        if (isset($validated['raw_extracted_data'])) {
            $extractedUpdates['data'] = json_encode($validated['raw_extracted_data']);
        }

        DB::connection('pgsql_coeus')->table('extracted_records')
            ->where('id', $extractedId)
            ->update($extractedUpdates);

        // 2. Update ParsedRecord if exists or if raw_parsed_data provided
        $parsed = DB::connection('pgsql_coeus')->table('parsed_records')
            ->where('extracted_record_id', $extractedId)
            ->first();

        if ($parsed && isset($validated['raw_parsed_data'])) {
            DB::connection('pgsql_coeus')->table('parsed_records')
                ->where('id', $parsed->id)
                ->update([
                    'data' => json_encode($validated['raw_parsed_data']),
                    'updated_at' => now(),
                ]);
        }

        // 3. Update ScrubbedRecord
        $scrubbed = DB::connection('pgsql_coeus')->table('scrubbed_records')
            ->where('extracted_record_id', $extractedId)
            ->first();

        if ($scrubbed) {
            $currentSrData = is_array($scrubbed->data) ? $scrubbed->data : (json_decode($scrubbed->data ?? '{}', true) ?: []);

            if (isset($validated['raw_scrubbed_data'])) {
                // If raw JSON provided, preserve precedents_cited if not supplied in raw
                $newSrData = $validated['raw_scrubbed_data'];
                if (! isset($newSrData['extracted_data']['precedents_cited']) && isset($currentSrData['extracted_data']['precedents_cited'])) {
                    $newSrData['extracted_data']['precedents_cited'] = $currentSrData['extracted_data']['precedents_cited'];
                }
            } else {
                // Merge structured form values into scrubbed_records.data
                $newSrData = $currentSrData;
                $extData = is_array($newSrData['extracted_data'] ?? null) ? $newSrData['extracted_data'] : [];
                $metaData = is_array($newSrData['metadata'] ?? null) ? $newSrData['metadata'] : [];

                if (array_key_exists('title', $validated)) {
                    $newSrData['title'] = $validated['title'];
                    $extData['title'] = $validated['title'];
                }
                if (array_key_exists('case_number', $validated)) {
                    $metaData['case_number'] = $validated['case_number'];
                    $extData['case_number'] = $validated['case_number'];
                    $newSrData['case_number'] = $validated['case_number'];
                }
                if (array_key_exists('court', $validated)) {
                    $extData['court'] = $validated['court'];
                    $metaData['court'] = $validated['court'];
                }
                if (array_key_exists('court_location', $validated)) {
                    $extData['court_location'] = $validated['court_location'];
                    $metaData['court_location'] = $validated['court_location'];
                }
                if (array_key_exists('document_date', $validated)) {
                    $extData['judgment_date'] = $validated['document_date'];
                    $metaData['document_date'] = $validated['document_date'];
                }
                if (array_key_exists('hearing_date', $validated)) {
                    $extData['hearing_date'] = $validated['hearing_date'];
                    $metaData['hearing_date'] = $validated['hearing_date'];
                }
                if (array_key_exists('applicant', $validated)) {
                    $extData['applicant_plaintiff'] = $validated['applicant'];
                    $newSrData['applicant_plaintiff'] = $validated['applicant'];
                }
                if (array_key_exists('respondent', $validated)) {
                    $extData['respondent_defendant'] = $validated['respondent'];
                    $newSrData['respondent_defendant'] = $validated['respondent'];
                }
                if (array_key_exists('judges', $validated)) {
                    $extData['judges'] = $validated['judges'];
                    $newSrData['judges'] = $validated['judges'];
                }
                if (array_key_exists('reportable', $validated)) {
                    $extData['reportable'] = $validated['reportable'];
                }
                if (array_key_exists('duration_days', $validated)) {
                    $extData['duration_days'] = $validated['duration_days'];
                }
                if (array_key_exists('summary', $validated)) {
                    $newSrData['ai_summary'] = $validated['summary'];
                    $extData['summary'] = $validated['summary'];
                    $newSrData['summary'] = $validated['summary'];
                }
                if (array_key_exists('ratio_decidendi', $validated)) {
                    $extData['ratio_decidendi'] = $validated['ratio_decidendi'];
                    $newSrData['ratio_decidendi'] = $validated['ratio_decidendi'];
                }
                if (array_key_exists('obiter_dicta', $validated)) {
                    $extData['obiter_dicta'] = $validated['obiter_dicta'];
                    $newSrData['obiter_dicta'] = $validated['obiter_dicta'];
                }
                if (array_key_exists('order', $validated)) {
                    $extData['order'] = $validated['order'];
                    $newSrData['order'] = $validated['order'];
                }
                if (array_key_exists('subjects', $validated)) {
                    $extData['subjects'] = $validated['subjects'];
                    $newSrData['subjects'] = $validated['subjects'];
                }
                if (array_key_exists('source_url', $validated)) {
                    $extData['source_url'] = $validated['source_url'];
                    $metaData['source_url'] = $validated['source_url'];
                }

                $newSrData['extracted_data'] = $extData;
                $newSrData['metadata'] = $metaData;
            }

            DB::connection('pgsql_coeus')->table('scrubbed_records')
                ->where('id', $scrubbed->id)
                ->update([
                    'data' => json_encode($newSrData),
                    'updated_at' => now(),
                ]);
        }

        Cache::forget('dataset_summary');

        return response()->json([
            'success' => true,
            'message' => 'Record successfully updated.',
            'extracted_record_id' => (string) $extractedId,
            'scrubbed_record_id' => $scrubbed ? (string) $scrubbed->id : null,
        ]);
    }
}

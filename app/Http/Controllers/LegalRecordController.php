<?php

namespace App\Http\Controllers;

use App\Models\ScrubbedRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class LegalRecordController extends Controller
{
    /**
     * Display the legal record index view.
     */
    public function index(Request $request): InertiaResponse
    {
        return Inertia::render('LegalRecords/Index');
    }

    /**
     * Return paginated JSON records for PrimeVue lazy DataTable.
     */
    public function data(Request $request): JsonResponse
    {
        $offset = max(0, (int) $request->input('offset', 0));
        $limit = min(100, max(1, (int) $request->input('limit', 25)));
        $search = trim((string) $request->input('search', ''));
        $recordType = trim((string) $request->input('record_type', ''));
        $court = trim((string) $request->input('court', ''));
        $sortField = trim((string) $request->input('sort_field', 'created_at'));
        $sortOrder = (int) $request->input('sort_order', -1) === 1 ? 'asc' : 'desc';

        $query = ScrubbedRecord::query()
            ->join('extracted_records', 'scrubbed_records.extracted_record_id', '=', 'extracted_records.id')
            ->select([
                'scrubbed_records.id',
                'scrubbed_records.extracted_record_id',
                'scrubbed_records.created_at',
                'scrubbed_records.data',
                'extracted_records.record_type',
                'extracted_records.document_date',
                'extracted_records.source_url',
            ]);

        if ($recordType !== '') {
            $query->where('extracted_records.record_type', $recordType);
        }

        if ($court !== '') {
            $query->whereRaw("scrubbed_records.data->>'court' = ?", [$court]);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->whereRaw("scrubbed_records.data->>'case_number' ILIKE ?", ["%{$search}%"])
                  ->orWhereRaw("scrubbed_records.data->>'title' ILIKE ?", ["%{$search}%"])
                  ->orWhereRaw("scrubbed_records.data->>'applicant_plaintiff' ILIKE ?", ["%{$search}%"])
                  ->orWhereRaw("scrubbed_records.data->>'respondent_defendant' ILIKE ?", ["%{$search}%"])
                  ->orWhereRaw("scrubbed_records.data->>'court' ILIKE ?", ["%{$search}%"]);
            });
        }

        $total = (clone $query)->count();

        // Map sorting fields
        if ($sortField === 'document_date') {
            $query->orderBy('extracted_records.document_date', $sortOrder);
        } elseif ($sortField === 'case_number') {
            $query->orderByRaw("scrubbed_records.data->>'case_number' " . $sortOrder);
        } elseif ($sortField === 'court') {
            $query->orderByRaw("scrubbed_records.data->>'court' " . $sortOrder);
        } else {
            $query->orderBy('scrubbed_records.created_at', $sortOrder);
        }

        $rows = $query->offset($offset)->limit($limit)->get();

        $records = $rows->map(function ($row) {
            $payload = is_array($row->data) ? $row->data : json_decode($row->data ?? '{}', true);

            $applicant = $payload['applicant_plaintiff'] ?? null;
            if (is_array($applicant)) {
                $applicant = implode(', ', $applicant);
            }

            $respondent = $payload['respondent_defendant'] ?? null;
            if (is_array($respondent)) {
                $respondent = implode(', ', $respondent);
            }

            $title = $payload['title'] ?? $payload['name'] ?? null;
            if (!$title && $applicant) {
                $title = $applicant . ($respondent ? ' v ' . $respondent : '');
            }

            $docDate = $row->document_date ? (is_string($row->document_date) ? substr($row->document_date, 0, 10) : $row->document_date->format('Y-m-d')) : ($payload['judgment_date'] ?? $payload['date'] ?? null);

            return [
                'id' => $row->id,
                'record_type' => $row->record_type,
                'document_date' => $docDate,
                'court' => $payload['court'] ?? $row->record_type,
                'case_number' => $payload['case_number'] ?? null,
                'title' => $title ?: ('Record #' . substr($row->id, 0, 8)),
                'source_url' => $row->source_url,
                'result' => $payload['result'] ?? $payload['order'] ?? $payload['holding'] ?? null,
                'reason_for_dismissal' => $payload['reason_for_dismissal'] ?? null,
                'summary' => $payload['ai_summary'] ?? $payload['summary'] ?? null,
            ];
        });

        return response()->json([
            'total' => $total,
            'records' => $records,
        ]);
    }

    /**
     * Return single record full detail payload.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $record = ScrubbedRecord::with('extractedRecord')->findOrFail($id);
        $payload = is_array($record->data) ? $record->data : json_decode($record->data ?? '{}', true);

        return response()->json([
            'id' => $record->id,
            'record_type' => $record->extractedRecord ? $record->extractedRecord->record_type : null,
            'document_date' => $record->extractedRecord ? $record->extractedRecord->document_date : null,
            'source_url' => $record->extractedRecord ? $record->extractedRecord->source_url : null,
            'data' => $payload,
        ]);
    }
}

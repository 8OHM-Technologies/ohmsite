<?php

namespace App\Http\Controllers;

use App\Models\CcmaAnalytics;
use App\Models\LegalAnalytics;
use App\Models\TargetVanity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
     * Return paginated JSON records for PrimeVue lazy DataTable.
     */
    public function data(Request $request): JsonResponse
    {
        $offset = max(0, (int) $request->input('offset', 0));
        $limit = min(100, max(1, (int) $request->input('limit', 25)));
        $search = trim((string) $request->input('search', ''));
        $category = trim((string) $request->input('category', 'all'));
        $recordType = trim((string) $request->input('record_type', ''));
        $sortField = trim((string) $request->input('sort_field', 'created_at'));
        $sortOrder = (int) $request->input('sort_order', -1) === 1 ? 'asc' : 'desc';

        $isPostgres = DB::connection()->getDriverName() === 'pgsql';
        $like = $isPostgres ? 'ILIKE' : 'LIKE';

        // Check target vanity to know if a specific source target is selected
        $targetVanity = null;
        if ($recordType !== '' && $recordType !== 'all') {
            $targetVanity = TargetVanity::where('target_name', $recordType)->first();
        }

        if ($targetVanity) {
            if ($targetVanity->target_name === 'sabinet_ccma') {
                // CCMA path
                $query = CcmaAnalytics::query();
                if ($search !== '') {
                    $query->where(function ($q) use ($search, $like) {
                        $q->where('title', $like, "%{$search}%")
                          ->orWhere('award_number', $like, "%{$search}%")
                          ->orWhere('court', $like, "%{$search}%")
                          ->orWhere('employee', $like, "%{$search}%")
                          ->orWhere('employer', $like, "%{$search}%")
                          ->orWhere('reason_for_dismissal', $like, "%{$search}%");
                    });
                }

                $total = $query->count();

                if ($sortField === 'document_date') {
                    $query->orderBy('award_date', $sortOrder);
                } elseif ($sortField === 'case_number') {
                    $query->orderBy('award_number', $sortOrder);
                } elseif ($sortField === 'court') {
                    $query->orderBy('court', $sortOrder);
                } else {
                    $query->orderBy('created_at', $sortOrder);
                }

                $rows = $query->offset($offset)->limit($limit)->get();
                $records = $rows->map(function ($row) {
                    return [
                        'id' => $row->id,
                        'source_table' => 'ccma',
                        'record_type' => $row->document_type,
                        'document_date' => $row->award_date ? $row->award_date->toDateString() : null,
                        'court' => $row->court,
                        'case_number' => $row->award_number,
                        'title' => $row->title,
                        'source_url' => $row->detail_url,
                        'applicant' => $row->employee,
                        'respondent' => $row->employer,
                        'subjects' => $row->reason_for_dismissal,
                        'outcome' => $row->forum,
                        'summary' => $row->reason_for_dismissal,
                    ];
                });
            } else {
                // Specific Legal Analytics target
                $query = LegalAnalytics::query()->where('target_name', $recordType);
                if ($search !== '') {
                    $query->where(function ($q) use ($search, $like, $isPostgres) {
                        $q->where('title', $like, "%{$search}%")
                          ->orWhere('case_number', $like, "%{$search}%")
                          ->orWhere('court', $like, "%{$search}%");
                        if ($isPostgres) {
                            $q->orWhereRaw("data::text ILIKE ?", ["%{$search}%"]);
                        } else {
                            $q->orWhere('data', 'LIKE', "%{$search}%");
                        }
                    });
                }

                $total = $query->count();

                if ($sortField === 'document_date') {
                    $query->orderBy('document_date', $sortOrder);
                } elseif ($sortField === 'case_number') {
                    $query->orderBy('case_number', $sortOrder);
                } elseif ($sortField === 'court') {
                    $query->orderBy('court', $sortOrder);
                } else {
                    $query->orderBy('created_at', $sortOrder);
                }

                $rows = $query->offset($offset)->limit($limit)->get();
                $records = $rows->map(function ($row) {
                    $payload = is_array($row->data) ? $row->data : (is_string($row->data) ? json_decode($row->data, true) : []);
                    $applicant = $payload['applicant_plaintiff'] ?? $payload['employee'] ?? null;
                    if (is_array($applicant)) {
                        $applicant = implode(', ', $applicant);
                    }
                    $respondent = $payload['respondent_defendant'] ?? $payload['employer'] ?? null;
                    if (is_array($respondent)) {
                        $respondent = implode(', ', $respondent);
                    }
                    $subjects = $payload['reason_for_dismissal'] ?? $payload['subjects'] ?? $payload['subject'] ?? null;
                    $outcome = $payload['result'] ?? $payload['order'] ?? $payload['holding'] ?? null;

                    return [
                        'id' => $row->id,
                        'source_table' => 'legal',
                        'record_type' => $row->document_type,
                        'document_date' => $row->document_date ? $row->document_date->toDateString() : null,
                        'court' => $row->court,
                        'case_number' => $row->case_number,
                        'title' => $row->title,
                        'source_url' => $row->source_url,
                        'applicant' => $applicant,
                        'respondent' => $respondent,
                        'subjects' => $subjects,
                        'outcome' => $outcome,
                        'summary' => $payload['ai_summary'] ?? $payload['summary'] ?? null,
                    ];
                });
            }
        } elseif ($category === 'journals') {
            // Journals & Gazettes only (pure legal_analytics)
            $query = LegalAnalytics::query()->whereIn('target_type', ['journals', 'gaz']);
            if ($search !== '') {
                $query->where(function ($q) use ($search, $like, $isPostgres) {
                    $q->where('title', $like, "%{$search}%")
                      ->orWhere('case_number', $like, "%{$search}%")
                      ->orWhere('court', $like, "%{$search}%");
                    if ($isPostgres) {
                        $q->orWhereRaw("data::text ILIKE ?", ["%{$search}%"]);
                    } else {
                        $q->orWhere('data', 'LIKE', "%{$search}%");
                    }
                });
            }

            $total = $query->count();

            if ($sortField === 'document_date') {
                $query->orderBy('document_date', $sortOrder);
            } elseif ($sortField === 'case_number') {
                $query->orderBy('case_number', $sortOrder);
            } elseif ($sortField === 'court') {
                $query->orderBy('court', $sortOrder);
            } else {
                $query->orderBy('created_at', $sortOrder);
            }

            $rows = $query->offset($offset)->limit($limit)->get();
            $records = $rows->map(function ($row) {
                $payload = is_array($row->data) ? $row->data : (is_string($row->data) ? json_decode($row->data, true) : []);
                return [
                    'id' => $row->id,
                    'source_table' => 'legal',
                    'record_type' => $row->document_type,
                    'document_date' => $row->document_date ? $row->document_date->toDateString() : null,
                    'court' => $row->court,
                    'case_number' => $row->case_number,
                    'title' => $row->title,
                    'source_url' => $row->source_url,
                    'applicant' => $payload['publisher'] ?? $payload['journal_name'] ?? null,
                    'respondent' => null,
                    'subjects' => $payload['subject'] ?? $payload['subjects'] ?? null,
                    'outcome' => null,
                    'summary' => $payload['ai_summary'] ?? $payload['summary'] ?? $payload['abstract'] ?? null,
                ];
            });
        } elseif ($category === 'court_rolls') {
            // Court Rolls only (pure legal_analytics)
            $query = LegalAnalytics::query()->where('target_type', 'other');
            if ($search !== '') {
                $query->where(function ($q) use ($search, $like, $isPostgres) {
                    $q->where('title', $like, "%{$search}%")
                      ->orWhere('case_number', $like, "%{$search}%")
                      ->orWhere('court', $like, "%{$search}%");
                    if ($isPostgres) {
                        $q->orWhereRaw("data::text ILIKE ?", ["%{$search}%"]);
                    } else {
                        $q->orWhere('data', 'LIKE', "%{$search}%");
                    }
                });
            }

            $total = $query->count();

            if ($sortField === 'document_date') {
                $query->orderBy('document_date', $sortOrder);
            } elseif ($sortField === 'case_number') {
                $query->orderBy('case_number', $sortOrder);
            } elseif ($sortField === 'court') {
                $query->orderBy('court', $sortOrder);
            } else {
                $query->orderBy('created_at', $sortOrder);
            }

            $rows = $query->offset($offset)->limit($limit)->get();
            $records = $rows->map(function ($row) {
                $payload = is_array($row->data) ? $row->data : (is_string($row->data) ? json_decode($row->data, true) : []);
                return [
                    'id' => $row->id,
                    'source_table' => 'legal',
                    'record_type' => $row->document_type,
                    'document_date' => $row->document_date ? $row->document_date->toDateString() : null,
                    'court' => $row->court,
                    'case_number' => $row->case_number,
                    'title' => $row->title,
                    'source_url' => $row->source_url,
                    'applicant' => null,
                    'respondent' => null,
                    'subjects' => null,
                    'outcome' => null,
                    'summary' => $payload['ai_summary'] ?? $payload['summary'] ?? null,
                ];
            });
        } else {
            // Cases or All (Union path)
            $ccmaQuery = DB::table('ccma_analytics')
                ->select([
                    'id',
                    'created_at',
                    'title',
                    'document_type as record_type',
                    'award_date as document_date',
                    'court',
                    'award_number as case_number',
                    'detail_url as source_url',
                    'employee as applicant',
                    'employer as respondent',
                    'reason_for_dismissal as subjects',
                    'forum as outcome',
                    DB::raw("NULL as summary"),
                    DB::raw("NULL as data"),
                    DB::raw("'ccma' as source_table"),
                ]);

            $legalQuery = DB::table('legal_analytics')
                ->select([
                    'id',
                    'created_at',
                    'title',
                    'document_type as record_type',
                    'document_date',
                    'court',
                    'case_number',
                    'source_url',
                    DB::raw("NULL as applicant"),
                    DB::raw("NULL as respondent"),
                    DB::raw("NULL as subjects"),
                    DB::raw("NULL as outcome"),
                    DB::raw("NULL as summary"),
                    'data',
                    DB::raw("'legal' as source_table"),
                ]);

            if ($category === 'cases') {
                $legalQuery->where('target_type', 'cases');
            }

            $unionQuery = $ccmaQuery->unionAll($legalQuery);
            $query = DB::table(DB::raw("({$unionQuery->toSql()}) as combined"))
                ->mergeBindings($unionQuery);

            if ($search !== '') {
                $query->where(function ($q) use ($search, $like, $isPostgres) {
                    $q->where('title', $like, "%{$search}%")
                      ->orWhere('case_number', $like, "%{$search}%")
                      ->orWhere('court', $like, "%{$search}%")
                      ->orWhere('applicant', $like, "%{$search}%")
                      ->orWhere('respondent', $like, "%{$search}%");
                    if ($isPostgres) {
                        $q->orWhereRaw("data::text ILIKE ?", ["%{$search}%"]);
                    } else {
                        $q->orWhere('data', 'LIKE', "%{$search}%");
                    }
                });
            }

            $total = $query->count();

            if ($sortField === 'document_date') {
                $query->orderBy('document_date', $sortOrder);
            } elseif ($sortField === 'case_number') {
                $query->orderBy('case_number', $sortOrder);
            } elseif ($sortField === 'court') {
                $query->orderBy('court', $sortOrder);
            } else {
                $query->orderBy('created_at', $sortOrder);
            }

            $rows = $query->offset($offset)->limit($limit)->get();
            $records = $rows->map(function ($row) {
                if ($row->source_table === 'ccma') {
                    return [
                        'id' => $row->id,
                        'source_table' => 'ccma',
                        'record_type' => $row->record_type,
                        'document_date' => $row->document_date ? substr((string)$row->document_date, 0, 10) : null,
                        'court' => $row->court,
                        'case_number' => $row->case_number,
                        'title' => $row->title,
                        'source_url' => $row->source_url,
                        'applicant' => $row->applicant,
                        'respondent' => $row->respondent,
                        'subjects' => $row->subjects,
                        'outcome' => $row->outcome,
                        'summary' => $row->subjects,
                    ];
                } else {
                    $payload = is_string($row->data) ? json_decode($row->data, true) : (array)$row->data;

                    $applicant = $payload['applicant_plaintiff'] ?? $payload['employee'] ?? null;
                    if (is_array($applicant)) {
                        $applicant = implode(', ', $applicant);
                    }

                    $respondent = $payload['respondent_defendant'] ?? $payload['employer'] ?? null;
                    if (is_array($respondent)) {
                        $respondent = implode(', ', $respondent);
                    }

                    $subjects = $payload['reason_for_dismissal'] ?? $payload['subjects'] ?? $payload['subject'] ?? null;
                    $outcome = $payload['result'] ?? $payload['order'] ?? $payload['holding'] ?? null;

                    return [
                        'id' => $row->id,
                        'source_table' => 'legal',
                        'record_type' => $row->record_type,
                        'document_date' => $row->document_date ? substr((string)$row->document_date, 0, 10) : null,
                        'court' => $row->court,
                        'case_number' => $row->case_number,
                        'title' => $row->title,
                        'source_url' => $row->source_url,
                        'applicant' => $applicant,
                        'respondent' => $respondent,
                        'subjects' => $subjects,
                        'outcome' => $outcome,
                        'summary' => $payload['ai_summary'] ?? $payload['summary'] ?? null,
                    ];
                }
            });
        }

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
        $sourceTable = $request->input('source_table', 'legal');

        if ($sourceTable === 'ccma') {
            $record = CcmaAnalytics::findOrFail($id);
            $payload = [
                'title' => $record->title,
                'award_number' => $record->award_number,
                'court' => $record->court,
                'award_date' => $record->award_date ? $record->award_date->toDateString() : null,
                'employee' => $record->employee,
                'employer' => $record->employer,
                'court_location' => $record->court_location,
                'reason_for_dismissal' => $record->reason_for_dismissal,
                'forum' => $record->forum,
                'detail_url' => $record->detail_url,
            ];
            return response()->json([
                'id' => $record->id,
                'source_table' => 'ccma',
                'record_type' => $record->document_type,
                'document_date' => $record->award_date ? $record->award_date->toDateString() : null,
                'source_url' => $record->detail_url,
                'data' => $payload,
            ]);
        } else {
            $record = LegalAnalytics::findOrFail($id);
            $payload = is_array($record->data) ? $record->data : json_decode($record->data ?? '{}', true);
            return response()->json([
                'id' => $record->id,
                'source_table' => 'legal',
                'record_type' => $record->document_type,
                'document_date' => $record->document_date ? $record->document_date->toDateString() : null,
                'source_url' => $record->source_url,
                'data' => $payload,
            ]);
        }
    }
}

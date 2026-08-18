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

        $user = auth()->user();
        $isPro = $user && ($user->isAdmin() || $user->hasLegalProAccess());

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
                $records = $rows->map(fn ($row) => $this->formatCcmaRecord($row, $isPro));
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
                $records = $rows->map(fn ($row) => $this->formatLegalRecord($row, $isPro));
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
            $records = $rows->map(fn ($row) => $this->formatLegalRecord($row, $isPro));
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
            $records = $rows->map(fn ($row) => $this->formatLegalRecord($row, $isPro));
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
                    DB::raw("reason_for_dismissal as summary"),
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
            $records = $rows->map(function ($row) use ($isPro) {
                if ($row->source_table === 'ccma') {
                    return $this->formatCcmaRecord($row, $isPro);
                }

                return $this->formatLegalRecord($row, $isPro);
            });
        }

        return response()->json([
            'total' => $total,
            'records' => $records,
            'is_pro' => $isPro,
        ]);
    }

    /**
     * Return single record full detail payload.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $sourceTable = $request->input('source_table', 'legal');
        $user = auth()->user();
        $isPro = $user && ($user->isAdmin() || $user->hasLegalProAccess());

        if ($sourceTable === 'ccma') {
            $record = CcmaAnalytics::findOrFail($id);
            $formatted = $this->formatCcmaRecord($record, $isPro);
            return response()->json([
                'id' => $record->id,
                'source_table' => 'ccma',
                'record_type' => $record->document_type,
                'document_date' => $record->award_date ? $record->award_date->toDateString() : null,
                'source_url' => $isPro ? $record->detail_url : null,
                'is_pro' => $isPro,
                'data' => $formatted,
            ]);
        } else {
            $record = LegalAnalytics::findOrFail($id);
            $formatted = $this->formatLegalRecord($record, $isPro);
            return response()->json([
                'id' => $record->id,
                'source_table' => 'legal',
                'record_type' => $record->document_type,
                'document_date' => $record->document_date ? $record->document_date->toDateString() : null,
                'source_url' => $isPro ? $record->source_url : null,
                'is_pro' => $isPro,
                'data' => $formatted,
            ]);
        }
    }

    /**
     * Format a legal analytics record into full structured dossier item.
     */
    private function formatLegalRecord($row, bool $isPro = true): array
    {
        $payload = is_array($row->data) ? $row->data : (is_string($row->data) ? json_decode($row->data, true) : []);
        $ext = is_array($payload['extracted_data'] ?? null) ? $payload['extracted_data'] : [];

        $applicant = $ext['applicant_plaintiff'] ?? $payload['applicant_plaintiff'] ?? $ext['employee'] ?? $payload['employee'] ?? $payload['publisher'] ?? $payload['journal_name'] ?? null;
        if (is_array($applicant)) {
            $applicant = implode(', ', $applicant);
        }

        $respondent = $ext['respondent_defendant'] ?? $payload['respondent_defendant'] ?? $ext['employer'] ?? $payload['employer'] ?? null;
        if (is_array($respondent)) {
            $respondent = implode(', ', $respondent);
        }

        $judges = $ext['judges'] ?? $payload['judges'] ?? [];
        if (!is_array($judges)) {
            $judges = $judges ? [$judges] : [];
        }

        $precedentsCited = $ext['precedents_cited'] ?? $payload['precedents_cited'] ?? [];
        if (!is_array($precedentsCited)) {
            $precedentsCited = [];
        }

        $reportable = $ext['reportable'] ?? $payload['reportable'] ?? true;
        $durationDays = isset($ext['duration_days']) ? (int) $ext['duration_days'] : (isset($payload['duration_days']) ? (int) $payload['duration_days'] : null);
        $courtLocation = $ext['court_location'] ?? $payload['court_location'] ?? null;
        $ratioDecidendi = $ext['ratio_decidendi'] ?? $payload['ratio_decidendi'] ?? null;
        $obiterDicta = $ext['obiter_dicta'] ?? $payload['obiter_dicta'] ?? null;
        $order = $ext['order'] ?? $payload['order'] ?? null;
        $summary = $payload['ai_summary'] ?? $ext['summary'] ?? $payload['summary'] ?? $payload['abstract'] ?? null;
        $subjects = $ext['reason_for_dismissal'] ?? $payload['reason_for_dismissal'] ?? $ext['subjects'] ?? $payload['subjects'] ?? $ext['subject'] ?? $payload['subject'] ?? null;
        $outcome = $ext['result'] ?? $payload['result'] ?? $order ?? $ext['holding'] ?? $payload['holding'] ?? null;

        $docDate = $row->document_date ? substr((string) $row->document_date, 0, 10) : null;
        $judgmentDate = $ext['judgment_date'] ?? $payload['judgment_date'] ?? $docDate;
        $hearingDate = $ext['hearing_date'] ?? $payload['hearing_date'] ?? null;

        if (! $isPro) {
            $maskedCaseNumber = $row->case_number ? (strlen($row->case_number) > 4 ? substr($row->case_number, 0, 4).'••••' : '••••') : null;
            $maskedDate = $docDate ? substr($docDate, 0, 4).'-••-••' : null;

            return [
                'id' => $row->id,
                'source_table' => 'legal',
                'record_type' => $row->document_type ?? $row->record_type ?? 'saflii_courts',
                'is_locked' => true,
                'is_pro' => false,
                'document_date' => $maskedDate,
                'judgment_date' => $maskedDate,
                'hearing_date' => $hearingDate ? substr((string) $hearingDate, 0, 4).'-••-••' : null,
                'court' => $row->court,
                'case_number' => $maskedCaseNumber,
                'title' => $row->title,
                'source_url' => null,
                'applicant' => $applicant ? 'Applicant (Locked - Pro Required)' : null,
                'respondent' => $respondent ? 'Respondent (Locked - Pro Required)' : null,
                'subjects' => $subjects,
                'outcome' => $outcome ? 'Judicial Order (Locked - Pro Required)' : null,
                'summary' => $summary,
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
            'id' => $row->id,
            'source_table' => 'legal',
            'record_type' => $row->document_type ?? $row->record_type ?? 'saflii_courts',
            'is_locked' => false,
            'is_pro' => true,
            'document_date' => $docDate,
            'judgment_date' => $judgmentDate,
            'hearing_date' => $hearingDate,
            'court' => $row->court,
            'case_number' => $row->case_number,
            'title' => $row->title,
            'source_url' => $row->source_url,
            'applicant' => $applicant,
            'respondent' => $respondent,
            'subjects' => $subjects,
            'outcome' => $outcome,
            'summary' => $summary,
            'ratio_decidendi' => $ratioDecidendi,
            'obiter_dicta' => $obiterDicta,
            'order' => $order,
            'judges' => $judges,
            'precedents_count' => count($precedentsCited),
            'precedents_cited' => $precedentsCited,
            'reportable' => (bool) $reportable,
            'duration_days' => $durationDays,
            'court_location' => $courtLocation,
        ];
    }

    /**
     * Format a CCMA record into structured dossier item.
     */
    private function formatCcmaRecord($row, bool $isPro = true): array
    {
        $docDate = $row->award_date ?? $row->document_date ?? null;
        if ($docDate) {
            $docDate = substr((string) $docDate, 0, 10);
        }

        $hearingDate = null;
        if (isset($row->hearing_start) && $row->hearing_start) {
            $hearingDate = substr((string) $row->hearing_start, 0, 10);
        }

        if (! $isPro) {
            $maskedCaseNumber = $row->award_number ?? $row->case_number ?? null;
            if ($maskedCaseNumber && strlen($maskedCaseNumber) > 4) {
                $maskedCaseNumber = substr($maskedCaseNumber, 0, 4).'••••';
            }
            $maskedDate = $docDate ? substr($docDate, 0, 4).'-••-••' : null;

            return [
                'id' => $row->id,
                'source_table' => 'ccma',
                'record_type' => $row->document_type ?? $row->record_type ?? 'CCMA Awards',
                'is_locked' => true,
                'is_pro' => false,
                'document_date' => $maskedDate,
                'award_date' => $maskedDate,
                'judgment_date' => $maskedDate,
                'hearing_date' => $hearingDate ? substr((string) $hearingDate, 0, 4).'-••-••' : null,
                'court' => $row->court ?? 'CCMA',
                'case_number' => $maskedCaseNumber,
                'award_number' => $maskedCaseNumber,
                'title' => $row->title,
                'source_url' => null,
                'detail_url' => null,
                'applicant' => ($row->employee ?? $row->applicant) ? 'Employee (Locked)' : null,
                'employee' => ($row->employee ?? $row->applicant) ? 'Employee (Locked)' : null,
                'respondent' => ($row->employer ?? $row->respondent) ? 'Employer (Locked)' : null,
                'employer' => ($row->employer ?? $row->respondent) ? 'Employer (Locked)' : null,
                'subjects' => $row->reason_for_dismissal ?? $row->subjects ?? null,
                'reason_for_dismissal' => $row->reason_for_dismissal ?? $row->subjects ?? null,
                'outcome' => $row->forum ?? $row->court ?? 'CCMA',
                'forum' => $row->forum ?? $row->court ?? 'CCMA',
                'summary' => $row->reason_for_dismissal ?? $row->summary ?? null,
                'ratio_decidendi' => null,
                'obiter_dicta' => null,
                'order' => null,
                'judges' => [],
                'precedents_count' => 0,
                'precedents_cited' => [],
                'reportable' => false,
                'duration_days' => null,
                'court_location' => $row->court_location ?? null,
            ];
        }

        return [
            'id' => $row->id,
            'source_table' => 'ccma',
            'record_type' => $row->document_type ?? $row->record_type ?? 'CCMA Awards',
            'is_locked' => false,
            'is_pro' => true,
            'document_date' => $docDate,
            'award_date' => $docDate,
            'judgment_date' => $docDate,
            'hearing_date' => $hearingDate,
            'court' => $row->court ?? 'CCMA',
            'case_number' => $row->award_number ?? $row->case_number ?? null,
            'award_number' => $row->award_number ?? $row->case_number ?? null,
            'title' => $row->title,
            'source_url' => $row->detail_url ?? $row->source_url ?? null,
            'detail_url' => $row->detail_url ?? $row->source_url ?? null,
            'applicant' => $row->employee ?? $row->applicant ?? null,
            'employee' => $row->employee ?? $row->applicant ?? null,
            'respondent' => $row->employer ?? $row->respondent ?? null,
            'employer' => $row->employer ?? $row->respondent ?? null,
            'subjects' => $row->reason_for_dismissal ?? $row->subjects ?? null,
            'reason_for_dismissal' => $row->reason_for_dismissal ?? $row->subjects ?? null,
            'outcome' => $row->forum ?? $row->court ?? 'CCMA',
            'forum' => $row->forum ?? $row->court ?? 'CCMA',
            'summary' => $row->reason_for_dismissal ?? $row->summary ?? null,
            'ratio_decidendi' => null,
            'obiter_dicta' => null,
            'order' => null,
            'judges' => [],
            'precedents_count' => 0,
            'precedents_cited' => [],
            'reportable' => false,
            'duration_days' => null,
            'court_location' => $row->court_location ?? null,
        ];
    }
}

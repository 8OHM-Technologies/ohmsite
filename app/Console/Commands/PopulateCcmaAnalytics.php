<?php

namespace App\Console\Commands;

use App\Models\CcmaAnalytics;
use App\Models\LegalAnalytics;
use App\Models\ScrubbedRecord;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PopulateCcmaAnalytics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ccma-analytics:populate {--limit=1000 : The maximum number of records to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate the analytics database from scrubbed records in batches';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        $this->info("Starting population of analytics database. Max limit: {$limit} records.");

        // 1. Retrieve all local extracted_record_id values from analytics
        $localIds = CcmaAnalytics::whereNotNull('extracted_record_id')->pluck('extracted_record_id')->toArray();
        $localIdsMap = array_flip($localIds);

        // 2. Retrieve all valid IDs from scrubbed_records (where extracted_records.record_type = 'sabinet_ccma')
        $coeusIds = ScrubbedRecord::join('extracted_records', 'scrubbed_records.extracted_record_id', '=', 'extracted_records.id')
            ->where('extracted_records.record_type', 'sabinet_ccma')
            ->pluck('scrubbed_records.extracted_record_id')
            ->toArray();
        $coeusIdsMap = array_flip($coeusIds);

        // 3. Determine deleted records (present locally, but no longer in coeus)
        $deletedIds = [];
        foreach ($localIds as $id) {
            if (! isset($coeusIdsMap[$id])) {
                $deletedIds[] = $id;
            }
        }

        // 4. Determine new incoming records
        $newIds = [];
        foreach ($coeusIds as $id) {
            if (! isset($localIdsMap[$id])) {
                $newIds[] = $id;
            }
        }

        // Apply limit to new records
        $newIdsToProcess = array_slice($newIds, 0, $limit);

        // If no new records to process and no records to delete, we are done!
        if (empty($newIdsToProcess) && empty($deletedIds)) {
            $this->info('Successfully processed 0 records. Deleted 0 obsolete records.');

            return self::SUCCESS;
        }

        // 5. Fetch and parse new records from scrubbed_records
        $preparedRecords = [];
        $newRecords = ScrubbedRecord::with('extractedRecord')
            ->whereIn('extracted_record_id', $newIdsToProcess)
            ->get();

        foreach ($newRecords as $record) {
            $data = $record->data;

            if (empty($data)) {
                $this->warn("Skipping record ID {$record->id} due to empty data payload.");

                continue;
            }

            try {
                $payload = $data;
                $metadata = $payload['metadata'] ?? null;
                $extractedData = $payload['extracted_data'] ?? null;

                if ($metadata && $extractedData) {
                    // LLM extracted format
                    $employee = $extractedData['employee'] ?? null;
                    $employer = $extractedData['employer'] ?? null;
                    $title = $payload['title'] ?? null;
                    if (! $title && $employee) {
                        $title = $employee.($employer ? ' v '.$employer : '');
                    }
                    if (! $title) {
                        $title = 'Unknown';
                    }

                    $awardNumber = $extractedData['award_number'] ?? 'Unknown';
                    $courtLocation = $extractedData['court_location'] ?? 'Unknown';
                    $reason = $extractedData['reason_for_dismissal'] ?? 'Unknown';
                    $forum = $metadata['entity_name'] ?? 'CCMA';
                    $court = $metadata['entity_name'] ?? 'CCMA';
                    $detailTitle = $title;
                    $previewImageUrl = $payload['preview_image_url'] ?? null;
                    $detailsScrapedAt = $payload['details_scraped_at'] ?? $payload['index_scraped_at'] ?? null;
                    $awardDate = $metadata['document_date'] ?? $payload['award_date'] ?? null;
                } else {
                    // Legacy / Raw Detailed / Indexed format
                    $title = $payload['title'] ?? 'Unknown';
                    $awardNumber = $payload['award_number'] ?? null;

                    $employee = $payload['employee'] ?? null;
                    $employer = $payload['employer'] ?? null;

                    if ($employee === null || $employer === null) {
                        $parsedTitle = $this->parseTitle($title, $awardNumber);
                        $employee = $employee ?? $parsedTitle['employee'];
                        $employer = $employer ?? $parsedTitle['employer'];
                    }

                    $courtLocation = $payload['court_location'] ?? $this->parseCourtLocation($awardNumber);
                    $reason = $payload['reason_for_dismissal'] ?? $this->parseReasonForDismissal($title);
                    $forum = $payload['forum'] ?? $payload['court'] ?? 'CCMA';
                    $court = $payload['court'] ?? 'CCMA';
                    $detailTitle = $payload['detail_title'] ?? $title;
                    $previewImageUrl = $payload['preview_image_url'] ?? null;
                    $detailsScrapedAt = $payload['details_scraped_at'] ?? $payload['index_scraped_at'] ?? null;
                    $awardDate = $payload['award_date'] ?? null;
                }

                $preparedRecords[] = [
                    'extracted_record_id' => $record->extracted_record_id,
                    'title' => $title,
                    'document_type' => $metadata['record_type'] ?? $payload['document_type'] ?? 'CCMA Bargaining Council Awards',
                    'award_date' => $awardDate ?? ($record->extractedRecord?->document_date ? $record->extractedRecord->document_date->toDateString() : now()->toDateString()),
                    'court' => $court ?? 'CCMA',
                    'award_number' => $awardNumber ?? 'Unknown',
                    'hearing_start' => $payload['hearing_start'] ?? null,
                    'hearing_end' => $payload['hearing_end'] ?? null,
                    'date_modified' => $payload['date_modified'] ?? null,
                    'detail_url' => $payload['detail_url'] ?? $record->extractedRecord?->source_url ?? null,
                    'detail_title' => $detailTitle,
                    'employee' => $employee,
                    'employer' => $employer,
                    'forum' => $forum,
                    'court_location' => $courtLocation,
                    'reason_for_dismissal' => $reason,
                    'preview_image_url' => $previewImageUrl,
                    'details_scraped_at' => $detailsScrapedAt ? Carbon::parse($detailsScrapedAt) : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            } catch (\Exception $e) {
                $this->error("Failed to parse record ID {$record->id}: {$e->getMessage()}");
            }
        }

        // 6. Perform the sync inside database transaction with table lock
        if (! empty($preparedRecords) || ! empty($deletedIds)) {
            DB::transaction(function () use ($preparedRecords, $deletedIds) {
                $connection = DB::connection();
                if ($connection->getDriverName() === 'pgsql') {
                    $connection->statement('LOCK TABLE ccma_analytics, backup_ccma_analytics IN ACCESS EXCLUSIVE MODE');
                }

                // Create backup before any changes are made to the current model
                DB::table('backup_ccma_analytics')->truncate();
                $columns = [
                    'id', 'extracted_record_id', 'title', 'document_type', 'award_date', 'court',
                    'award_number', 'hearing_start', 'hearing_end', 'date_modified',
                    'detail_url', 'detail_title', 'employee', 'employer', 'forum',
                    'court_location', 'reason_for_dismissal', 'preview_image_url',
                    'details_scraped_at', 'created_at', 'updated_at',
                ];
                $columnsStr = implode(', ', $columns);
                DB::statement("INSERT INTO backup_ccma_analytics ($columnsStr) SELECT $columnsStr FROM ccma_analytics");

                // Delete local records not present in extracted_records
                if (! empty($deletedIds)) {
                    CcmaAnalytics::whereIn('extracted_record_id', $deletedIds)->delete();
                }

                // Insert new records
                if (! empty($preparedRecords)) {
                    foreach (array_chunk($preparedRecords, 100) as $chunk) {
                        CcmaAnalytics::insert($chunk);
                    }
                }
            });
        }

        $processedCount = count($preparedRecords);
        $deletedCount = count($deletedIds);
        $this->info("Successfully processed {$processedCount} records. Deleted {$deletedCount} obsolete records.");

        // Generate the CSV and JSON dataset files
        $this->generateDatasetFiles();

        Cache::forget('dataset_summary');

        return self::SUCCESS;
    }

    /**
     * Generate CSV and JSON dataset files from the current local Analytics model entries.
     */
    private function generateDatasetFiles(): void
    {
        $this->info('Generating CSV and JSON dataset files...');

        foreach (['ccma', 'all'] as $dataset) {
            $csvHeader = ['ID', 'Case Reference', 'Title', 'Employer', 'Employee', 'Court Location', 'Dismissal Reason', 'Outcome', 'Date Decision', 'Detail URL', 'Details Scraped At'];
            $csvData = [];
            $csvData[] = "\xEF\xBB\xBF".implode(',', array_map(fn ($h) => '"'.str_replace('"', '""', $h).'"', $csvHeader));

            $jsonData = [];

            if ($dataset === 'ccma') {
                $analytics = CcmaAnalytics::all();
                foreach ($analytics as $item) {
                    $row = [
                        $item->id,
                        $item->award_number,
                        $item->title,
                        $item->employer,
                        $item->employee,
                        $item->court_location,
                        $item->reason_for_dismissal,
                        $item->court,
                        $item->award_date ? $item->award_date->toDateString() : null,
                        $item->detail_url,
                        $item->details_scraped_at ? $item->details_scraped_at->toDateTimeString() : null,
                    ];
                    $csvData[] = implode(',', array_map(fn ($val) => '"'.str_replace('"', '""', $val).'"', $row));

                    $jsonData[] = [
                        'id' => $item->id,
                        'case_reference' => $item->award_number,
                        'title' => $item->title,
                        'employer' => $item->employer,
                        'employee' => $item->employee,
                        'court_location' => $item->court_location,
                        'dismissal_reason' => $item->reason_for_dismissal,
                        'outcome' => $item->court,
                        'date_decision' => $item->award_date ? $item->award_date->toDateString() : null,
                        'detail_url' => $item->detail_url,
                        'details_scraped_at' => $item->details_scraped_at ? $item->details_scraped_at->toDateTimeString() : null,
                    ];
                }
            } else {
                // Combined
                $ccmaItems = CcmaAnalytics::all();
                $legalItems = LegalAnalytics::all();

                foreach ($ccmaItems as $item) {
                    $row = [
                        'CCMA_'.$item->id,
                        $item->award_number,
                        $item->title,
                        $item->employer,
                        $item->employee,
                        $item->court_location,
                        $item->reason_for_dismissal,
                        $item->court,
                        $item->award_date ? $item->award_date->toDateString() : null,
                        $item->detail_url,
                        $item->details_scraped_at ? $item->details_scraped_at->toDateTimeString() : null,
                    ];
                    $csvData[] = implode(',', array_map(fn ($val) => '"'.str_replace('"', '""', $val).'"', $row));

                    $jsonData[] = [
                        'id' => 'CCMA_'.$item->id,
                        'case_reference' => $item->award_number,
                        'title' => $item->title,
                        'employer' => $item->employer,
                        'employee' => $item->employee,
                        'court_location' => $item->court_location,
                        'dismissal_reason' => $item->reason_for_dismissal,
                        'outcome' => $item->court,
                        'date_decision' => $item->award_date ? $item->award_date->toDateString() : null,
                        'detail_url' => $item->detail_url,
                        'details_scraped_at' => $item->details_scraped_at ? $item->details_scraped_at->toDateTimeString() : null,
                    ];
                }

                foreach ($legalItems as $item) {
                    $row = [
                        'LEGAL_'.$item->id,
                        $item->case_number,
                        $item->title,
                        $item->respondent,
                        $item->applicant,
                        $item->court_location,
                        $item->subjects,
                        $item->court,
                        $item->document_date ? $item->document_date->toDateString() : null,
                        $item->source_url,
                        $item->created_at ? $item->created_at->toDateTimeString() : null,
                    ];
                    $csvData[] = implode(',', array_map(fn ($val) => '"'.str_replace('"', '""', $val).'"', $row));

                    $jsonData[] = [
                        'id' => 'LEGAL_'.$item->id,
                        'case_reference' => $item->case_number,
                        'title' => $item->title,
                        'employer' => $item->respondent,
                        'employee' => $item->applicant,
                        'court_location' => $item->court_location,
                        'dismissal_reason' => $item->subjects,
                        'outcome' => $item->court,
                        'date_decision' => $item->document_date ? $item->document_date->toDateString() : null,
                        'detail_url' => $item->source_url,
                        'details_scraped_at' => $item->created_at ? $item->created_at->toDateTimeString() : null,
                    ];
                }
            }

            $csvContent = implode("\n", $csvData);
            $jsonContent = json_encode($jsonData, JSON_PRETTY_PRINT);

            // Ensure datasets directory exists in local storage
            if (! Storage::disk('local')->exists('datasets')) {
                Storage::disk('local')->makeDirectory('datasets');
            }

            Storage::disk('local')->put("datasets/8ohm_{$dataset}_dataset.csv", $csvContent);
            Storage::disk('local')->put("datasets/8ohm_{$dataset}_dataset.json", $jsonContent);
        }

        $this->info('Dataset files generated successfully.');
    }

    /**
     * Deduce employee and employer names from the title.
     *
     * Format: "Employee v Employer, AwardNumber" or "Employee v Employer"
     *
     * @return array{employee: string, employer: string}
     */
    private function parseTitle(string $title, ?string $awardNumber): array
    {
        // Split by case-insensitive ' v ' or ' vs '
        $parts = preg_split('/\s+v(?:s)?\.\s+|\s+v(?:s)?\s+/i', $title, 2);

        if (count($parts) === 2) {
            $employee = trim($parts[0]);
            $employerWithAward = trim($parts[1]);

            // If we have an award number, remove it from the end of the employer name
            if (! empty($awardNumber)) {
                $pattern = '/'.preg_quote($awardNumber, '/').'$/i';
                $employer = preg_replace($pattern, '', $employerWithAward);
                $employer = rtrim(trim($employer), ',');
            } else {
                $employer = $employerWithAward;
            }

            return [
                'employee' => $employee ?: '[REDACTED]',
                'employer' => $employer ?: 'Unknown',
            ];
        }

        return [
            'employee' => '[REDACTED]',
            'employer' => $title ?: 'Unknown',
        ];
    }

    /**
     * Deduce court location based on the award number prefix.
     */
    private function parseCourtLocation(?string $awardNumber): string
    {
        if (empty($awardNumber)) {
            return 'Gauteng [Johannesburg]';
        }

        $prefix = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $awardNumber), 0, 2));

        return match ($prefix) {
            'WE' => 'Western Cape [Cape Town]',
            'GA' => 'Gauteng [Johannesburg]',
            'KN' => 'KwaZulu-Natal [Durban]',
            'NW' => 'North West [Rustenburg]',
            'MP' => 'Mpumalanga [Nelspruit]',
            'EC' => 'Eastern Cape [Port Elizabeth]',
            'FS' => 'Free State [Bloemfontein]',
            'LP' => 'Limpopo [Polokwane]',
            default => 'Gauteng [Johannesburg]',
        };
    }

    /**
     * Deduce reason for dismissal based on keywords in title.
     */
    private function parseReasonForDismissal(string $title): string
    {
        $titleLower = strtolower($title);

        if (str_contains($titleLower, 'misconduct')) {
            return 'MISCONDUCT';
        }
        if (str_contains($titleLower, 'incapacity')) {
            return 'INCAPACITY';
        }
        if (str_contains($titleLower, 'retrench') || str_contains($titleLower, 'operational requirement')) {
            return 'OPERATIONAL REQUIREMENTS';
        }
        if (str_contains($titleLower, 'constructive')) {
            return 'CONSTRUCTIVE DISMISSAL';
        }
        if (str_contains($titleLower, 'mutual interest')) {
            return 'MATTERS OF MUTUAL INTEREST';
        }
        if (str_contains($titleLower, 'unfair labour') || str_contains($titleLower, 'unfair labor')) {
            return 'UNFAIR LABOUR PRACTICE';
        }

        return 'UNFAIR DISMISSAL';
    }
}

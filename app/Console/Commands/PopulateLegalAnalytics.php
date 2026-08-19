<?php

namespace App\Console\Commands;

use App\Models\CcmaAnalytics;
use App\Models\ExtractedRecord;
use App\Models\LegalAnalytics;
use App\Models\ScrubbedRecord;
use App\Models\TargetVanity;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PopulateLegalAnalytics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'legal-analytics:populate {--limit=1000 : The maximum number of records to process} {--fresh : Truncate and re-sync all records from scratch}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate the legal analytics database from scrubbed records in batches';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            return $this->doHandle();
        } catch (\Throwable $e) {
            $this->error("FATAL EXCEPTION: " . $e->getMessage());
            $this->error("At file: " . $e->getFile() . " line " . $e->getLine());
            return self::FAILURE;
        }
    }

    private function doHandle(): int
    {
        $limit = (int) $this->option('limit');
        $fresh = (bool) $this->option('fresh');

        if ($fresh) {
            $this->info("Fresh mode enabled: truncating local legal_analytics table...");
            LegalAnalytics::truncate();
        }

        $this->info("Starting population of legal analytics database. Max limit: {$limit} records.");

        // 1. Retrieve all local extracted_record_id values from legal_analytics
        $localIds = $fresh ? [] : LegalAnalytics::whereNotNull('extracted_record_id')->pluck('extracted_record_id')->toArray();
        $localIdsMap = array_flip($localIds);

        // 2. Retrieve all valid IDs from scrubbed_records (where extracted_records.record_type != 'sabinet_ccma')
        $coeusIds = ScrubbedRecord::join('extracted_records', 'scrubbed_records.extracted_record_id', '=', 'extracted_records.id')
            ->where('extracted_records.record_type', '!=', 'sabinet_ccma')
            ->pluck('scrubbed_records.extracted_record_id')
            ->toArray();
        $coeusIdsMap = array_flip($coeusIds);

        // 3. Determine deleted records
        $deletedIds = [];
        foreach ($localIds as $id) {
            if (! isset($coeusIdsMap[$id])) {
                $deletedIds[] = $id;
            }
        }

        // 4. Determine new records
        $newIds = [];
        foreach ($coeusIds as $id) {
            if (! isset($localIdsMap[$id])) {
                $newIds[] = $id;
            }
        }

        // Limit new records to process in this run
        $newIdsToProcess = array_slice($newIds, 0, $limit);

        // If no records to process and none to delete, we are done!
        if (empty($newIdsToProcess) && empty($deletedIds)) {
            $this->info('Successfully processed 0 records. Deleted 0 obsolete records.');

            return self::SUCCESS;
        }

        $processedCount = 0;
        $skippedCount = 0;
        $failedCount = 0;

        // 5. Perform the sync inside database transaction with table lock
        DB::transaction(function () use ($newIdsToProcess, $deletedIds, &$processedCount, &$skippedCount, &$failedCount) {
            $connection = DB::connection();
            if ($connection->getDriverName() === 'pgsql') {
                $connection->statement('LOCK TABLE legal_analytics, backup_legal_analytics IN ACCESS EXCLUSIVE MODE');
            }

            // Create backup before any changes are made to the current model
            DB::table('backup_legal_analytics')->truncate();
            $columns = [
                'id', 'extracted_record_id', 'target_type', 'target_name', 'title',
                'document_type', 'document_date', 'court', 'case_number',
                'source_url', 'data', 'created_at', 'updated_at',
            ];
            $columnsStr = implode(', ', $columns);
            DB::statement("INSERT INTO backup_legal_analytics ($columnsStr) SELECT $columnsStr FROM legal_analytics");

            // Delete local records not present in coeus
            if (! empty($deletedIds)) {
                LegalAnalytics::whereIn('extracted_record_id', $deletedIds)->delete();
            }

            // Fetch, parse, and insert records in small, isolated database batches of 10 to prevent memory exhaustion
            foreach (array_chunk($newIdsToProcess, 10) as $chunkIds) {
                $records = ScrubbedRecord::with(['extractedRecord.target'])
                    ->whereIn('extracted_record_id', $chunkIds)
                    ->get();

                $chunkPrepared = [];
                foreach ($records as $scrubbedRecord) {
                    $payload = $scrubbedRecord->data;

                    if (empty($payload)) {
                        $skippedCount++;
                        continue;
                    }

                    try {
                        $extractedRecord = $scrubbedRecord->extractedRecord;
                        $target = $extractedRecord?->target;

                        $extData = is_array($payload['extracted_data'] ?? null) ? $payload['extracted_data'] : [];
                        $metaData = is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [];

                        $sourceUrl = $extractedRecord?->source_url ?? $payload['url'] ?? $payload['source_url'] ?? null;

                        $extractedCode = null;
                        $extractedType = 'cases';
                        if (!empty($sourceUrl) && preg_match('/\/(cases|journals|gazettes|other|rolls)\/([A-Za-z0-9_-]+)\//i', $sourceUrl, $m)) {
                            $extractedType = strtolower($m[1]) === 'gazettes' ? 'gaz' : (strtolower($m[1]) === 'rolls' ? 'other' : strtolower($m[1]));
                            $extractedCode = $m[2];
                        }

                        $court = $extData['court'] ?? $payload['court'] ?? $metaData['entity_name'] ?? $extractedCode;

                        $vanityMatch = null;
                        if ($extractedCode || $court) {
                            $codeCandidate = $extractedCode ?: $court;
                            $vanityMatch = TargetVanity::where('target_name', $codeCandidate)
                                ->orWhere('target_name', strtoupper($codeCandidate))
                                ->first();
                        }

                        $targetType = $vanityMatch?->target_type ?: ($target?->target_type ?: ($metaData['target_type'] ?? $extractedType));
                        $targetName = $vanityMatch?->target_name ?: ($metaData['target_name'] ?? ($extractedCode ?: ($court ?: ($target?->target_name ?: 'Unknown'))));

                        // Clean target name and target type to ensure they are never null or empty strings
                        $targetType = empty($targetType) ? 'cases' : $targetType;
                        $targetName = empty($targetName) ? 'Unknown' : $targetName;

                        $title = $payload['title'] ?? $payload['name'] ?? null;
                        if (empty($title)) {
                            $applicant = $extData['applicant_plaintiff'] ?? $payload['applicant_plaintiff'] ?? $payload['employee'] ?? null;
                            $respondent = $extData['respondent_defendant'] ?? $payload['respondent_defendant'] ?? $payload['employer'] ?? null;
                            if (is_array($applicant)) {
                                $applicant = implode(', ', $applicant);
                            }
                            if (is_array($respondent)) {
                                $respondent = implode(', ', $respondent);
                            }
                            if ($applicant && $respondent) {
                                $title = $applicant . ' v ' . $respondent;
                            }
                        }
                        if (empty($title) && ! empty($extractedRecord?->data)) {
                            $rawPayload = is_string($extractedRecord->data) ? json_decode($extractedRecord->data, true) : (array) $extractedRecord->data;
                            $title = $rawPayload['title'] ?? $rawPayload['name'] ?? null;
                        }
                        $title = $title ?: ('Record #' . substr($scrubbedRecord->extracted_record_id, 0, 8));

                        $documentType = $metaData['record_type'] ?? $extractedRecord?->record_type ?? 'saflii_courts';
                        $documentDate = $extractedRecord?->document_date ? $extractedRecord->document_date->toDateString() : ($extData['judgment_date'] ?? $extData['hearing_date'] ?? $metaData['document_date'] ?? $payload['judgment_date'] ?? $payload['date'] ?? null);
                        $caseNumber = $metaData['case_number'] ?? $extData['case_number'] ?? $payload['case_number'] ?? $payload['dataset_number'] ?? $payload['gazette_number'] ?? $payload['volume'] ?? null;

                        $chunkPrepared[] = [
                            'extracted_record_id' => $scrubbedRecord->extracted_record_id,
                            'target_type' => $targetType,
                            'target_name' => $targetName,
                            'title' => $title,
                            'document_type' => $documentType,
                            'document_date' => $documentDate,
                            'court' => $court ?: $targetName,
                            'case_number' => $caseNumber,
                            'source_url' => $sourceUrl,
                            'data' => json_encode($payload),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    } catch (\Exception $e) {
                        $failedCount++;
                    }
                }

                // Write batch directly to the database
                if (! empty($chunkPrepared)) {
                    try {
                        LegalAnalytics::insert($chunkPrepared);
                        $processedCount += count($chunkPrepared);
                    } catch (\Throwable $e) {
                        $sample = $chunkPrepared[0] ?? [];
                        unset($sample['data']);
                        throw new \Exception("INSERT FAILURE: " . $e->getMessage() . " | Sample row metadata: " . json_encode($sample));
                    }
                }

                // Free memory for this batch
                unset($records);
                unset($chunkPrepared);
                gc_collect_cycles();
            }
        });

        $this->info("Successfully processed {$processedCount} records. Deleted " . count($deletedIds) . " obsolete records.");
        if ($skippedCount > 0) {
            $this->warn("Skipped {$skippedCount} records due to empty data payload.");
        }
        if ($failedCount > 0) {
            $this->error("Failed to parse {$failedCount} records.");
        }

        // Generate the CSV and JSON dataset files
        $this->generateDatasetFiles();

        Cache::forget('dataset_summary');

        return self::SUCCESS;
    }

    /**
     * Generate CSV and JSON dataset files from the current local analytics models in memory-efficient chunks.
     */
    private function generateDatasetFiles(): void
    {
        $this->info('Generating CSV and JSON dataset files...');

        foreach (['saflii', 'all'] as $dataset) {
            $csvHeader = ['ID', 'Case Reference', 'Title', 'Employer', 'Employee', 'Court Location', 'Dismissal Reason', 'Outcome', 'Date Decision', 'Detail URL', 'Details Scraped At'];
            $csvData = [];
            $csvData[] = "\xEF\xBB\xBF".implode(',', array_map(fn ($h) => '"'.str_replace('"', '""', $h).'"', $csvHeader));

            $jsonData = [];

            if ($dataset === 'saflii') {
                LegalAnalytics::query()->chunk(10, function ($analytics) use (&$csvData, &$jsonData) {
                    foreach ($analytics as $item) {
                        $row = [
                            $item->id,
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
                        $csvData[] = implode(',', array_map(fn ($val) => '"'.str_replace('"', '""', $val ?? '').'"', $row));

                        $jsonData[] = [
                            'id' => $item->id,
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
                    gc_collect_cycles();
                });
            } else {
                // Combined
                CcmaAnalytics::query()->chunk(10, function ($ccmaItems) use (&$csvData, &$jsonData) {
                    foreach ($ccmaItems as $item) {
                        $row = [
                            'CCMA_' . $item->id,
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
                        $csvData[] = implode(',', array_map(fn ($val) => '"'.str_replace('"', '""', $val ?? '').'"', $row));

                        $jsonData[] = [
                            'id' => 'CCMA_' . $item->id,
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
                    gc_collect_cycles();
                });

                LegalAnalytics::query()->chunk(10, function ($legalItems) use (&$csvData, &$jsonData) {
                    foreach ($legalItems as $item) {
                        $row = [
                            'LEGAL_' . $item->id,
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
                        $csvData[] = implode(',', array_map(fn ($val) => '"'.str_replace('"', '""', $val ?? '').'"', $row));

                        $jsonData[] = [
                            'id' => 'LEGAL_' . $item->id,
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
                    gc_collect_cycles();
                });
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
}

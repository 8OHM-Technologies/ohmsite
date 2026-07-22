<?php

namespace App\Console\Commands;

use App\Models\Analytics;
use App\Models\ExtractedData;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PopulateAnalytics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analytics:populate {--limit=1000 : The maximum number of records to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate the analytics database from extracted records in batches';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        $this->info("Starting population of analytics database. Max limit: {$limit} records.");

        // 1. Retrieve all local extracted_record_id values from analytics
        $localIds = Analytics::whereNotNull('extracted_record_id')->pluck('extracted_record_id')->toArray();
        $localIdsMap = array_flip($localIds);

        // 2. Retrieve all valid IDs from extracted_records (cleaned_at IS NOT NULL)
        $coeusIds = ExtractedData::whereNotNull('cleaned_at')->pluck('id')->toArray();
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

        // 5. Fetch and parse new records
        $preparedRecords = [];
        $newRecords = ExtractedData::whereIn('id', $newIdsToProcess)->get();

        foreach ($newRecords as $record) {
            $data = $record->data;

            if (empty($data)) {
                $this->warn("Skipping record ID {$record->id} due to empty data payload.");

                continue;
            }

            try {
                $title = $data['title'] ?? 'Unknown';
                $awardNumber = $data['award_number'] ?? null;

                // Handle Detailed vs Indexed state
                $employee = $data['employee'] ?? null;
                $employer = $data['employer'] ?? null;

                if ($employee === null || $employer === null) {
                    $parsedTitle = $this->parseTitle($title, $awardNumber);
                    $employee = $employee ?? $parsedTitle['employee'];
                    $employer = $employer ?? $parsedTitle['employer'];
                }

                $courtLocation = $data['court_location'] ?? $this->parseCourtLocation($awardNumber);
                $reason = $data['reason_for_dismissal'] ?? $this->parseReasonForDismissal($title);
                $forum = $data['forum'] ?? $data['court'] ?? 'CCMA';
                $detailTitle = $data['detail_title'] ?? $title;
                $previewImageUrl = $data['preview_image_url'] ?? null;
                $detailsScrapedAt = $data['details_scraped_at'] ?? $data['index_scraped_at'] ?? null;

                $preparedRecords[] = [
                    'extracted_record_id' => $record->id,
                    'title' => $title,
                    'document_type' => $data['document_type'] ?? 'CCMA Bargaining Council Awards',
                    'award_date' => $data['award_date'] ?? ($record->document_date ? $record->document_date->toDateString() : now()->toDateString()),
                    'court' => $data['court'] ?? 'CCMA',
                    'award_number' => $awardNumber ?? 'Unknown',
                    'hearing_start' => $data['hearing_start'] ?? null,
                    'hearing_end' => $data['hearing_end'] ?? null,
                    'date_modified' => $data['date_modified'] ?? null,
                    'detail_url' => $data['detail_url'] ?? null,
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
                    $connection->statement('LOCK TABLE analytics, backup_analytics IN ACCESS EXCLUSIVE MODE');
                }

                // Create backup before any changes are made to the current model
                DB::table('backup_analytics')->truncate();
                $columns = [
                    'id', 'extracted_record_id', 'title', 'document_type', 'award_date', 'court',
                    'award_number', 'hearing_start', 'hearing_end', 'date_modified',
                    'detail_url', 'detail_title', 'employee', 'employer', 'forum',
                    'court_location', 'reason_for_dismissal', 'preview_image_url',
                    'details_scraped_at', 'created_at', 'updated_at',
                ];
                $columnsStr = implode(', ', $columns);
                DB::statement("INSERT INTO backup_analytics ($columnsStr) SELECT $columnsStr FROM analytics");

                // Delete local records not present in extracted_records
                if (! empty($deletedIds)) {
                    Analytics::whereIn('extracted_record_id', $deletedIds)->delete();
                }

                // Insert new records
                if (! empty($preparedRecords)) {
                    foreach (array_chunk($preparedRecords, 100) as $chunk) {
                        Analytics::insert($chunk);
                    }
                }
            });
        }

        // 7. Update processed_at timestamps in coeus DB for all processed records (including skipped/failed)
        if (! empty($newIdsToProcess)) {
            ExtractedData::whereIn('id', $newIdsToProcess)->update([
                'processed_at' => now(),
            ]);
        }

        $processedCount = count($preparedRecords);
        $deletedCount = count($deletedIds);
        $this->info("Successfully processed {$processedCount} records. Deleted {$deletedCount} obsolete records.");

        // Generate the CSV and JSON dataset files
        $this->generateDatasetFiles();

        return self::SUCCESS;
    }

    /**
     * Generate CSV and JSON dataset files from the current local Analytics model entries.
     */
    private function generateDatasetFiles(): void
    {
        $this->info('Generating CSV and JSON dataset files...');

        $csvHeader = ['ID', 'Case Reference', 'Title', 'Employer', 'Employee', 'Court Location', 'Dismissal Reason', 'Outcome', 'Date Decision', 'Detail URL', 'Details Scraped At'];
        $csvData = [];
        $csvData[] = "\xEF\xBB\xBF".implode(',', array_map(fn ($h) => '"'.str_replace('"', '""', $h).'"', $csvHeader));

        $jsonData = [];

        $analytics = Analytics::all();
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

        $csvContent = implode("\n", $csvData);
        $jsonContent = json_encode($jsonData, JSON_PRETTY_PRINT);

        // Ensure datasets directory exists in local storage
        if (! Storage::disk('local')->exists('datasets')) {
            Storage::disk('local')->makeDirectory('datasets');
        }

        foreach (['ccma', 'all'] as $dataset) {
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

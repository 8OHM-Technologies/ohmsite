<?php

namespace App\Console\Commands;

use App\Models\Analytics;
use App\Models\ExtractedData;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

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
        $processedCount = 0;
        $batchSize = 100;

        $this->info("Starting population of analytics database. Max limit: {$limit} records.");

        while ($processedCount < $limit) {
            $chunkSize = min($batchSize, $limit - $processedCount);

            // Fetch a chunk of unprocessed records
            $records = ExtractedData::whereNull('processed_at')
                ->limit($chunkSize)
                ->get();

            if ($records->isEmpty()) {
                break;
            }

            foreach ($records as $record) {
                $data = $record->data;

                if (empty($data)) {
                    $this->warn("Skipping record ID {$record->id} due to empty data payload.");
                    try {
                        $record->update([
                            'processed_at' => now(),
                        ]);
                    } catch (\Exception $e) {
                        $this->error("Failed to mark empty record ID {$record->id} as processed: {$e->getMessage()}");
                    }
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

                    // Create the analytics entry in pgsql database
                    Analytics::create([
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
                    ]);

                    // Update the coeus record
                    $record->update([
                        'processed_at' => now(),
                    ]);

                    $processedCount++;
                } catch (\Exception $e) {
                    $this->error("Failed to process record ID {$record->id}: {$e->getMessage()}");
                    try {
                        $record->update([
                            'processed_at' => now(),
                        ]);
                    } catch (\Exception $updateEx) {
                        $this->error("Could not mark record {$record->id} as processed: {$updateEx->getMessage()}");
                    }
                }
            }
        }

        $this->info("Successfully processed {$processedCount} records.");

        return self::SUCCESS;
    }

    /**
     * Deduce employee and employer names from the title.
     *
     * Format: "Employee v Employer, AwardNumber" or "Employee v Employer"
     *
     * @param string $title
     * @param string|null $awardNumber
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
                $pattern = '/' . preg_quote($awardNumber, '/') . '$/i';
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
     *
     * @param string|null $awardNumber
     * @return string
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
     *
     * @param string $title
     * @return string
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

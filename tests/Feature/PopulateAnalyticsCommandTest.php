<?php

namespace Tests\Feature;

use App\Models\CcmaAnalytics;
use App\Models\BackupCcmaAnalytics;
use App\Models\LegalAnalytics;
use App\Models\BackupLegalAnalytics;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class PopulateAnalyticsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Redirect pgsql_coeus to an isolated SQLite in-memory database during tests
        config(['database.connections.pgsql_coeus' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);

        // Create the entities, targets, extracted_records, and scrubbed_records tables in the test pgsql_coeus connection
        $db = DB::connection('pgsql_coeus');
        $db->statement('CREATE TABLE entities (id VARCHAR(36) PRIMARY KEY, name VARCHAR(255) UNIQUE, identifier VARCHAR(50), created_at TIMESTAMP)');
        $db->statement('CREATE TABLE targets (id VARCHAR(36) PRIMARY KEY, entity_id VARCHAR(36), target_name VARCHAR(255), target_type VARCHAR(50), location VARCHAR(255), created_at TIMESTAMP)');
        $db->statement('CREATE TABLE extracted_records (id VARCHAR(36) PRIMARY KEY, target_id VARCHAR(36), document_date DATE, record_type VARCHAR(100), data TEXT, requires_human_review BOOLEAN DEFAULT FALSE, review_reason TEXT, source_url TEXT, status VARCHAR(50), extracted_at TIMESTAMP, processed_at TIMESTAMP, cleaned_at TIMESTAMP)');
        $db->statement('CREATE TABLE scrubbed_records (id VARCHAR(36) PRIMARY KEY, extracted_record_id VARCHAR(36), data TEXT, created_at TIMESTAMP)');
    }

    private function createTarget(?string $targetName = null, string $targetType = 'cases'): string
    {
        $entityId = Str::uuid()->toString();
        $targetId = Str::uuid()->toString();

        DB::connection('pgsql_coeus')->table('entities')->insert([
            'id' => $entityId,
            'name' => 'Test Entity '.$entityId,
            'created_at' => now(),
        ]);

        DB::connection('pgsql_coeus')->table('targets')->insert([
            'id' => $targetId,
            'entity_id' => $entityId,
            'target_name' => $targetName ?? ('Test Target '.$targetId),
            'target_type' => $targetType,
            'location' => 'https://example.com',
            'created_at' => now(),
        ]);

        return $targetId;
    }

    public function test_populates_indexed_data_correctly(): void
    {
        Storage::fake('local');
        $targetId = $this->createTarget();
        $id = Str::uuid()->toString();

        $dataPayload = [
            'court' => 'CCMA',
            'title' => 'Gumede v Mastercraft, KN39790',
            'award_date' => '2000-07-01',
            'detail_url' => 'https://discover.sabinet.co.za/document/1628547',
            'hearing_end' => '2000-05-30',
            'award_number' => 'KN39790',
            'date_modified' => '2019-10-29',
            'document_type' => 'CCMA Bargaining Council Awards',
            'hearing_start' => '2000-03-22',
            'index_scraped_at' => '2026-07-10T16:11:35.943615',
        ];

        DB::connection('pgsql_coeus')->table('extracted_records')->insert([
            'id' => $id,
            'target_id' => $targetId,
            'document_date' => '2000-07-01',
            'record_type' => 'sabinet_ccma',
            'data' => json_encode($dataPayload),
            'requires_human_review' => false,
            'status' => 'detailed',
            'extracted_at' => now(),
            'processed_at' => null,
            'cleaned_at' => now(),
        ]);

        DB::connection('pgsql_coeus')->table('scrubbed_records')->insert([
            'id' => Str::uuid()->toString(),
            'extracted_record_id' => $id,
            'data' => json_encode($dataPayload),
            'created_at' => now(),
        ]);

        $this->artisan('ccma-analytics:populate')
            ->expectsOutput('Starting population of analytics database. Max limit: 1000 records.')
            ->expectsOutput('Successfully processed 1 records. Deleted 0 obsolete records.')
            ->assertExitCode(0);

        // Verify ccma_analytics table has the mapped record
        $analytic = CcmaAnalytics::first();
        $this->assertNotNull($analytic);
        $this->assertEquals('Gumede v Mastercraft, KN39790', $analytic->title);
        $this->assertEquals('CCMA Bargaining Council Awards', $analytic->document_type);
        $this->assertEquals('2000-07-01', $analytic->award_date->toDateString());
        $this->assertEquals('CCMA', $analytic->court);
        $this->assertEquals('KN39790', $analytic->award_number);
        $this->assertEquals('2000-03-22', $analytic->hearing_start->toDateString());
        $this->assertEquals('2000-05-30', $analytic->hearing_end->toDateString());
        $this->assertEquals('2019-10-29', $analytic->date_modified->toDateString());
        $this->assertEquals('https://discover.sabinet.co.za/document/1628547', $analytic->detail_url);
        $this->assertEquals('Gumede v Mastercraft, KN39790', $analytic->detail_title);
        $this->assertEquals('Gumede', $analytic->employee);
        $this->assertEquals('Mastercraft', $analytic->employer);
        $this->assertEquals('CCMA', $analytic->forum);
        $this->assertEquals('KwaZulu-Natal [Durban]', $analytic->court_location);
        $this->assertEquals('UNFAIR DISMISSAL', $analytic->reason_for_dismissal);

        // Verify dataset files were generated
        Storage::disk('local')->assertExists('datasets/8ohm_ccma_dataset.csv');
        Storage::disk('local')->assertExists('datasets/8ohm_ccma_dataset.json');
        Storage::disk('local')->assertExists('datasets/8ohm_all_dataset.csv');
        Storage::disk('local')->assertExists('datasets/8ohm_all_dataset.json');
    }

    public function test_populates_detailed_data_correctly(): void
    {
        $targetId = $this->createTarget();
        $id = Str::uuid()->toString();

        $dataPayload = [
            'court' => 'CCMA',
            'forum' => 'CCMA Forum',
            'title' => 'Melikhaya Richard Jikane v Quattro Protection Services (Pty) Ltd, WE54',
            'employee' => 'Melikhaya Richard Jikane',
            'employer' => 'Quattro Protection Services (Pty) Ltd',
            'award_date' => '1996-11-01',
            'detail_url' => 'https://discover.sabinet.co.za/document/1608136',
            'hearing_end' => '1996-11-27',
            'award_number' => 'WE54',
            'detail_title' => 'Detailed Title Test',
            'date_modified' => '2019-10-28',
            'document_type' => 'CCMA Bargaining Council Awards',
            'hearing_start' => '1996-11-27',
            'court_location' => 'Western Cape [Cape Town]',
            'index_scraped_at' => '2026-07-10T13:34:12.453216',
            'preview_image_url' => 'https://discover.sabinet.co.za/preview.jpg',
            'details_scraped_at' => '2026-07-12T21:14:46.678241',
            'reason_for_dismissal' => 'Unfair Dismissal Disputes',
        ];

        DB::connection('pgsql_coeus')->table('extracted_records')->insert([
            'id' => $id,
            'target_id' => $targetId,
            'document_date' => '1996-11-01',
            'record_type' => 'sabinet_ccma',
            'data' => json_encode($dataPayload),
            'requires_human_review' => false,
            'status' => 'detailed',
            'extracted_at' => now(),
            'processed_at' => null,
            'cleaned_at' => now(),
        ]);

        DB::connection('pgsql_coeus')->table('scrubbed_records')->insert([
            'id' => Str::uuid()->toString(),
            'extracted_record_id' => $id,
            'data' => json_encode($dataPayload),
            'created_at' => now(),
        ]);

        $this->artisan('ccma-analytics:populate')
            ->expectsOutput('Starting population of analytics database. Max limit: 1000 records.')
            ->expectsOutput('Successfully processed 1 records. Deleted 0 obsolete records.')
            ->assertExitCode(0);

        // Verify ccma_analytics table has the mapped record
        $analytic = CcmaAnalytics::first();
        $this->assertNotNull($analytic);
        $this->assertEquals('Melikhaya Richard Jikane v Quattro Protection Services (Pty) Ltd, WE54', $analytic->title);
        $this->assertEquals('CCMA Bargaining Council Awards', $analytic->document_type);
        $this->assertEquals('1996-11-01', $analytic->award_date->toDateString());
        $this->assertEquals('CCMA', $analytic->court);
        $this->assertEquals('WE54', $analytic->award_number);
        $this->assertEquals('1996-11-27', $analytic->hearing_start->toDateString());
        $this->assertEquals('1996-11-27', $analytic->hearing_end->toDateString());
        $this->assertEquals('2019-10-28', $analytic->date_modified->toDateString());
        $this->assertEquals('https://discover.sabinet.co.za/document/1608136', $analytic->detail_url);
        $this->assertEquals('Detailed Title Test', $analytic->detail_title);
        $this->assertEquals('Melikhaya Richard Jikane', $analytic->employee);
        $this->assertEquals('Quattro Protection Services (Pty) Ltd', $analytic->employer);
        $this->assertEquals('CCMA Forum', $analytic->forum);
        $this->assertEquals('Western Cape [Cape Town]', $analytic->court_location);
        $this->assertEquals('Unfair Dismissal Disputes', $analytic->reason_for_dismissal);
        $this->assertEquals('https://discover.sabinet.co.za/preview.jpg', $analytic->preview_image_url);
        $this->assertEquals('2026-07-12 21:14:46', $analytic->details_scraped_at->toDateTimeString());
    }

    public function test_populates_sabinet_llm_data_correctly(): void
    {
        Storage::fake('local');
        $targetId = $this->createTarget();
        $id = Str::uuid()->toString();

        DB::connection('pgsql_coeus')->table('extracted_records')->insert([
            'id' => $id,
            'target_id' => $targetId,
            'document_date' => '2026-06-18',
            'record_type' => 'sabinet_ccma',
            'data' => json_encode([
                'title' => 'Gumede v Mastercraft',
            ]),
            'requires_human_review' => false,
            'status' => 'detailed',
            'extracted_at' => now(),
            'processed_at' => null,
            'cleaned_at' => now(),
        ]);

        DB::connection('pgsql_coeus')->table('scrubbed_records')->insert([
            'id' => Str::uuid()->toString(),
            'extracted_record_id' => $id,
            'data' => json_encode([
                'metadata' => [
                    'entity_name' => 'CCMA',
                    'target_name' => 'Durban Regional Office',
                    'document_date' => '2026-06-18',
                    'record_type' => 'Arbitration Award',
                ],
                'extracted_data' => [
                    'employer' => 'Mastercraft Retail',
                    'employee' => 'Gumede',
                    'arbitrator' => 'Mnguni [AJ]',
                    'award_number' => 'KN39790',
                    'court_location' => 'KwaZulu-Natal [Durban]',
                    'reason_for_dismissal' => 'Misconduct: Employee accused of unauthorized absence',
                    'outcome' => 'Dismissed',
                    'costs_order' => 'No order as to costs',
                ],
            ]),
            'created_at' => now(),
        ]);

        $this->artisan('ccma-analytics:populate')
            ->expectsOutput('Starting population of analytics database. Max limit: 1000 records.')
            ->expectsOutput('Successfully processed 1 records. Deleted 0 obsolete records.')
            ->assertExitCode(0);

        $analytic = CcmaAnalytics::first();
        $this->assertNotNull($analytic);
        $this->assertEquals('Gumede v Mastercraft Retail', $analytic->title);
        $this->assertEquals('Arbitration Award', $analytic->document_type);
        $this->assertEquals('2026-06-18', $analytic->award_date->toDateString());
        $this->assertEquals('CCMA', $analytic->court);
        $this->assertEquals('KN39790', $analytic->award_number);
        $this->assertEquals('Gumede', $analytic->employee);
        $this->assertEquals('Mastercraft Retail', $analytic->employer);
        $this->assertEquals('CCMA', $analytic->forum);
        $this->assertEquals('KwaZulu-Natal [Durban]', $analytic->court_location);
        $this->assertEquals('Misconduct: Employee accused of unauthorized absence', $analytic->reason_for_dismissal);
    }

    public function test_populates_legal_saflii_data_correctly(): void
    {
        Storage::fake('local');
        $targetId = $this->createTarget('ZACC', 'cases');
        $id = Str::uuid()->toString();

        DB::connection('pgsql_coeus')->table('extracted_records')->insert([
            'id' => $id,
            'target_id' => $targetId,
            'document_date' => '2026-06-18',
            'record_type' => 'saflii_courts',
            'data' => json_encode([
                'url' => 'https://www.saflii.org/za/cases/ZACC/2026/1.html',
                'title' => 'State v Zuma and Others',
            ]),
            'requires_human_review' => false,
            'status' => 'detailed',
            'extracted_at' => now(),
            'processed_at' => null,
            'cleaned_at' => now(),
        ]);

        DB::connection('pgsql_coeus')->table('scrubbed_records')->insert([
            'id' => Str::uuid()->toString(),
            'extracted_record_id' => $id,
            'data' => json_encode([
                'title' => 'State v Zuma and Others',
                'metadata' => [
                    'entity_name' => 'Saflii',
                    'target_name' => 'ZACC',
                    'document_date' => '2026-06-18',
                    'record_type' => 'Judgment',
                    'case_number' => 'CCT 12/25',
                ],
                'extracted_data' => [
                    'applicant_plaintiff' => 'State',
                    'respondent_defendant' => ['Zuma', 'Ministers of Justice'],
                    'hearing_date' => '2026-06-12',
                    'judgment_date' => '2026-06-18',
                    'reportable' => false,
                    'court' => 'Constitutional Court',
                    'judges' => ['Zondo CJ', 'Goliath AJ'],
                    'court_location' => 'Johannesburg',
                    'ratio_decidendi' => 'Core binding legal rule established by the majority.',
                    'precedents_cited' => [
                        [
                            'case_name_citation' => 'S v Makwanyane',
                            'treatment' => 'Applied/Followed',
                            'reasoning' => 'Foundational human rights precedent.',
                            'url' => 'https://www.saflii.org/za/cases/ZACC/1995/3.html',
                        ],
                    ],
                    'obiter_dicta' => 'Observations on constitutional values.',
                    'order' => 'Application for leave to appeal dismissed.',
                    'summary' => 'The applicant, ID number [RSA ID], sought urgent relief regarding...',
                    'keywords' => ['leave to appeal', 'constitutional challenge'],
                ],
                'data_quality_flags' => [
                    'requires_human_review' => false,
                    'review_reason' => null,
                ],
            ]),
            'created_at' => now(),
        ]);

        $this->artisan('legal-analytics:populate')
            ->expectsOutput('Starting population of legal analytics database. Max limit: 1000 records.')
            ->expectsOutput('Successfully processed 1 records. Deleted 0 obsolete records.')
            ->assertExitCode(0);

        // Verify legal_analytics table has the mapped record
        $analytic = LegalAnalytics::first();
        $this->assertNotNull($analytic);
        $this->assertEquals('State v Zuma and Others', $analytic->title);
        $this->assertEquals('Judgment', $analytic->document_type);
        $this->assertEquals('cases', $analytic->target_type);
        $this->assertEquals('ZACC', $analytic->target_name);
        $this->assertEquals('2026-06-18', $analytic->document_date->toDateString());
        $this->assertEquals('Constitutional Court', $analytic->court);
        $this->assertEquals('CCT 12/25', $analytic->case_number);
        $this->assertEquals('State', $analytic->applicant);
        $this->assertEquals('Zuma, Ministers of Justice', $analytic->respondent);
        $this->assertEquals('Application for leave to appeal dismissed.', $analytic->outcome);
        $this->assertEquals('Johannesburg', $analytic->court_location);
        $this->assertEquals('leave to appeal, constitutional challenge', $analytic->subjects);
        $this->assertEquals('Core binding legal rule established by the majority.', $analytic->ratio_decidendi);
        $this->assertEquals('Observations on constitutional values.', $analytic->obiter_dicta);
        $this->assertEquals(['Zondo CJ', 'Goliath AJ'], $analytic->judges);

        // Verify dataset files were generated
        Storage::disk('local')->assertExists('datasets/8ohm_saflii_dataset.csv');
        Storage::disk('local')->assertExists('datasets/8ohm_saflii_dataset.json');
        Storage::disk('local')->assertExists('datasets/8ohm_all_dataset.csv');
        Storage::disk('local')->assertExists('datasets/8ohm_all_dataset.json');
    }

    public function test_populates_legal_saflii_journal_record_correctly(): void
    {
        Storage::fake('local');
        $targetId = $this->createTarget('PER', 'journals');
        $id = Str::uuid()->toString();

        DB::connection('pgsql_coeus')->table('extracted_records')->insert([
            'id' => $id,
            'target_id' => $targetId,
            'document_date' => '2025-01-15',
            'record_type' => 'saflii_courts',
            'source_url' => 'https://www.saflii.org/za/journals/PER/2025/1.html',
            'data' => json_encode(['title' => 'Constitutional Law in Practice']),
            'requires_human_review' => false,
            'status' => 'detailed',
        ]);

        DB::connection('pgsql_coeus')->table('scrubbed_records')->insert([
            'id' => Str::uuid()->toString(),
            'extracted_record_id' => $id,
            'data' => json_encode([
                'title' => 'Constitutional Law in Practice',
                'formatted_text' => '# Constitutional Law in Practice\n\nAbstract: An exploration of modern jurisprudence.',
                'data_quality_flags' => [
                    'requires_human_review' => false,
                ],
            ]),
            'created_at' => now(),
        ]);

        $this->artisan('legal-analytics:populate')
            ->expectsOutput('Starting population of legal analytics database. Max limit: 1000 records.')
            ->expectsOutput('Successfully processed 1 records. Deleted 0 obsolete records.')
            ->assertExitCode(0);

        $analytic = LegalAnalytics::first();
        $this->assertNotNull($analytic);
        $this->assertEquals('Constitutional Law in Practice', $analytic->title);
        $this->assertEquals('journals', $analytic->target_type);
        $this->assertEquals('PER', $analytic->target_name);
        $this->assertEquals('https://www.saflii.org/za/journals/PER/2025/1.html', $analytic->source_url);
    }

    public function test_legal_analytics_skips_unscrubbed_records(): void
    {
        $targetId = $this->createTarget('ZACC', 'cases');
        $id = Str::uuid()->toString();

        DB::connection('pgsql_coeus')->table('extracted_records')->insert([
            'id' => $id,
            'target_id' => $targetId,
            'document_date' => '2026-06-18',
            'record_type' => 'saflii_courts',
            'data' => json_encode([
                'title' => 'Unscrubbed Raw Record',
            ]),
            'requires_human_review' => false,
            'status' => 'detailed',
        ]);

        // No scrubbed_records row exists

        $this->artisan('legal-analytics:populate')
            ->expectsOutput('Starting population of legal analytics database. Max limit: 1000 records.')
            ->expectsOutput('Successfully processed 0 records. Deleted 0 obsolete records.')
            ->assertExitCode(0);

        $this->assertEquals(0, LegalAnalytics::count());
    }

    public function test_legal_analytics_deletes_obsolete_records(): void
    {
        $targetId = $this->createTarget('ZACC', 'cases');
        $id = Str::uuid()->toString();

        LegalAnalytics::create([
            'extracted_record_id' => $id,
            'target_type' => 'cases',
            'target_name' => 'ZACC',
            'title' => 'Obsolete Record',
            'document_type' => 'saflii_courts',
            'document_date' => '2026-01-01',
            'court' => 'Constitutional Court',
        ]);

        $this->assertEquals(1, LegalAnalytics::count());

        $this->artisan('legal-analytics:populate')
            ->expectsOutput('Starting population of legal analytics database. Max limit: 1000 records.')
            ->expectsOutput('Successfully processed 0 records. Deleted 1 obsolete records.')
            ->assertExitCode(0);

        $this->assertEquals(0, LegalAnalytics::count());
    }

    public function test_legal_analytics_creates_backup_before_changes(): void
    {
        $targetId = $this->createTarget('ZACC', 'cases');
        $id1 = Str::uuid()->toString();
        $id2 = Str::uuid()->toString();

        // Setup first scrubbed record
        DB::connection('pgsql_coeus')->table('extracted_records')->insert([
            'id' => $id1,
            'target_id' => $targetId,
            'document_date' => '2026-01-01',
            'record_type' => 'saflii_courts',
            'data' => json_encode(['title' => 'Case 1']),
            'requires_human_review' => false,
            'status' => 'detailed',
        ]);
        DB::connection('pgsql_coeus')->table('scrubbed_records')->insert([
            'id' => Str::uuid()->toString(),
            'extracted_record_id' => $id1,
            'data' => json_encode(['title' => 'Case 1']),
            'created_at' => now(),
        ]);

        // First run
        $this->artisan('legal-analytics:populate')->assertExitCode(0);
        $this->assertEquals(1, LegalAnalytics::count());

        // Setup second scrubbed record
        DB::connection('pgsql_coeus')->table('extracted_records')->insert([
            'id' => $id2,
            'target_id' => $targetId,
            'document_date' => '2026-02-01',
            'record_type' => 'saflii_courts',
            'data' => json_encode(['title' => 'Case 2']),
            'requires_human_review' => false,
            'status' => 'detailed',
        ]);
        DB::connection('pgsql_coeus')->table('scrubbed_records')->insert([
            'id' => Str::uuid()->toString(),
            'extracted_record_id' => $id2,
            'data' => json_encode(['title' => 'Case 2']),
            'created_at' => now(),
        ]);

        // Second run
        $this->artisan('legal-analytics:populate')->assertExitCode(0);
        $this->assertEquals(2, LegalAnalytics::count());
        $this->assertEquals(1, DB::table('backup_legal_analytics')->count());
        $this->assertEquals('Case 1', DB::table('backup_legal_analytics')->first()->title);
    }

    public function test_skips_already_processed_records(): void
    {
        $targetId = $this->createTarget();
        $id = Str::uuid()->toString();

        $dataPayload = [
            'court' => 'CCMA',
            'title' => 'Gumede v Mastercraft, KN39790',
            'award_date' => '2000-07-01',
            'award_number' => 'KN39790',
        ];

        DB::connection('pgsql_coeus')->table('extracted_records')->insert([
            'id' => $id,
            'target_id' => $targetId,
            'document_date' => '2000-07-01',
            'record_type' => 'sabinet_ccma',
            'data' => json_encode($dataPayload),
            'requires_human_review' => false,
            'status' => 'detailed',
            'extracted_at' => now(),
            'processed_at' => now(), // Already processed
        ]);

        DB::connection('pgsql_coeus')->table('scrubbed_records')->insert([
            'id' => Str::uuid()->toString(),
            'extracted_record_id' => $id,
            'data' => json_encode($dataPayload),
            'created_at' => now(),
        ]);

        // Put in CcmaAnalytics manually so it's considered already locally processed
        CcmaAnalytics::create([
            'extracted_record_id' => $id,
            'title' => 'Gumede v Mastercraft, KN39790',
            'document_type' => 'CCMA Bargaining Council Awards',
            'award_date' => '2000-07-01',
            'court' => 'CCMA',
            'award_number' => 'KN39790',
            'employee' => 'Gumede',
            'employer' => 'Mastercraft',
            'court_location' => 'KwaZulu-Natal [Durban]',
            'reason_for_dismissal' => 'UNFAIR DISMISSAL',
        ]);

        $this->artisan('ccma-analytics:populate')
            ->expectsOutput('Starting population of analytics database. Max limit: 1000 records.')
            ->expectsOutput('Successfully processed 0 records. Deleted 0 obsolete records.')
            ->assertExitCode(0);

        // Verify ccma_analytics table has 1 record
        $this->assertEquals(1, CcmaAnalytics::count());
    }

    public function test_skips_records_without_cleaned_at(): void
    {
        $targetId = $this->createTarget();
        $id = Str::uuid()->toString();

        DB::connection('pgsql_coeus')->table('extracted_records')->insert([
            'id' => $id,
            'target_id' => $targetId,
            'document_date' => '2000-07-01',
            'record_type' => 'sabinet_ccma',
            'data' => json_encode([
                'court' => 'CCMA',
                'title' => 'Gumede v Mastercraft, KN39790',
                'award_date' => '2000-07-01',
                'award_number' => 'KN39790',
            ]),
            'requires_human_review' => false,
            'status' => 'detailed',
            'extracted_at' => now(),
            'processed_at' => null,
            'cleaned_at' => null,
        ]);

        // Since it's not cleaned, it is not in scrubbed_records table.

        $this->artisan('ccma-analytics:populate')
            ->expectsOutput('Starting population of analytics database. Max limit: 1000 records.')
            ->expectsOutput('Successfully processed 0 records. Deleted 0 obsolete records.')
            ->assertExitCode(0);

        // Verify ccma_analytics table remains empty
        $this->assertEquals(0, CcmaAnalytics::count());
    }

    public function test_deletes_local_records_not_in_extracted_records(): void
    {
        $targetId = $this->createTarget();
        $id = Str::uuid()->toString();

        $dataPayload = [
            'court' => 'CCMA',
            'title' => 'Gumede v Mastercraft, KN39790',
            'award_date' => '2000-07-01',
            'award_number' => 'KN39790',
        ];

        DB::connection('pgsql_coeus')->table('extracted_records')->insert([
            'id' => $id,
            'target_id' => $targetId,
            'document_date' => '2000-07-01',
            'record_type' => 'sabinet_ccma',
            'data' => json_encode($dataPayload),
            'requires_human_review' => false,
            'status' => 'detailed',
            'extracted_at' => now(),
            'processed_at' => null,
            'cleaned_at' => now(),
        ]);

        DB::connection('pgsql_coeus')->table('scrubbed_records')->insert([
            'id' => Str::uuid()->toString(),
            'extracted_record_id' => $id,
            'data' => json_encode($dataPayload),
            'created_at' => now(),
        ]);

        // First run: inserts the record locally
        $this->artisan('ccma-analytics:populate')
            ->expectsOutput('Starting population of analytics database. Max limit: 1000 records.')
            ->expectsOutput('Successfully processed 1 records. Deleted 0 obsolete records.')
            ->assertExitCode(0);

        $this->assertEquals(1, CcmaAnalytics::count());

        // Delete from coeus DB
        DB::connection('pgsql_coeus')->table('extracted_records')->where('id', $id)->delete();
        DB::connection('pgsql_coeus')->table('scrubbed_records')->where('extracted_record_id', $id)->delete();

        // Second run: should detect deletion in coeus and delete it locally
        $this->artisan('ccma-analytics:populate')
            ->expectsOutput('Starting population of analytics database. Max limit: 1000 records.')
            ->expectsOutput('Successfully processed 0 records. Deleted 1 obsolete records.')
            ->assertExitCode(0);

        $this->assertEquals(0, CcmaAnalytics::count());
    }

    public function test_creates_backup_before_changes(): void
    {
        $targetId = $this->createTarget();
        $id1 = Str::uuid()->toString();

        $dataPayload1 = [
            'court' => 'CCMA',
            'title' => 'Gumede v Mastercraft, KN39790',
            'award_date' => '2000-07-01',
            'award_number' => 'KN39790',
        ];

        DB::connection('pgsql_coeus')->table('extracted_records')->insert([
            'id' => $id1,
            'target_id' => $targetId,
            'document_date' => '2000-07-01',
            'record_type' => 'sabinet_ccma',
            'data' => json_encode($dataPayload1),
            'requires_human_review' => false,
            'status' => 'detailed',
            'extracted_at' => now(),
            'processed_at' => null,
            'cleaned_at' => now(),
        ]);

        DB::connection('pgsql_coeus')->table('scrubbed_records')->insert([
            'id' => Str::uuid()->toString(),
            'extracted_record_id' => $id1,
            'data' => json_encode($dataPayload1),
            'created_at' => now(),
        ]);

        // First run: inserts the first record
        $this->artisan('ccma-analytics:populate')
            ->expectsOutput('Starting population of analytics database. Max limit: 1000 records.')
            ->expectsOutput('Successfully processed 1 records. Deleted 0 obsolete records.')
            ->assertExitCode(0);

        $this->assertEquals(1, CcmaAnalytics::count());
        // Since changes were made, backup should contain the snapshot *before* the first changes (which was empty)
        $this->assertEquals(0, DB::table('backup_ccma_analytics')->count());

        // Insert second record
        $id2 = Str::uuid()->toString();
        $dataPayload2 = [
            'court' => 'CCMA',
            'title' => 'Naidoo v Mastercraft, KN39791',
            'award_date' => '2000-07-02',
            'award_number' => 'KN39791',
        ];

        DB::connection('pgsql_coeus')->table('extracted_records')->insert([
            'id' => $id2,
            'target_id' => $targetId,
            'document_date' => '2000-07-02',
            'record_type' => 'sabinet_ccma',
            'data' => json_encode($dataPayload2),
            'requires_human_review' => false,
            'status' => 'detailed',
            'extracted_at' => now(),
            'processed_at' => null,
            'cleaned_at' => now(),
        ]);

        DB::connection('pgsql_coeus')->table('scrubbed_records')->insert([
            'id' => Str::uuid()->toString(),
            'extracted_record_id' => $id2,
            'data' => json_encode($dataPayload2),
            'created_at' => now(),
        ]);

        // Second run: inserts the second record
        $this->artisan('ccma-analytics:populate')
            ->expectsOutput('Starting population of analytics database. Max limit: 1000 records.')
            ->expectsOutput('Successfully processed 1 records. Deleted 0 obsolete records.')
            ->assertExitCode(0);

        // Current analytics has 2 records
        $this->assertEquals(2, CcmaAnalytics::count());
        // Backup should contain the snapshot *before* this run (which was the 1 record from the first run)
        $this->assertEquals(1, DB::table('backup_ccma_analytics')->count());
        $this->assertEquals('Gumede v Mastercraft, KN39790', DB::table('backup_ccma_analytics')->first()->title);
    }

    public function test_user_can_download_dataset_in_both_formats(): void
    {
        Storage::fake('local');

        // Create the dataset files in local storage
        Storage::disk('local')->put('datasets/8ohm_saflii_dataset.csv', 'dummy csv');
        Storage::disk('local')->put('datasets/8ohm_saflii_dataset.json', 'dummy json');

        $user = User::factory()->create();

        // Create product once-off-dataset and associate order/item to authorize access
        $product = Product::factory()->create([
            'slug' => 'once-off-dataset',
            'name' => 'Once-off Dataset',
            'price' => 5000,
        ]);
        $order = Order::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address' => '123 Street',
            'city' => 'Johannesburg',
            'country' => 'South Africa',
            'phone' => '123456789',
            'total_amount' => 5000,
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 5000,
        ]);

        // Request CSV format
        $responseCsv = $this->actingAs($user)->get(route('downloads.dataset', ['dataset' => 'saflii', 'format' => 'csv']));
        $responseCsv->assertStatus(200);
        $responseCsv->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertEquals('dummy csv', $responseCsv->streamedContent());

        // Request JSON format
        $responseJson = $this->actingAs($user)->get(route('downloads.dataset', ['dataset' => 'saflii', 'format' => 'json']));
        $responseJson->assertStatus(200);
        $responseJson->assertHeader('Content-Type', 'application/json');
        $this->assertEquals('dummy json', $responseJson->streamedContent());
    }
}

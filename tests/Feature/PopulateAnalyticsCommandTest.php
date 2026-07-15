<?php

namespace Tests\Feature;

use App\Models\Analytics;
use App\Models\BackupAnalytics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

        // Create the entities, targets, and extracted_records tables in the test pgsql_coeus connection
        $db = DB::connection('pgsql_coeus');
        $db->statement('CREATE TABLE entities (id VARCHAR(36) PRIMARY KEY, name VARCHAR(255) UNIQUE, identifier VARCHAR(50), created_at TIMESTAMP)');
        $db->statement('CREATE TABLE targets (id VARCHAR(36) PRIMARY KEY, entity_id VARCHAR(36), target_name VARCHAR(255), location VARCHAR(255), created_at TIMESTAMP)');
        $db->statement('CREATE TABLE extracted_records (id VARCHAR(36) PRIMARY KEY, target_id VARCHAR(36), document_date DATE, record_type VARCHAR(100), data TEXT, requires_human_review BOOLEAN DEFAULT FALSE, review_reason TEXT, source_url TEXT, extracted_at TIMESTAMP, processed_at TIMESTAMP, cleaned_at TIMESTAMP)');
    }

    private function createTarget(): string
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
            'target_name' => 'Test Target '.$targetId,
            'location' => 'https://example.com',
            'created_at' => now(),
        ]);

        return $targetId;
    }

    public function test_populates_indexed_data_correctly(): void
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
                'detail_url' => 'https://discover.sabinet.co.za/document/1628547',
                'hearing_end' => '2000-05-30',
                'award_number' => 'KN39790',
                'date_modified' => '2019-10-29',
                'document_type' => 'CCMA Bargaining Council Awards',
                'hearing_start' => '2000-03-22',
                'index_scraped_at' => '2026-07-10T16:11:35.943615',
            ]),
            'requires_human_review' => false,
            'extracted_at' => now(),
            'processed_at' => null,
            'cleaned_at' => now(),
        ]);

        $this->artisan('analytics:populate')
            ->expectsOutput('Starting population of analytics database. Max limit: 1000 records.')
            ->expectsOutput('Successfully processed 1 records. Deleted 0 obsolete records.')
            ->assertExitCode(0);

        // Verify analytics table has the mapped record
        $analytic = Analytics::first();
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

        // Verify coeus record has processed_at set
        $record = DB::connection('pgsql_coeus')->table('extracted_records')->where('id', $id)->first();
        $this->assertNotNull($record->processed_at);
    }

    public function test_populates_detailed_data_correctly(): void
    {
        $targetId = $this->createTarget();
        $id = Str::uuid()->toString();

        DB::connection('pgsql_coeus')->table('extracted_records')->insert([
            'id' => $id,
            'target_id' => $targetId,
            'document_date' => '1996-11-01',
            'record_type' => 'sabinet_ccma',
            'data' => json_encode([
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
            ]),
            'requires_human_review' => false,
            'extracted_at' => now(),
            'processed_at' => null,
            'cleaned_at' => now(),
        ]);

        $this->artisan('analytics:populate')
            ->expectsOutput('Starting population of analytics database. Max limit: 1000 records.')
            ->expectsOutput('Successfully processed 1 records. Deleted 0 obsolete records.')
            ->assertExitCode(0);

        // Verify analytics table has the mapped record
        $analytic = Analytics::first();
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

        // Verify coeus record has processed_at set
        $record = DB::connection('pgsql_coeus')->table('extracted_records')->where('id', $id)->first();
        $this->assertNotNull($record->processed_at);
    }

    public function test_skips_already_processed_records(): void
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
            'extracted_at' => now(),
            'processed_at' => now(), // Already processed
        ]);

        $this->artisan('analytics:populate')
            ->expectsOutput('Starting population of analytics database. Max limit: 1000 records.')
            ->expectsOutput('Successfully processed 0 records. Deleted 0 obsolete records.')
            ->assertExitCode(0);

        // Verify analytics table remains empty
        $this->assertEquals(0, Analytics::count());
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
            'extracted_at' => now(),
            'processed_at' => null,
            'cleaned_at' => null,
        ]);

        $this->artisan('analytics:populate')
            ->expectsOutput('Starting population of analytics database. Max limit: 1000 records.')
            ->expectsOutput('Successfully processed 0 records. Deleted 0 obsolete records.')
            ->assertExitCode(0);

        // Verify analytics table remains empty
        $this->assertEquals(0, Analytics::count());
    }

    public function test_deletes_local_records_not_in_extracted_records(): void
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
            'extracted_at' => now(),
            'processed_at' => null,
            'cleaned_at' => now(),
        ]);

        // First run: inserts the record locally
        $this->artisan('analytics:populate')
            ->expectsOutput('Starting population of analytics database. Max limit: 1000 records.')
            ->expectsOutput('Successfully processed 1 records. Deleted 0 obsolete records.')
            ->assertExitCode(0);

        $this->assertEquals(1, Analytics::count());

        // Delete from coeus DB
        DB::connection('pgsql_coeus')->table('extracted_records')->where('id', $id)->delete();

        // Second run: should detect deletion in coeus and delete it locally
        $this->artisan('analytics:populate')
            ->expectsOutput('Starting population of analytics database. Max limit: 1000 records.')
            ->expectsOutput('Successfully processed 0 records. Deleted 1 obsolete records.')
            ->assertExitCode(0);

        $this->assertEquals(0, Analytics::count());
    }

    public function test_creates_backup_before_changes(): void
    {
        $targetId = $this->createTarget();
        $id1 = Str::uuid()->toString();

        DB::connection('pgsql_coeus')->table('extracted_records')->insert([
            'id' => $id1,
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
            'extracted_at' => now(),
            'processed_at' => null,
            'cleaned_at' => now(),
        ]);

        // First run: inserts the first record
        $this->artisan('analytics:populate')
            ->expectsOutput('Starting population of analytics database. Max limit: 1000 records.')
            ->expectsOutput('Successfully processed 1 records. Deleted 0 obsolete records.')
            ->assertExitCode(0);

        $this->assertEquals(1, Analytics::count());
        // Since changes were made, backup should contain the snapshot *before* the first changes (which was empty)
        $this->assertEquals(0, BackupAnalytics::count());

        // Insert second record
        $id2 = Str::uuid()->toString();
        DB::connection('pgsql_coeus')->table('extracted_records')->insert([
            'id' => $id2,
            'target_id' => $targetId,
            'document_date' => '2000-07-02',
            'record_type' => 'sabinet_ccma',
            'data' => json_encode([
                'court' => 'CCMA',
                'title' => 'Naidoo v Mastercraft, KN39791',
                'award_date' => '2000-07-02',
                'award_number' => 'KN39791',
            ]),
            'requires_human_review' => false,
            'extracted_at' => now(),
            'processed_at' => null,
            'cleaned_at' => now(),
        ]);

        // Second run: inserts the second record
        $this->artisan('analytics:populate')
            ->expectsOutput('Starting population of analytics database. Max limit: 1000 records.')
            ->expectsOutput('Successfully processed 1 records. Deleted 0 obsolete records.')
            ->assertExitCode(0);

        // Current analytics has 2 records
        $this->assertEquals(2, Analytics::count());
        // Backup should contain the snapshot *before* this run (which was the 1 record from the first run)
        $this->assertEquals(1, BackupAnalytics::count());
        $this->assertEquals('Gumede v Mastercraft, KN39790', BackupAnalytics::first()->title);
    }
}

<?php

namespace Tests;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    protected static bool $coeusSchemaBootstrapped = false;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->withoutMiddleware([
            ValidateCsrfToken::class,
            ThrottleRequests::class,
            ThrottleRequestsWithRedis::class,
        ]);
        $this->ensureCoeusSchema();
    }

    protected function ensureCoeusSchema(): void
    {
        if (static::$coeusSchemaBootstrapped) {
            return;
        }

        try {
            $connection = DB::connection('pgsql_coeus');
            $driver = $connection->getDriverName();
            if (! in_array($driver, ['pgsql', 'sqlite'], true)) {
                return;
            }

            if (! Schema::connection('pgsql_coeus')->hasTable('entities')) {
                Schema::connection('pgsql_coeus')->create('entities', function ($table) {
                    $table->uuid('id')->primary();
                    $table->string('name')->nullable();
                    $table->string('identifier')->nullable();
                    $table->timestamps();
                });
            }

            if (! Schema::connection('pgsql_coeus')->hasTable('targets')) {
                Schema::connection('pgsql_coeus')->create('targets', function ($table) {
                    $table->uuid('id')->primary();
                    $table->uuid('entity_id')->nullable();
                    $table->string('target_name')->nullable();
                    $table->string('name')->nullable();
                    $table->string('target_type')->nullable();
                    $table->string('location')->nullable();
                    $table->timestamps();
                });
            }

            if (! Schema::connection('pgsql_coeus')->hasTable('extracted_records')) {
                Schema::connection('pgsql_coeus')->create('extracted_records', function ($table) {
                    $table->uuid('id')->primary();
                    $table->uuid('target_id')->nullable();
                    $table->string('record_type')->nullable();
                    $table->text('source_url')->nullable();
                    $table->date('document_date')->nullable();
                    $table->json('data')->nullable();
                    $table->boolean('requires_human_review')->default(false);
                    $table->text('review_reason')->nullable();
                    $table->string('status')->nullable();
                    $table->timestamp('scraped_at')->nullable();
                    $table->timestamp('detailed_at')->nullable();
                    $table->timestamp('parsed_at')->nullable();
                    $table->timestamp('scrubbed_at')->nullable();
                    $table->timestamps();
                });
            } elseif (! Schema::connection('pgsql_coeus')->hasColumn('extracted_records', 'scrubbed_at')) {
                Schema::connection('pgsql_coeus')->table('extracted_records', function ($table) {
                    $table->timestamp('scrubbed_at')->nullable();
                });
            }

            if (! Schema::connection('pgsql_coeus')->hasTable('parsed_records')) {
                Schema::connection('pgsql_coeus')->create('parsed_records', function ($table) {
                    $table->uuid('id')->primary();
                    $table->uuid('extracted_record_id')->nullable();
                    $table->json('data')->nullable();
                    $table->timestamps();
                });
            }

            if (! Schema::connection('pgsql_coeus')->hasTable('scrubbed_records')) {
                Schema::connection('pgsql_coeus')->create('scrubbed_records', function ($table) {
                    $table->uuid('id')->primary();
                    $table->uuid('extracted_record_id')->nullable();
                    $table->json('data')->nullable();
                    $table->timestamps();
                });
            }

            if ($driver === 'pgsql') {
                $connection->unprepared("
                    CREATE OR REPLACE FUNCTION get_scrubbed_record_category(data jsonb)
                    RETURNS text LANGUAGE plpgsql IMMUTABLE PARALLEL SAFE AS \$function\$
                    DECLARE
                      rt text := COALESCE(data->'metadata'->>'record_type', data->>'record_type');
                      cat text := COALESCE(data->'metadata'->>'category', data->'extracted_data'->>'category', data->>'category');
                    BEGIN
                      IF cat IN ('gazettes', 'gaz') 
                         OR (data ? 'gazette_number' AND data ? 'jurisdiction')
                         OR (data ? 'gazette_type' AND data ? 'jurisdiction')
                         OR (data->>'title' ILIKE '%Gazette%' AND (data ? 'has_html_content' OR data->>'formatted_text' ILIKE '%no available HTML version%')) THEN
                        RETURN 'gazettes';
                      ELSIF data ? 'roll_type' OR data ? 'rows' OR cat IN ('court_rolls', 'other') THEN
                        RETURN 'court_rolls';
                      ELSIF cat IN ('journals', 'journal') 
                         OR data ? 'journal_name' 
                         OR (data ? 'formatted_text' AND NOT (data ? 'court' AND data ? 'parties')) THEN
                        RETURN 'journals';
                      ELSIF rt IN ('fsca_enforcement_records', 'fsca_regulatory_records', 'pa_insurance_records', 'popia_records')
                         OR (data->'extracted_data' ? 'administrative_penalty_amount')
                         OR (data->'extracted_data' ? 'prudential_standard_number')
                         OR (data->'extracted_data' ? 'contravention_findings') THEN
                        RETURN 'regulatory';
                      ELSIF rt IN ('fst_cases', 'fst_decisions')
                         OR (data->'extracted_data' ? 'decision_outcome') THEN
                        RETURN 'tribunal';
                      ELSIF rt IN ('nfo_cases', 'fais_ombud_cases', 'fais_determinations')
                         OR (data->'extracted_data' ? 'repudiation_grounds') THEN
                        RETURN 'ombud';
                      ELSE
                        RETURN 'cases';
                      END IF;
                    END;
                    \$function\$;
                ");
            }

            static::$coeusSchemaBootstrapped = true;
        } catch (\Throwable $e) {
            // Ignore if connection is not available in non-coeus tests
        }
    }
}

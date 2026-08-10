<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop old analytics tables if they exist
        Schema::dropIfExists('analytics');
        Schema::dropIfExists('backup_analytics');

        // Create ccma_analytics
        Schema::create('ccma_analytics', function (Blueprint $table) {
            $table->id();
            $table->string('extracted_record_id', 36)->nullable()->unique()->index();
            $table->text('title');
            $table->text('document_type');
            $table->date('award_date')->index();
            $table->text('court');
            $table->text('award_number');
            $table->date('hearing_start')->nullable();
            $table->date('hearing_end')->nullable();
            $table->date('date_modified')->nullable();
            $table->text('detail_url')->nullable();
            $table->text('detail_title')->nullable();
            $table->text('employee');
            $table->text('employer')->index();
            $table->text('forum')->nullable();
            $table->text('court_location')->index();
            $table->text('reason_for_dismissal');
            $table->text('preview_image_url')->nullable();
            $table->datetime('details_scraped_at')->nullable();
            $table->timestamps();
        });

        // Create backup_ccma_analytics
        Schema::create('backup_ccma_analytics', function (Blueprint $table) {
            $table->id();
            $table->string('extracted_record_id', 36)->nullable()->unique()->index();
            $table->text('title');
            $table->text('document_type');
            $table->date('award_date')->index();
            $table->text('court');
            $table->text('award_number');
            $table->date('hearing_start')->nullable();
            $table->date('hearing_end')->nullable();
            $table->date('date_modified')->nullable();
            $table->text('detail_url')->nullable();
            $table->text('detail_title')->nullable();
            $table->text('employee');
            $table->text('employer')->index();
            $table->text('forum')->nullable();
            $table->text('court_location')->index();
            $table->text('reason_for_dismissal');
            $table->text('preview_image_url')->nullable();
            $table->datetime('details_scraped_at')->nullable();
            $table->timestamps();
        });

        // Create legal_analytics
        Schema::create('legal_analytics', function (Blueprint $table) {
            $table->id();
            $table->string('extracted_record_id', 36)->nullable()->unique()->index();
            $table->string('target_type')->index();
            $table->string('target_name')->index();
            $table->text('title');
            $table->text('document_type');
            $table->date('document_date')->nullable()->index();
            $table->text('court')->nullable();
            $table->text('case_number')->nullable()->index();
            $table->text('source_url')->nullable();
            $table->jsonb('data')->nullable();
            $table->timestamps();
        });

        // Create backup_legal_analytics
        Schema::create('backup_legal_analytics', function (Blueprint $table) {
            $table->id();
            $table->string('extracted_record_id', 36)->nullable()->unique()->index();
            $table->string('target_type')->index();
            $table->string('target_name')->index();
            $table->text('title');
            $table->text('document_type');
            $table->date('document_date')->nullable()->index();
            $table->text('court')->nullable();
            $table->text('case_number')->nullable()->index();
            $table->text('source_url')->nullable();
            $table->jsonb('data')->nullable();
            $table->timestamps();
        });

        // Create target_vanities
        Schema::create('target_vanities', function (Blueprint $table) {
            $table->id();
            $table->string('target_name')->unique()->index();
            $table->text('vanity_name');
            $table->string('target_type');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ccma_analytics');
        Schema::dropIfExists('backup_ccma_analytics');
        Schema::dropIfExists('legal_analytics');
        Schema::dropIfExists('backup_legal_analytics');
        Schema::dropIfExists('target_vanities');
    }
};

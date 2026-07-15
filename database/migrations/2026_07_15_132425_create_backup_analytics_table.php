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
        Schema::table('analytics', function (Blueprint $table) {
            $table->string('extracted_record_id', 36)->nullable()->unique()->index();
        });

        Schema::create('backup_analytics', function (Blueprint $table) {
            $table->id();
            $table->string('extracted_record_id', 36)->nullable()->unique()->index();
            $table->string('title');
            $table->string('document_type');
            $table->date('award_date')->index();
            $table->string('court');
            $table->string('award_number');
            $table->date('hearing_start')->nullable();
            $table->date('hearing_end')->nullable();
            $table->date('date_modified')->nullable();
            $table->text('detail_url')->nullable();
            $table->string('detail_title')->nullable();
            $table->string('employee');
            $table->string('employer')->index();
            $table->string('forum')->nullable();
            $table->string('court_location')->index();
            $table->string('reason_for_dismissal');
            $table->text('preview_image_url')->nullable();
            $table->datetime('details_scraped_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backup_analytics');

        Schema::table('analytics', function (Blueprint $table) {
            $table->dropColumn('extracted_record_id');
        });
    }
};

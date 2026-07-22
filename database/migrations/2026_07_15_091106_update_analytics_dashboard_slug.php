<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('products')
            ->where('slug', 'analytics-dashboard')
            ->update(['slug' => 'pro-analytics']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('products')
            ->where('slug', 'pro-analytics')
            ->update(['slug' => 'analytics-dashboard']);
    }
};

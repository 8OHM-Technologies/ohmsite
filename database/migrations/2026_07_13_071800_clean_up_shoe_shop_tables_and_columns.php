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
        // 1. Drop foreign key and column from products table
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['brand_id']);
            $table->dropColumn(['brand_id', 'colors', 'sizes']);
        });

        // 2. Drop brand_product pivot table
        Schema::dropIfExists('brand_product');

        // 3. Drop brands table
        Schema::dropIfExists('brands');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse migration needed for shoe-shop cleanups
    }
};

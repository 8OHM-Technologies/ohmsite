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
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_active_at')->nullable();
            $table->string('avatar')->nullable();
            $table->enum('status', ['active', 'inactive', 'banned'])->default('active');
            $table->decimal('total_spent', 12, 2)->default(0);
            $table->integer('orders_count')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['last_active_at', 'avatar', 'status', 'total_spent', 'orders_count']);
        });
    }
};

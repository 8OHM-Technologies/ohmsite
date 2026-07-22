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
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('key')->unique()->index();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('api_calls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('api_key_id')->nullable()->constrained('api_keys')->nullOnDelete();
            $table->string('endpoint');
            $table->string('ip_address')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->integer('api_limit_override')->nullable()->default(null);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('api_limit_override');
        });

        Schema::dropIfExists('api_calls');
        Schema::dropIfExists('api_keys');
    }
};

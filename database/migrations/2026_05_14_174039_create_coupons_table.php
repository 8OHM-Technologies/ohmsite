<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $header) {
            $header->id();
            $header->string('code')->unique();
            $header->enum('type', ['percentage', 'fixed']);
            $header->decimal('value', 10, 2);
            $header->decimal('min_order', 10, 2)->default(0);
            $header->integer('max_uses')->nullable();
            $header->integer('used_count')->default(0);
            $header->timestamp('starts_at')->nullable();
            $header->timestamp('expires_at')->nullable();
            $header->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};

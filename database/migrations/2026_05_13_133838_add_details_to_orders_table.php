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
        Schema::table('orders', function (Blueprint $table) {
            $table->text('shipping_address')->nullable()->after('total_amount');
            $table->string('phone')->nullable()->after('shipping_address');
            $table->string('payment_status')->default('pending')->after('status');
            $table->string('tracking_number')->nullable()->after('payment_status');
            $table->text('admin_notes')->nullable()->after('tracking_number');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['shipping_address', 'phone', 'payment_status', 'tracking_number', 'admin_notes']);
        });
    }
};

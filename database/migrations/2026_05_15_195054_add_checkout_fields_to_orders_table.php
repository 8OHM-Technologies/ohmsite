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
            $table->foreignId('user_id')->nullable()->change();
            $table->string('email')->nullable()->after('user_id');
            $table->string('first_name')->nullable()->after('email');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('address')->nullable()->after('last_name');
            $table->string('apartment')->nullable()->after('address');
            $table->string('city')->nullable()->after('apartment');
            $table->string('postal_code')->nullable()->after('city');
            $table->string('country')->default('Kosovo')->after('postal_code');
            $table->decimal('shipping_fee', 8, 2)->default(0)->after('total_amount');
            $table->string('payment_method')->default('cash')->after('payment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'email',
                'first_name',
                'last_name',
                'address',
                'apartment',
                'city',
                'postal_code',
                'country',
                'shipping_fee',
                'payment_method'
            ]);
        });
    }
};

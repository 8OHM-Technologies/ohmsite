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
            $table->string('paystack_customer_code')->nullable()->after('email');
            $table->string('subscription_code')->nullable()->after('paystack_customer_code');
            $table->string('subscription_status')->nullable()->after('subscription_code');
            $table->timestamp('subscribed_at')->nullable()->after('subscription_status');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_reference')->nullable()->after('payment_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'paystack_customer_code',
                'subscription_code',
                'subscription_status',
                'subscribed_at',
            ]);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('payment_reference');
        });
    }
};

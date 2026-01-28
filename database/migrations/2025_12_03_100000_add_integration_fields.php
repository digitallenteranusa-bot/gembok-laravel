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
        // Add PPPoE fields to customers
        Schema::table('customers', function (Blueprint $table) {
            $table->string('pppoe_username')->nullable()->after('username');
            $table->string('pppoe_password')->nullable()->after('pppoe_username');
            $table->string('static_ip')->nullable()->after('pppoe_password');
            $table->string('mac_address')->nullable()->after('static_ip');
        });

        // Add payment gateway fields to invoices
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('payment_gateway')->nullable()->after('status');
            $table->string('payment_order_id')->nullable()->after('payment_gateway');
            $table->string('transaction_id')->nullable()->after('payment_order_id');
            $table->string('payment_method')->nullable()->after('transaction_id');
            $table->foreignId('collected_by')->nullable()->after('payment_method')->constrained('users')->nullOnDelete();
        });

        // Add mikrotik_profile to packages
        Schema::table('packages', function (Blueprint $table) {
            $table->string('mikrotik_profile')->nullable()->after('speed');
            $table->string('hotspot_profile')->nullable()->after('mikrotik_profile');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['pppoe_username', 'pppoe_password', 'static_ip', 'mac_address']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['collected_by']);
            $table->dropColumn(['payment_gateway', 'payment_order_id', 'transaction_id', 'payment_method', 'collected_by']);
        });

        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn(['mikrotik_profile', 'hotspot_profile']);
        });
    }
};

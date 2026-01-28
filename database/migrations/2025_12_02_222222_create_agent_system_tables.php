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
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('name');
            $table->string('phone')->unique();
            $table->string('email')->nullable();
            $table->string('password');
            $table->text('address')->nullable();
            $table->string('status')->default('active');
            $table->decimal('commission_rate', 5, 2)->default(5.00);
            $table->timestamps();
        });

        Schema::create('agent_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->decimal('balance', 15, 2)->default(0.00);
            $table->timestamp('last_updated')->useCurrent();
        });

        Schema::create('agent_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->string('transaction_type');
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->string('reference_id')->nullable();
            $table->string('status')->default('completed');
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('agent_voucher_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->string('voucher_code')->unique();
            $table->string('package_id');
            $table->string('package_name');
            $table->string('customer_phone')->nullable();
            $table->string('customer_name')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('commission', 10, 2)->default(0.00);
            $table->string('status')->default('active');
            $table->timestamp('sold_at')->useCurrent();
            $table->timestamp('used_at')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('agent_price', 10, 2)->default(0.00);
            $table->decimal('commission_amount', 10, 2)->default(0.00);
        });

        Schema::create('agent_balance_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('status')->default('pending');
            $table->text('admin_notes')->nullable();
            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('processed_by')->nullable();
        });

        Schema::create('agent_monthly_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->decimal('payment_amount', 15, 2);
            $table->decimal('commission_amount', 15, 2)->default(0.00);
            $table->string('payment_method')->default('cash');
            $table->text('notes')->nullable();
            $table->string('status')->default('completed');
            $table->timestamp('paid_at')->useCurrent();
        });

        Schema::create('agent_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('payment_method')->default('cash');
            $table->text('notes')->nullable();
            $table->string('status')->default('completed');
            $table->timestamp('paid_at')->useCurrent();
        });

        Schema::create('agent_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->string('notification_type');
            $table->string('title');
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_system_tables');
    }
};

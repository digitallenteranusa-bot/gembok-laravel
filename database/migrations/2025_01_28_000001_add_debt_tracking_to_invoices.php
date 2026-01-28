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
        // Add installment and debt tracking fields to invoices
        Schema::table('invoices', function (Blueprint $table) {
            $table->integer('paid_amount')->default(0)->after('tax_amount'); // Amount already paid (for partial payments)
            $table->integer('remaining_amount')->default(0)->after('paid_amount'); // Remaining balance
            $table->boolean('is_installment')->default(false)->after('invoice_type'); // Is this an installment invoice
            $table->integer('installment_number')->nullable()->after('is_installment'); // Which installment (1, 2, 3...)
            $table->integer('total_installments')->nullable()->after('installment_number'); // Total number of installments
            $table->unsignedBigInteger('parent_invoice_id')->nullable()->after('total_installments'); // Parent invoice for installments
            $table->foreign('parent_invoice_id')->references('id')->on('invoices')->onDelete('set null');
        });

        // Add debt tracking fields to customers
        Schema::table('customers', function (Blueprint $table) {
            $table->integer('total_debt')->default(0)->after('status'); // Total current debt
            $table->integer('unpaid_invoices_count')->default(0)->after('total_debt'); // Count of unpaid invoices
            $table->boolean('has_installment_plan')->default(false)->after('unpaid_invoices_count'); // Has active installment
            $table->timestamp('last_payment_date')->nullable()->after('has_installment_plan'); // Last payment date
            $table->timestamp('isolated_at')->nullable()->after('last_payment_date'); // When customer was isolated
            $table->string('isolation_reason')->nullable()->after('isolated_at'); // Reason for isolation
        });

        // Create payment history table for detailed tracking
        Schema::create('payment_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('invoice_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('payment_id')->nullable()->constrained()->onDelete('set null');
            $table->integer('amount');
            $table->string('type'); // payment, adjustment, refund, write_off
            $table->integer('balance_before');
            $table->integer('balance_after');
            $table->string('payment_method')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        // Create installment plans table
        Schema::create('installment_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade'); // Original invoice
            $table->integer('total_amount'); // Total debt amount
            $table->integer('installment_amount'); // Amount per installment
            $table->integer('number_of_installments'); // How many installments
            $table->integer('paid_installments')->default(0); // How many paid
            $table->integer('remaining_amount'); // Remaining to pay
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status')->default('active'); // active, completed, cancelled, defaulted
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('installment_plans');
        Schema::dropIfExists('payment_histories');

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'total_debt',
                'unpaid_invoices_count',
                'has_installment_plan',
                'last_payment_date',
                'isolated_at',
                'isolation_reason',
            ]);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['parent_invoice_id']);
            $table->dropColumn([
                'paid_amount',
                'remaining_amount',
                'is_installment',
                'installment_number',
                'total_installments',
                'parent_invoice_id',
            ]);
        });
    }
};

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
        Schema::create('monthly_summary', function (Blueprint $table) {
            $table->id();
            $table->integer('year');
            $table->integer('month');
            $table->integer('total_customers')->default(0);
            $table->integer('active_customers')->default(0);
            $table->integer('monthly_invoices')->default(0);
            $table->integer('voucher_invoices')->default(0);
            $table->integer('paid_monthly_invoices')->default(0);
            $table->integer('paid_voucher_invoices')->default(0);
            $table->integer('unpaid_monthly_invoices')->default(0);
            $table->integer('unpaid_voucher_invoices')->default(0);
            $table->decimal('monthly_revenue', 15, 2)->default(0);
            $table->decimal('voucher_revenue', 15, 2)->default(0);
            $table->decimal('monthly_unpaid', 15, 2)->default(0);
            $table->decimal('voucher_unpaid', 15, 2)->default(0);
            $table->decimal('total_revenue', 15, 2)->default(0);
            $table->decimal('total_unpaid', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['year', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_summary');
    }
};

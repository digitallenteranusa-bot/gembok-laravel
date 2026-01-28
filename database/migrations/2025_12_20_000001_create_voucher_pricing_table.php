<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voucher_pricing', function (Blueprint $table) {
            $table->id();
            $table->string('package_name');
            $table->decimal('customer_price', 12, 2)->default(0);
            $table->decimal('agent_price', 12, 2)->default(0);
            $table->decimal('commission_amount', 12, 2)->default(0);
            $table->integer('duration')->default(1); // in hours
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_pricing');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            
            // Customer Info
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('customer_email')->nullable();
            $table->text('customer_address');
            $table->string('customer_nik')->nullable();
            
            // Location
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            
            // Package
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->string('connection_type')->default('pppoe'); // pppoe, hotspot
            
            // Pricing
            $table->integer('package_price');
            $table->integer('installation_fee')->default(0);
            $table->integer('discount')->default(0);
            $table->integer('total_amount');
            
            // Payment
            $table->string('payment_method')->nullable(); // midtrans, manual, cod
            $table->string('payment_status')->default('pending'); // pending, paid, failed, expired
            $table->string('payment_transaction_id')->nullable();
            $table->string('payment_url')->nullable();
            $table->timestamp('paid_at')->nullable();
            
            // Order Status
            $table->string('status')->default('pending'); // pending, confirmed, scheduled, installing, completed, cancelled
            $table->date('installation_date')->nullable();
            $table->string('installation_time')->nullable();
            $table->foreignId('technician_id')->nullable()->constrained('technicians')->nullOnDelete();
            
            // Notes
            $table->text('customer_notes')->nullable();
            $table->text('admin_notes')->nullable();
            
            // Converted to customer
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

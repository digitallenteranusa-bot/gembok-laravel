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
        Schema::create('odps', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->unique();
            $table->foreignId('parent_odp_id')->nullable()->constrained('odps')->nullOnDelete();
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->text('address')->nullable();
            $table->integer('capacity')->default(64);
            $table->integer('used_ports')->default(0);
            $table->string('status')->default('active');
            $table->date('installation_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('cable_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('odp_id')->constrained('odps')->cascadeOnDelete();
            $table->decimal('cable_length', 8, 2)->nullable();
            $table->string('cable_type')->default('Fiber Optic');
            $table->date('installation_date')->nullable();
            $table->string('status')->default('connected');
            $table->integer('port_number')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('network_segments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('start_odp_id')->constrained('odps')->cascadeOnDelete();
            $table->foreignId('end_odp_id')->nullable()->constrained('odps')->cascadeOnDelete();
            $table->string('segment_type')->default('Backbone');
            $table->decimal('cable_length', 10, 2)->nullable();
            $table->string('status')->default('active');
            $table->date('installation_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('cable_maintenance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cable_route_id')->nullable()->constrained('cable_routes')->cascadeOnDelete();
            $table->foreignId('network_segment_id')->nullable()->constrained('network_segments')->cascadeOnDelete();
            $table->string('maintenance_type');
            $table->text('description');
            $table->foreignId('performed_by')->nullable()->constrained('technicians')->nullOnDelete();
            $table->date('maintenance_date');
            $table->decimal('duration_hours', 4, 2)->nullable();
            $table->decimal('cost', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('odp_system_tables');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Hotspot Profiles
        Schema::create('hotspot_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('mikrotik_id')->nullable()->index();
            $table->string('name')->unique();
            $table->string('rate_limit')->nullable();
            $table->integer('upload_speed')->default(0)->comment('Mbps');
            $table->integer('download_speed')->default(0)->comment('Mbps');
            $table->integer('shared_users')->default(1);
            $table->string('session_timeout')->nullable();
            $table->string('idle_timeout')->nullable();
            $table->string('keepalive_timeout')->nullable();
            $table->string('address_pool')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('agent_price', 12, 2)->default(0);
            $table->string('validity')->nullable()->comment('e.g., 1h, 1d, 7d, 30d');
            $table->boolean('is_active')->default(true);
            $table->boolean('synced')->default(false);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });

        // Hotspot Vouchers
        Schema::create('hotspot_vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('mikrotik_id')->nullable()->index();
            $table->foreignId('profile_id')->nullable()->constrained('hotspot_profiles')->nullOnDelete();
            $table->string('username')->unique();
            $table->string('password');
            $table->string('profile_name')->nullable();
            $table->string('limit_uptime')->nullable();
            $table->bigInteger('limit_bytes_total')->nullable();
            $table->bigInteger('limit_bytes_in')->nullable();
            $table->bigInteger('limit_bytes_out')->nullable();
            $table->string('server')->default('all');
            $table->string('mac_address')->nullable();
            $table->text('comment')->nullable();
            $table->enum('status', ['unused', 'used', 'expired', 'disabled'])->default('unused');
            $table->timestamp('used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('used_by_mac')->nullable();
            $table->string('used_by_ip')->nullable();
            $table->foreignId('agent_id')->nullable()->constrained('agents')->nullOnDelete();
            $table->foreignId('sold_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sold_at')->nullable();
            $table->boolean('synced')->default(false);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });

        // Sync Logs
        Schema::create('hotspot_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['profile', 'voucher']);
            $table->enum('direction', ['pull', 'push', 'full']);
            $table->enum('status', ['success', 'failed', 'partial']);
            $table->integer('total_items')->default(0);
            $table->integer('created')->default(0);
            $table->integer('updated')->default(0);
            $table->integer('deleted')->default(0);
            $table->integer('failed')->default(0);
            $table->text('error_message')->nullable();
            $table->json('details')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotspot_sync_logs');
        Schema::dropIfExists('hotspot_vouchers');
        Schema::dropIfExists('hotspot_profiles');
    }
};

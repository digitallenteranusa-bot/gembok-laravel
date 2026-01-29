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
        Schema::create('mikrotik_routers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('identity')->nullable();
            $table->string('host');
            $table->integer('port')->default(8728);
            $table->string('username');
            $table->text('password');
            $table->boolean('use_ssl')->default(false);
            $table->boolean('enabled')->default(true);
            $table->boolean('is_default')->default(false);
            $table->string('location')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('last_connected_at')->nullable();
            $table->boolean('last_connection_success')->nullable();
            $table->string('last_connection_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mikrotik_routers');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            if (!Schema::hasColumn('agents', 'balance')) {
                $table->decimal('balance', 15, 2)->default(0)->after('status');
            }
            if (!Schema::hasColumn('agents', 'commission_rate')) {
                $table->decimal('commission_rate', 5, 2)->default(10)->after('balance');
            }
        });
    }

    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropColumn(['balance', 'commission_rate']);
        });
    }
};

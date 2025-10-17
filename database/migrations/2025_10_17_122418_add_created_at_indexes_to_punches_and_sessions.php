<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('dg_punches', function (Blueprint $table) {
            $table->index('created_at', 'dg_punches_created_at_idx');
        });

        Schema::table('dg_work_sessions', function (Blueprint $table) {
            $table->index('created_at', 'dg_work_sessions_created_at_idx');
        });
    }

    public function down(): void {
        Schema::table('dg_punches', function (Blueprint $table) {
            $table->dropIndex('dg_punches_created_at_idx');
        });

        Schema::table('dg_work_sessions', function (Blueprint $table) {
            $table->dropIndex('dg_work_sessions_created_at_idx');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up(): void
    {
        // Migliora le query sui report (filtri per sito/data e anomalie)
        DB::statement('CREATE INDEX IF NOT EXISTS dg_work_sessions_resolved_site_date_idx ON dg_work_sessions (resolved_site_id, session_date)');
        DB::statement('CREATE INDEX IF NOT EXISTS dg_work_sessions_date_resolved_site_idx ON dg_work_sessions (session_date, resolved_site_id)');

        Schema::table('dg_anomalies', function (Blueprint $table) {
            $table->index(['session_id', 'date'], 'dg_anomalies_session_date_idx');
            $table->index(['user_id', 'date'], 'dg_anomalies_user_date_idx');
            $table->index(['type', 'date'], 'dg_anomalies_type_date_idx');
        });
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS dg_work_sessions_resolved_site_date_idx');
        DB::statement('DROP INDEX IF EXISTS dg_work_sessions_date_resolved_site_idx');

        Schema::table('dg_anomalies', function (Blueprint $table) {
            $table->dropIndex('dg_anomalies_session_date_idx');
            $table->dropIndex('dg_anomalies_user_date_idx');
            $table->dropIndex('dg_anomalies_type_date_idx');
        });
    }
};

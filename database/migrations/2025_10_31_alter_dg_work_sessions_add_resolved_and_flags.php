<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dg_work_sessions', function (Blueprint $t) {
            // Colonne aggiuntive (idempotenti)
            if (!Schema::hasColumn('dg_work_sessions', 'resolved_site_id')) {
                $t->foreignId('resolved_site_id')
                    ->nullable()
                    ->after('site_id')
                    ->constrained('dg_sites')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('dg_work_sessions', 'overtime_minutes')) {
                $t->unsignedInteger('overtime_minutes')
                    ->default(0)
                    ->after('worked_minutes');
            }

            if (!Schema::hasColumn('dg_work_sessions', 'anomaly_flags')) {
                // Postgres: jsonb; se mai dovessi migrare MySQL, cambia in ->json()
                $t->jsonb('anomaly_flags')
                    ->nullable()
                    ->after('overtime_minutes');
            }
        });

        // Indici: in Postgres usiamo SQL grezzo per IF NOT EXISTS
        // Nomi dichiarati esplicitamente, così non li perdi tra un refactor e l'altro
        DB::statement('CREATE INDEX IF NOT EXISTS dg_work_sessions_user_date_idx ON dg_work_sessions (user_id, session_date)');
        DB::statement('CREATE INDEX IF NOT EXISTS dg_work_sessions_site_date_idx ON dg_work_sessions (site_id, session_date)');
    }

    public function down(): void
    {
        // Droppa indici in modo sicuro (non andare di array colonne in PG)
        DB::statement('DROP INDEX IF EXISTS dg_work_sessions_user_date_idx');
        DB::statement('DROP INDEX IF EXISTS dg_work_sessions_site_date_idx');

        Schema::table('dg_work_sessions', function (Blueprint $t) {
            // Foreign key + colonna
            if (Schema::hasColumn('dg_work_sessions', 'resolved_site_id')) {
                // Questo rimuove sia la FK che la colonna
                $t->dropConstrainedForeignId('resolved_site_id');
            }

            if (Schema::hasColumn('dg_work_sessions', 'overtime_minutes')) {
                $t->dropColumn('overtime_minutes');
            }

            if (Schema::hasColumn('dg_work_sessions', 'anomaly_flags')) {
                $t->dropColumn('anomaly_flags');
            }
        });
    }
};

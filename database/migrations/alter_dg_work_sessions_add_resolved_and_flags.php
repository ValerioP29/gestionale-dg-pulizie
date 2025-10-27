<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dg_work_sessions', function (Blueprint $t) {
            // Aggiungi solo se non esistono già
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
                $t->jsonb('anomaly_flags')
                    ->nullable()
                    ->after('overtime_minutes');
            }

            // Indici aggiuntivi
            try {
                $t->index(['user_id', 'session_date'], 'dg_work_sessions_user_date_index');
                $t->index(['site_id', 'session_date'], 'dg_work_sessions_site_date_index');
            } catch (\Throwable $e) {
                // Se già esistono, ignora
            }
        });
    }

    public function down(): void
    {
        Schema::table('dg_work_sessions', function (Blueprint $t) {
            // Rimuovi solo se esistono
            if (Schema::hasColumn('dg_work_sessions', 'resolved_site_id')) {
                $t->dropConstrainedForeignId('resolved_site_id');
            }
            if (Schema::hasColumn('dg_work_sessions', 'overtime_minutes')) {
                $t->dropColumn('overtime_minutes');
            }
            if (Schema::hasColumn('dg_work_sessions', 'anomaly_flags')) {
                $t->dropColumn('anomaly_flags');
            }

            $t->dropIndex(['user_id', 'session_date']);
            $t->dropIndex(['site_id', 'session_date']);
        });
    }
};

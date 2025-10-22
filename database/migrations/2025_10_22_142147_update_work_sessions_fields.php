<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('dg_work_sessions', function (Blueprint $table) {
            // worked_minutes
            if (!Schema::hasColumn('dg_work_sessions', 'worked_minutes')) {
                $table->integer('worked_minutes')->nullable()->default(0);
            } else {
                $table->integer('worked_minutes')->nullable()->default(0)->change();
            }

            // status
            if (!Schema::hasColumn('dg_work_sessions', 'status')) {
                $table->string('status', 50)->default('complete')->nullable()->index();
            } else {
                $table->string('status', 50)->default('complete')->nullable()->change();
            }

            // source
            if (!Schema::hasColumn('dg_work_sessions', 'source')) {
                $table->string('source', 20)->default('auto'); // auto | manual
            }
        });

        // PostgreSQL CHECK constraint per status
        DB::statement("ALTER TABLE dg_work_sessions DROP CONSTRAINT IF EXISTS dg_work_sessions_status_check;");
        DB::statement("ALTER TABLE dg_work_sessions ADD CONSTRAINT dg_work_sessions_status_check CHECK (status IN ('complete', 'incomplete', 'invalid'));");
    }

    public function down(): void
    {
        Schema::table('dg_work_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('dg_work_sessions', 'source')) {
                $table->dropColumn('source');
            }
            if (Schema::hasColumn('dg_work_sessions', 'worked_minutes')) {
                $table->dropColumn('worked_minutes');
            }
            if (Schema::hasColumn('dg_work_sessions', 'status')) {
                $table->dropColumn('status');
            }
        });

        // Rimuovi il constraint se esiste
        DB::statement("ALTER TABLE dg_work_sessions DROP CONSTRAINT IF EXISTS dg_work_sessions_status_check;");
    }
};

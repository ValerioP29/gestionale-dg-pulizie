<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('dg_work_sessions', 'approval_status')) {
            return;
        }

        DB::statement("ALTER TABLE dg_work_sessions ALTER COLUMN approval_status TYPE VARCHAR(20) USING approval_status::text");
        DB::statement("ALTER TABLE dg_work_sessions ALTER COLUMN approval_status SET DEFAULT 'pending'");
        DB::statement("UPDATE dg_work_sessions SET approval_status = 'pending' WHERE approval_status IS NULL");

        DB::statement("ALTER TABLE dg_work_sessions DROP CONSTRAINT IF EXISTS dg_work_sessions_approval_status_check;");
        DB::statement("ALTER TABLE dg_work_sessions ADD CONSTRAINT dg_work_sessions_approval_status_check CHECK (approval_status IN ('pending','in_review','approved','rejected'));");
    }

    public function down(): void
    {
        if (! Schema::hasColumn('dg_work_sessions', 'approval_status')) {
            return;
        }

        DB::statement("ALTER TABLE dg_work_sessions DROP CONSTRAINT IF EXISTS dg_work_sessions_approval_status_check;");
        DB::statement("ALTER TABLE dg_work_sessions ADD CONSTRAINT dg_work_sessions_approval_status_check CHECK (approval_status IN ('pending','approved','rejected'));");
    }
};

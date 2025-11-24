<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement('CREATE INDEX IF NOT EXISTS dg_work_sessions_date_user_idx ON dg_work_sessions (session_date, user_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS dg_work_sessions_user_date_idx ON dg_work_sessions (user_id, session_date)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS dg_work_sessions_date_user_idx');
        DB::statement('DROP INDEX IF EXISTS dg_work_sessions_user_date_idx');
    }
};

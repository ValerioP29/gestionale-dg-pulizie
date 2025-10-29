<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        DB::statement("
            CREATE OR REPLACE VIEW payroll_export_v AS
            SELECT
                u.id AS user_id,
                u.payroll_code AS user_payroll_code,
                u.first_name,
                u.last_name,
                s.payroll_site_code,
                c.payroll_client_code,
                rc.period_start,
                rc.period_end,
                rc.worked_hours,
                rc.overtime_minutes,
                rc.late_entries,
                rc.early_exits,
                rc.days_present,
                rc.days_absent,
                rc.is_final
            FROM dg_reports_cache rc
            JOIN users u ON u.id = rc.user_id
            LEFT JOIN dg_sites s ON s.id = rc.site_id
            LEFT JOIN dg_clients c ON c.id = s.client_id
        ");
    }

    public function down(): void {
        DB::statement("DROP VIEW IF EXISTS payroll_export_v");
    }
};

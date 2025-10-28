<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('dg_reports_cache', function (Blueprint $t) {

            if (!Schema::hasColumn('dg_reports_cache', 'is_final')) {
                $t->boolean('is_final')->default(false)->after('generated_at');
            }

            if (!Schema::hasColumn('dg_reports_cache', 'overtime_minutes')) {
                $t->integer('overtime_minutes')->default(0)->after('early_exits');
            }

            if (!Schema::hasColumn('dg_reports_cache', 'anomaly_flags')) {
                $t->jsonb('anomaly_flags')->nullable()->after('overtime_minutes');
            }

            if (!Schema::hasColumn('dg_reports_cache', 'resolved_site_id')) {
                $t->foreignId('resolved_site_id')
                    ->nullable()
                    ->after('site_id')
                    ->constrained('dg_sites')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('dg_reports_cache', function (Blueprint $t) {
            if (Schema::hasColumn('dg_reports_cache', 'resolved_site_id')) {
                $t->dropConstrainedForeignId('resolved_site_id');
            }
            if (Schema::hasColumn('dg_reports_cache', 'anomaly_flags')) {
                $t->dropColumn('anomaly_flags');
            }
            if (Schema::hasColumn('dg_reports_cache', 'overtime_minutes')) {
                $t->dropColumn('overtime_minutes');
            }
            if (Schema::hasColumn('dg_reports_cache', 'is_final')) {
                $t->dropColumn('is_final');
            }
        });
    }
};

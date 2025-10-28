<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('dg_work_sessions', function (Blueprint $t) {
            if (!Schema::hasColumn('dg_work_sessions', 'approval_status')) {
                $t->enum('approval_status', ['pending','approved','rejected'])
                  ->default('pending')->index();
            }
            if (!Schema::hasColumn('dg_work_sessions', 'approved_by')) {
                $t->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('dg_work_sessions', 'approved_at')) {
                $t->timestamp('approved_at')->nullable()->index();
            }
            if (!Schema::hasColumn('dg_work_sessions', 'extra_minutes')) {
                $t->integer('extra_minutes')->default(0);
            }
            if (!Schema::hasColumn('dg_work_sessions', 'extra_reason')) {
                $t->string('extra_reason', 255)->nullable();
            }
            // override metadata: usiamo già resolved_site_id come “finale”
            if (!Schema::hasColumn('dg_work_sessions', 'override_set_by')) {
                $t->foreignId('override_set_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('dg_work_sessions', 'override_reason')) {
                $t->string('override_reason', 255)->nullable();
            }
        });
    }

    public function down(): void {
        Schema::table('dg_work_sessions', function (Blueprint $t) {
            if (Schema::hasColumn('dg_work_sessions', 'approval_status')) {
                $t->dropColumn('approval_status');
            }
            if (Schema::hasColumn('dg_work_sessions', 'approved_by')) {
                $t->dropConstrainedForeignId('approved_by');
            }
            if (Schema::hasColumn('dg_work_sessions', 'approved_at')) {
                $t->dropColumn('approved_at');
            }
            if (Schema::hasColumn('dg_work_sessions', 'extra_minutes')) {
                $t->dropColumn('extra_minutes');
            }
            if (Schema::hasColumn('dg_work_sessions', 'extra_reason')) {
                $t->dropColumn('extra_reason');
            }
            if (Schema::hasColumn('dg_work_sessions', 'override_set_by')) {
                $t->dropConstrainedForeignId('override_set_by');
            }
            if (Schema::hasColumn('dg_work_sessions', 'override_reason')) {
                $t->dropColumn('override_reason');
            }
        });
    }
};

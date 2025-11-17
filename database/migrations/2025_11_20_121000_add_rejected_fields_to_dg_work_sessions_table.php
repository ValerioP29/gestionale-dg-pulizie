<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('dg_work_sessions', function (Blueprint $table) {
            if (! Schema::hasColumn('dg_work_sessions', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('approved_at');
            }

            if (! Schema::hasColumn('dg_work_sessions', 'rejected_by')) {
                $table->foreignId('rejected_by')->nullable()->after('rejected_at')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('dg_work_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('dg_work_sessions', 'rejected_by')) {
                $table->dropConstrainedForeignId('rejected_by');
            }

            if (Schema::hasColumn('dg_work_sessions', 'rejected_at')) {
                $table->dropColumn('rejected_at');
            }
        });
    }
};

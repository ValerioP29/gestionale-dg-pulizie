<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('dg_user_justifications', function (Blueprint $t) {
            if (!Schema::hasColumn('dg_user_justifications', 'status')) {
                $t->enum('status', ['open','approved','rejected'])->default('open')->index();
            }
            if (!Schema::hasColumn('dg_user_justifications', 'reviewed_by')) {
                $t->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('dg_user_justifications', 'reviewed_at')) {
                $t->timestamp('reviewed_at')->nullable()->index();
            }
        });
    }

    public function down(): void {
        Schema::table('dg_user_justifications', function (Blueprint $t) {
            if (Schema::hasColumn('dg_user_justifications', 'status')) {
                $t->dropColumn('status');
            }
            if (Schema::hasColumn('dg_user_justifications', 'reviewed_by')) {
                $t->dropConstrainedForeignId('reviewed_by');
            }
            if (Schema::hasColumn('dg_user_justifications', 'reviewed_at')) {
                $t->dropColumn('reviewed_at');
            }
        });
    }
};

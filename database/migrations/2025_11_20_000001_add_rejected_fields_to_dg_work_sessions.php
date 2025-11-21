<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('dg_work_sessions', function (Blueprint $t) {
            $t->timestamp('rejected_at')->nullable()->after('approved_at');
            $t->foreignId('rejected_by')->nullable()->after('approved_by')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('dg_work_sessions', function (Blueprint $t) {
            $t->dropColumn('rejected_at');
            $t->dropConstrainedForeignId('rejected_by');
        });
    }
};

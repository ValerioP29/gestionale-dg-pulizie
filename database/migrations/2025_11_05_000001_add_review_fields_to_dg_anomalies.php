<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('dg_anomalies', function (Blueprint $t) {
            $t->timestamp('approved_at')->nullable()->after('status');
            $t->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();

            $t->timestamp('rejected_at')->nullable()->after('approved_by');
            $t->foreignId('rejected_by')->nullable()->after('rejected_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('dg_anomalies', function (Blueprint $t) {
            $t->dropConstrainedForeignId('approved_by');
            $t->dropConstrainedForeignId('rejected_by');
            $t->dropColumn(['approved_at','rejected_at']);
        });
    }
};

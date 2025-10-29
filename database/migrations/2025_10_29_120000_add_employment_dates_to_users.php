<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $t) {
            if (!Schema::hasColumn('users', 'hired_at')) {
                $t->date('hired_at')->nullable()->after('payroll_code');
            }
            if (!Schema::hasColumn('users', 'contract_end_at')) {
                $t->date('contract_end_at')->nullable()->after('hired_at');
            }
        });
    }

    public function down(): void {
        Schema::table('users', function (Blueprint $t) {
            if (Schema::hasColumn('users', 'hired_at')) {
                $t->dropColumn('hired_at');
            }
            if (Schema::hasColumn('users', 'contract_end_at')) {
                $t->dropColumn('contract_end_at');
            }
        });
    }
};

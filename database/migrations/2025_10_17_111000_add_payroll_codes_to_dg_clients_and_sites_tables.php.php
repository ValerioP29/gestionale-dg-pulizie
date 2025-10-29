<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('dg_clients', function (Blueprint $t) {
            if (!Schema::hasColumn('dg_clients', 'payroll_client_code')) {
                $t->string('payroll_client_code', 32)->nullable()->index();
            }
        });

        Schema::table('dg_sites', function (Blueprint $t) {
            if (!Schema::hasColumn('dg_sites', 'payroll_site_code')) {
                $t->string('payroll_site_code', 32)->nullable()->index();
            }
        });
    }

    public function down(): void {
        Schema::table('dg_clients', function (Blueprint $t) {
            if (Schema::hasColumn('dg_clients', 'payroll_client_code')) {
                $t->dropColumn('payroll_client_code');
            }
        });

        Schema::table('dg_sites', function (Blueprint $t) {
            if (Schema::hasColumn('dg_sites', 'payroll_site_code')) {
                $t->dropColumn('payroll_site_code');
            }
        });
    }
};

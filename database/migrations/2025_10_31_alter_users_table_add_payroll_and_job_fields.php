<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            // Riferimento al cantiere principale
            $t->foreignId('main_site_id')
                ->nullable()
                ->after('active')
                ->constrained('dg_sites')
                ->nullOnDelete();

            // Riferimento all’orario contrattuale
            $t->foreignId('contract_schedule_id')
                ->nullable()
                ->after('main_site_id')
                ->constrained('dg_contract_schedules')
                ->nullOnDelete();

            // Codice interno busta paga / HR
            $t->string('payroll_code', 64)->nullable()->after('contract_schedule_id');

        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->dropConstrainedForeignId('main_site_id');
            $t->dropConstrainedForeignId('contract_schedule_id');
            $t->dropColumn('payroll_code');
        });
    }
};

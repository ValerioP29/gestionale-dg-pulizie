<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $t) {
            // JSON delle regole avanzate (usa JSONB su Postgres)
            if (!Schema::hasColumn('users', 'rules')) {
                // se usi Postgres e vuoi jsonb:
                $t->json('rules')->nullable();
            }

            // Ore/mese calcolate (se per caso mancasse)
            if (!Schema::hasColumn('users', 'contract_hours_monthly')) {
                $t->integer('contract_hours_monthly')->nullable();
            }
        });
    }

    public function down(): void {
        Schema::table('users', function (Blueprint $t) {
            if (Schema::hasColumn('users', 'rules')) {
                $t->dropColumn('rules');
            }
            if (Schema::hasColumn('users', 'contract_hours_monthly')) {
                $t->dropColumn('contract_hours_monthly');
            }
        });
    }
};

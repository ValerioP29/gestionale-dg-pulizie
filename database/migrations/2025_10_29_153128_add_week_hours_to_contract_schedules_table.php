<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('dg_contract_schedules', function (Blueprint $table) {
            if (!Schema::hasColumn('dg_contract_schedules','mon')) {
                $table->decimal('mon', 4, 2)->default(0);
                $table->decimal('tue', 4, 2)->default(0);
                $table->decimal('wed', 4, 2)->default(0);
                $table->decimal('thu', 4, 2)->default(0);
                $table->decimal('fri', 4, 2)->default(0);
                $table->decimal('sat', 4, 2)->default(0);
                $table->decimal('sun', 4, 2)->default(0);
            }

            if (!Schema::hasColumn('dg_contract_schedules','contract_hours_monthly')) {
                $table->integer('contract_hours_monthly')->nullable();
            }

            if (!Schema::hasColumn('dg_contract_schedules','name')) {
                $table->string('name')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('dg_contract_schedules', function (Blueprint $table) {
            $table->dropColumn(['mon','tue','wed','thu','fri','sat','sun','contract_hours_monthly','name']);
        });
    }
};

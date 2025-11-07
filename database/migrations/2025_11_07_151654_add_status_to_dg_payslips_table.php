<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dg_payslips', function (Blueprint $table) {
            $table->string('status', 32)
                ->default('matched')
                ->after('period_month')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('dg_payslips', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};

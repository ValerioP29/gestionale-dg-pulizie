<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dg_contract_schedules', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->unsignedSmallInteger('weekly_minutes')->default(0);
            $t->jsonb('rules')->nullable(); // es: {"mon": {"start":"08:00","end":"12:00"}, ...}
            $t->boolean('active')->default(true);
            $t->timestampsTz();
            $t->softDeletesTz();

            $t->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dg_contract_schedules');
    }
};

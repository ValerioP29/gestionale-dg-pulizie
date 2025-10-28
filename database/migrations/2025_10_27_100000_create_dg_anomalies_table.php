<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('dg_anomalies', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('session_id')->nullable()->constrained('dg_work_sessions')->nullOnDelete();
            $t->date('date')->index();
            $t->string('type', 32)->index(); // missing_punch, absence, overtime, unplanned_day, late_entry, early_exit...
            $t->integer('minutes')->default(0);
            $t->string('status', 20)->default('open')->index(); // open|justified|rejected|approved
            $t->text('note')->nullable();
            $t->timestampsTz();
            $t->unique(['user_id','date','type']); // idempotenza
        });
    }
    public function down(): void {
        Schema::dropIfExists('dg_anomalies');
    }
};

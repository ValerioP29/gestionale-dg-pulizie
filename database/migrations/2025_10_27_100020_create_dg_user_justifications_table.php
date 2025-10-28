<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('dg_user_justifications', function (Blueprint $t) {
            $t->id();
            $t->foreignId('anomaly_id')->constrained('dg_anomalies')->cascadeOnDelete();
            $t->foreignId('type_id')->constrained('dg_justification_types')->restrictOnDelete();
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->text('note')->nullable();
            $t->string('attachment_path')->nullable();
            $t->timestampsTz();
        });
    }
    public function down(): void {
        Schema::dropIfExists('dg_user_justifications');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('dg_justification_types', function (Blueprint $t) {
            $t->id();
            $t->string('code', 16)->unique(); // FER, MAL, PER, 104, EXC...
            $t->string('label', 64);
            $t->boolean('requires_doc')->default(false);
            $t->timestampsTz();
        });
    }
    public function down(): void {
        Schema::dropIfExists('dg_justification_types');
    }
};

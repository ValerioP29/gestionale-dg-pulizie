<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dg_user_job_titles', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $t->foreignId('job_title_id')
                ->constrained('dg_job_titles')
                ->restrictOnDelete();
            $t->date('from_date')->nullable();
            $t->date('to_date')->nullable();
            $t->text('notes')->nullable();
            $t->timestampsTz();

            $t->index(['user_id', 'from_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dg_user_job_titles');
    }
};

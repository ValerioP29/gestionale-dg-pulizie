<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dg_site_assignments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade');

            $table->foreignId('site_id')
                  ->constrained('dg_sites')
                  ->onDelete('cascade');

            // chi ha effettuato l’assegnazione
            $table->foreignId('assigned_by')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null');

            $table->date('assigned_from');
            $table->date('assigned_to')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            // evita doppioni nella stessa data
            $table->unique(['user_id', 'site_id', 'assigned_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dg_site_assignments');
    }
};

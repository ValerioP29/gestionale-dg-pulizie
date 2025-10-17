<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dg_payslips', function (Blueprint $table) {
            $table->id();

            // a chi appartiene il documento
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade');

            // file info
            $table->string('file_name', 255);         // nome originale
            $table->string('file_path', 255);         // path nello storage (es. s3)
            $table->string('storage_disk', 50)->default('s3'); // s3, local, ecc.
            $table->string('mime_type', 100)->nullable();
            $table->integer('file_size')->nullable(); // in byte
            $table->string('checksum', 64)->nullable(); // SHA1 o SHA256

            // periodo di riferimento
            $table->integer('period_year');           // es. 2025
            $table->tinyInteger('period_month');      // 1..12

            // visibilità lato dipendente (si/no)
            $table->boolean('visible_to_employee')->default(true)->index();

            // audit upload / download
            $table->foreignId('uploaded_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->timestamp('uploaded_at')->useCurrent();
            $table->timestamp('downloaded_at')->nullable();
            $table->integer('downloads_count')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // indici utili
            $table->index(['user_id', 'period_year', 'period_month']);
            // evitare doppioni per stesso utente/periodo (se carichi doppio, fallisce)
            $table->unique(['user_id', 'period_year', 'period_month', 'deleted_at'], 'dg_payslips_user_period_unique');

            // opzionale: se usi checksum, evita duplicati identici
            $table->unique(['user_id', 'checksum'], 'dg_payslips_user_checksum_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dg_payslips');
    }
};

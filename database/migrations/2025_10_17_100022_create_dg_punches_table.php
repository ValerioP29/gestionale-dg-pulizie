<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dg_punches', function (Blueprint $table) {
            $table->id();

            $table->uuid('uuid')->unique(); // per sincronizzazione offline

            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            $table->foreignId('site_id')
                ->nullable()
                ->constrained('dg_sites')
                ->onDelete('set null');

            // tipo di timbratura
            $table->enum('type', ['check_in', 'check_out']);

            // dati GPS
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->integer('accuracy_m')->nullable();

            // info dispositivo
            $table->string('device_id', 255)->nullable();
            $table->integer('device_battery')->nullable();
            $table->string('network_type', 50)->nullable(); // WiFi, 4G, offline

            // orario reale del timbro
            $table->timestamp('created_at')->useCurrent();

            // data sincronizzazione (solo se offline)
            $table->timestamp('synced_at')->nullable();

            // timestamp aggiornamento (per eventuali modifiche)
            $table->timestamp('updated_at')->nullable();

            $table->index(['user_id', 'site_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dg_punches');
    }
};

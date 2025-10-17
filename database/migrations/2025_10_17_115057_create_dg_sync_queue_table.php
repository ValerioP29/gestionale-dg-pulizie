<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dg_sync_queue', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            $table->uuid('uuid')->unique();

            $table->jsonb('payload'); // dati da sincronizzare (timbrature, presenze, ecc.)

            $table->enum('status', ['pending', 'processing', 'synced', 'error'])
                ->default('pending')
                ->index();

            $table->boolean('synced')->default(false);
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);

            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dg_sync_queue');
    }
};

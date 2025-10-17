<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('dg_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            $table->string('device_id', 255);
            $table->enum('platform', ['android', 'ios', 'pwa'])->default('pwa');
            $table->timestamp('registered_at')->nullable();
            $table->timestamp('last_sync_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('dg_devices');
    }
};

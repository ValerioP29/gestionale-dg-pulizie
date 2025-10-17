<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dg_work_sessions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            $table->foreignId('site_id')
                ->nullable()
                ->constrained('dg_sites')
                ->onDelete('set null');

            $table->date('session_date')->index();

            $table->timestamp('check_in')->nullable();
            $table->timestamp('check_out')->nullable();

            $table->integer('worked_minutes')->default(0);

            $table->enum('status', ['complete', 'incomplete', 'invalid'])
                ->default('complete')
                ->index();

            $table->timestamps();

            $table->index(['user_id', 'site_id', 'session_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dg_work_sessions');
    }
};

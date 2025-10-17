<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dg_user_consents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade');

            $table->enum('type', ['privacy', 'localization', 'marketing'])->index();

            $table->boolean('accepted')->default(false);
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('revoked_at')->nullable();

            $table->string('source', 50)->default('app'); // app, admin, form_web...

            $table->timestamps();

            $table->unique(['user_id', 'type'], 'dg_user_consents_user_type_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dg_user_consents');
    }
};

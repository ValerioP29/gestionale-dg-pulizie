<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dg_client_groups', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->text('notes')->nullable();
            $t->boolean('active')->default(true);
            $t->timestampsTz();
            $t->softDeletesTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dg_client_groups');
    }
};

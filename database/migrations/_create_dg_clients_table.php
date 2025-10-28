<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dg_clients', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->foreignId('group_id')->nullable()->constrained('dg_client_groups')->nullOnDelete();
            $t->string('vat')->nullable();
            $t->string('address')->nullable();
            $t->string('email')->nullable();
            $t->string('phone')->nullable();
            $t->boolean('active')->default(true);
            $t->timestampsTz();
            $t->softDeletesTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dg_clients');
    }
};

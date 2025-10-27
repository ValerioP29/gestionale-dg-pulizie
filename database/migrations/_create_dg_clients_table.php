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
            $t->foreignId('client_group_id')->nullable()
                ->constrained('dg_client_groups')
                ->nullOnDelete();
            $t->string('name');
            $t->string('vat_number', 20)->nullable();
            $t->string('address')->nullable();
            $t->string('city')->nullable();
            $t->string('province', 5)->nullable();
            $t->string('zip', 10)->nullable();
            $t->boolean('active')->default(true);
            $t->timestampsTz();
            $t->softDeletesTz();

            $t->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dg_clients');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dg_sites', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('address', 255)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->integer('radius_m')->default(100); // raggio operativo in metri
            $table->boolean('active')->default(true)->index();
            $table->timestamps();

            $table->index(['latitude', 'longitude']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dg_sites');
    }
};

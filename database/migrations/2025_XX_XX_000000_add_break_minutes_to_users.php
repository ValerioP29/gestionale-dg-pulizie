<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // pausa giornaliera in minuti, default 0
            $table->integer('break_minutes')
                ->default(0)
                ->after('sun'); // dopo i campi delle ore giornaliere è perfetto
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('break_minutes');
        });
    }
};

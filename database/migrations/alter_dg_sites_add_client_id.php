<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dg_sites', function (Blueprint $t) {
            $t->foreignId('client_id')
                ->nullable()
                ->after('type')
                ->constrained('dg_clients')
                ->nullOnDelete();

            $t->index('client_id');
        });
    }

    public function down(): void
    {
        Schema::table('dg_sites', function (Blueprint $t) {
            $t->dropIndex(['client_id']);
            $t->dropConstrainedForeignId('client_id');
        });
    }
};

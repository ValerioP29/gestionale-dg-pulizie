<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dg_punches', function (Blueprint $t) {
            $t->foreignId('session_id')
                ->nullable()
                ->after('site_id')
                ->constrained('dg_work_sessions')
                ->nullOnDelete();

            $t->string('source', 32)
                ->nullable()
                ->after('type');

            $t->jsonb('payload')
                ->nullable()
                ->after('source');

            $t->index('created_at');
            $t->index(['user_id', 'created_at']);
            $t->index(['site_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('dg_punches', function (Blueprint $t) {
            $t->dropIndex(['created_at']);
            $t->dropIndex(['user_id', 'created_at']);
            $t->dropIndex(['site_id', 'created_at']);
            $t->dropConstrainedForeignId('session_id');
            $t->dropColumn(['source', 'payload']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $t) {
            if (!Schema::hasColumn('users', 'receive_presence_feedback')) {
                $t->boolean('receive_presence_feedback')->default(true)->index();
            }
        });
    }

    public function down(): void {
        Schema::table('users', function (Blueprint $t) {
            if (Schema::hasColumn('users', 'receive_presence_feedback')) {
                $t->dropColumn('receive_presence_feedback');
            }
        });
    }
};

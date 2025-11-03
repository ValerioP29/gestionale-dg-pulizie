<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $t) {
            $t->float('mon')->default(0);
            $t->float('tue')->default(0);
            $t->float('wed')->default(0);
            $t->float('thu')->default(0);
            $t->float('fri')->default(0);
            $t->float('sat')->default(0);
            $t->float('sun')->default(0);
        });
    }
    public function down(): void {
        Schema::table('users', fn($t) => $t->dropColumn(['mon','tue','wed','thu','fri','sat','sun']));
    }
};

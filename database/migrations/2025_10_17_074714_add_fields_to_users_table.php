<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            // aggiungiamo campi "profilo"
            $table->string('name', 255)->nullable()->change();
            $table->string('first_name', 100)->nullable()->after('id');
            $table->string('last_name', 100)->nullable()->after('first_name');
            $table->string('phone', 30)->nullable()->after('password');
            $table->boolean('active')->default(true)->index()->after('phone');
        });
    }

    public function down(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['active']);
            $table->dropColumn(['first_name', 'last_name', 'phone', 'active']);
        });
    }
};

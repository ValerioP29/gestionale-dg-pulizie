<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dg_reports_cache', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade');

            $table->foreignId('site_id')
                  ->nullable()
                  ->constrained('dg_sites')
                  ->nullOnDelete();

            $table->date('period_start');
            $table->date('period_end');

            $table->decimal('worked_hours', 6, 2)->default(0);
            $table->integer('days_present')->default(0);
            $table->integer('days_absent')->default(0);
            $table->integer('late_entries')->default(0);
            $table->integer('early_exits')->default(0);

            $table->timestamp('generated_at')->useCurrent();

            $table->timestamps();
            $table->unique(['user_id', 'site_id', 'period_start', 'period_end'], 'dg_reports_cache_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dg_reports_cache');
    }
};

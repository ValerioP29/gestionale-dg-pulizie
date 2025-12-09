<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('dg_work_sessions', function (Blueprint $table) {
            if (! Schema::hasColumn('dg_work_sessions', 'device_latitude')) {
                $table->decimal('device_latitude', 10, 7)->nullable()->after('session_date');
            }
            if (! Schema::hasColumn('dg_work_sessions', 'device_longitude')) {
                $table->decimal('device_longitude', 10, 7)->nullable()->after('device_latitude');
            }
            if (! Schema::hasColumn('dg_work_sessions', 'device_accuracy_m')) {
                $table->decimal('device_accuracy_m', 10, 2)->nullable()->after('device_longitude');
            }
            if (! Schema::hasColumn('dg_work_sessions', 'device_distance_m')) {
                $table->decimal('device_distance_m', 10, 2)->nullable()->after('device_accuracy_m');
            }
            if (! Schema::hasColumn('dg_work_sessions', 'outside_site')) {
                $table->boolean('outside_site')->default(false)->after('device_distance_m');
            }
        });
    }

    public function down(): void
    {
        Schema::table('dg_work_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('dg_work_sessions', 'outside_site')) {
                $table->dropColumn('outside_site');
            }
            if (Schema::hasColumn('dg_work_sessions', 'device_distance_m')) {
                $table->dropColumn('device_distance_m');
            }
            if (Schema::hasColumn('dg_work_sessions', 'device_accuracy_m')) {
                $table->dropColumn('device_accuracy_m');
            }
            if (Schema::hasColumn('dg_work_sessions', 'device_longitude')) {
                $table->dropColumn('device_longitude');
            }
            if (Schema::hasColumn('dg_work_sessions', 'device_latitude')) {
                $table->dropColumn('device_latitude');
            }
        });
    }
};

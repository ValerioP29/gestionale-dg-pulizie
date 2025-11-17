<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('dg_user_justifications', function (Blueprint $table) {
            if (Schema::hasColumn('dg_user_justifications', 'anomaly_id')) {
                $table->foreignId('anomaly_id')->nullable()->change();
            }

            if (! Schema::hasColumn('dg_user_justifications', 'user_id')) {
                $table->foreignId('user_id')
                    ->after('id')
                    ->constrained('users')
                    ->cascadeOnDelete();
            }

            if (! Schema::hasColumn('dg_user_justifications', 'session_id')) {
                $table->foreignId('session_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('dg_work_sessions')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('dg_user_justifications', 'date')) {
                $table->date('date')->after('session_id');
            }

            if (! Schema::hasColumn('dg_user_justifications', 'date_end')) {
                $table->date('date_end')->nullable()->after('date');
            }

            if (! Schema::hasColumn('dg_user_justifications', 'category')) {
                $table->string('category', 32)->after('date_end');
            }

            if (! Schema::hasColumn('dg_user_justifications', 'covers_full_day')) {
                $table->boolean('covers_full_day')->default(true)->after('category');
            }

            if (! Schema::hasColumn('dg_user_justifications', 'minutes')) {
                $table->unsignedInteger('minutes')->default(0)->after('covers_full_day');
            }
        });

        if (Schema::hasColumn('dg_user_justifications', 'type_id')) {
            Schema::table('dg_user_justifications', function (Blueprint $table) {
                $table->dropConstrainedForeignId('type_id');
            });
        }

        Schema::table('dg_user_justifications', function (Blueprint $table) {
            if (! Schema::hasColumn('dg_user_justifications', 'status')) {
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->index();
            } else {
                DB::statement("ALTER TABLE dg_user_justifications DROP CONSTRAINT IF EXISTS dg_user_justifications_status_check");
                DB::statement("ALTER TABLE dg_user_justifications ADD CONSTRAINT dg_user_justifications_status_check CHECK (status IN ('pending','approved','rejected'))");
                DB::table('dg_user_justifications')->where('status', 'open')->update(['status' => 'pending']);
            }
        });

        Schema::table('dg_user_justifications', function (Blueprint $table) {
            $table->index(['user_id', 'date']);
        });

        if (Schema::hasTable('dg_justification_types')) {
            Schema::dropIfExists('dg_justification_types');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('dg_user_justifications')) {
            return;
        }

        Schema::table('dg_user_justifications', function (Blueprint $table) {
            if (Schema::hasColumn('dg_user_justifications', 'user_id')) {
                $table->dropConstrainedForeignId('user_id');
            }

            if (Schema::hasColumn('dg_user_justifications', 'session_id')) {
                $table->dropConstrainedForeignId('session_id');
            }

            if (Schema::hasColumn('dg_user_justifications', 'date')) {
                $table->dropColumn('date');
            }

            if (Schema::hasColumn('dg_user_justifications', 'date_end')) {
                $table->dropColumn('date_end');
            }

            if (Schema::hasColumn('dg_user_justifications', 'category')) {
                $table->dropColumn('category');
            }

            if (Schema::hasColumn('dg_user_justifications', 'covers_full_day')) {
                $table->dropColumn('covers_full_day');
            }

            if (Schema::hasColumn('dg_user_justifications', 'minutes')) {
                $table->dropColumn('minutes');
            }
        });

        Schema::table('dg_user_justifications', function (Blueprint $table) {
            $table->enum('status', ['open', 'approved', 'rejected'])->default('open')->change();
            $table->dropIndex('dg_user_justifications_user_id_date_index');
        });

        Schema::create('dg_justification_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 16)->unique();
            $table->string('label', 64);
            $table->boolean('requires_doc')->default(false);
            $table->timestampsTz();
        });

        Schema::table('dg_user_justifications', function (Blueprint $table) {
            $table->foreignId('type_id')->nullable()->constrained('dg_justification_types')->nullOnDelete();
        });
    }
};

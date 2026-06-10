<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The original create_users_table migration predates columns the application
 * now relies on (added directly in the live DB). This backfills them and is
 * a no-op where they already exist, so it is safe against the production DB.
 *
 * NOTE: reconstructed from code usage — reconcile against `php artisan schema:dump`
 * of the live database before treating it as authoritative.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'phone_number')) {
                $table->string('phone_number', 20)->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'username')) {
                $table->string('username')->nullable()->after('phone_number');
            }
            if (!Schema::hasColumn('users', 'fcm_token')) {
                $table->text('fcm_token')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Intentionally left as a no-op: dropping columns that may predate this
        // migration in the live DB would be destructive.
    }
};

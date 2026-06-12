<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-channel notification preferences. Default ON (existing behaviour). Guarded.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            foreach (['notify_push', 'notify_sms', 'notify_whatsapp'] as $col) {
                if (!Schema::hasColumn('users', $col)) {
                    $table->boolean($col)->default(true);
                }
            }
        });
    }

    public function down(): void
    {
        // No-op: additive.
    }
};

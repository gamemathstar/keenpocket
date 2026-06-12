<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Streak-freeze item count — protects a weekly streak from a missed week.
 * Guarded; everyone starts with 2.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users') || Schema::hasColumn('users', 'streak_freezes')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('streak_freezes')->default(2);
        });
    }

    public function down(): void
    {
        // No-op: additive.
    }
};

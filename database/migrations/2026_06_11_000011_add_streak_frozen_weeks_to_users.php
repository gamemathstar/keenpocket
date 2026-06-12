<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Records which weeks a streak-freeze was spent on, so freezes survive across
 * requests and the on-the-fly streak calculation can treat them as covered.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users') || Schema::hasColumn('users', 'streak_frozen_weeks')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->text('streak_frozen_weeks')->nullable();
        });
    }

    public function down(): void
    {
        // No-op: additive.
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Visibility flag for the public adashi directory. Defaults to private so every
 * existing (family/friends) adashi stays unlisted and admin-managed. Guarded.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('adashis') || Schema::hasColumn('adashis', 'is_public')) {
            return;
        }

        Schema::table('adashis', function (Blueprint $table) {
            $table->boolean('is_public')->default(false);
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('adashis') && Schema::hasColumn('adashis', 'is_public')) {
            Schema::table('adashis', function (Blueprint $table) {
                $table->dropColumn('is_public');
            });
        }
    }
};

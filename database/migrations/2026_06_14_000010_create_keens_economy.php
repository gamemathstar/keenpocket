<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Keens coin balance — everyone starts with 50.
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'keens')) {
            Schema::table('users', function (Blueprint $table) {
                $table->integer('keens')->default(50);
            });
        }

        if (!Schema::hasTable('keen_transactions')) {
            Schema::create('keen_transactions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->integer('amount');           // +grant / -spend
                $table->string('reason')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('keen_transactions');
        Schema::dropIfExists('settings');
    }
};

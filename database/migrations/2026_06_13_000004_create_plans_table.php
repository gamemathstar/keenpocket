<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('plans')) {
            Schema::create('plans', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('owner_id')->index();
                $table->string('title');
                $table->string('month', 7)->nullable();  // e.g. 2026-06
                $table->unsignedBigInteger('budget')->nullable();
                $table->string('status', 12)->default('ACTIVE'); // ACTIVE | ARCHIVED
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('plan_collaborators')) {
            Schema::create('plan_collaborators', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('plan_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->timestamps();
                $table->unique(['plan_id', 'user_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_collaborators');
        Schema::dropIfExists('plans');
    }
};

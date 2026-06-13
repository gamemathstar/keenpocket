<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('charity_projects')) {
            Schema::create('charity_projects', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('pocket_id')->index();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('goal_type', 8)->default('amount'); // amount | items
                $table->unsignedBigInteger('target_amount')->nullable(); // for amount goals
                $table->string('status', 12)->default('ACTIVE'); // ACTIVE | COMPLETED | CLOSED
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('charity_goal_items')) {
            Schema::create('charity_goal_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('charity_project_id')->index();
                $table->string('name');                       // e.g. "Bag of rice"
                $table->string('unit', 32)->nullable();       // e.g. "bag", "carton", "kg"
                $table->unsignedInteger('target_quantity')->default(0);
                $table->unsignedBigInteger('unit_price')->nullable(); // optional ₦/unit
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('charity_goal_items');
        Schema::dropIfExists('charity_projects');
    }
};

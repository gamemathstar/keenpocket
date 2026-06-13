<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('invoice_item')) {
            return;
        }
        Schema::table('invoice_item', function (Blueprint $table) {
            // A 'Donation' line may target a specific charity project / goal item.
            if (!Schema::hasColumn('invoice_item', 'charity_project_id')) {
                $table->unsignedBigInteger('charity_project_id')->nullable()->index();
            }
            if (!Schema::hasColumn('invoice_item', 'charity_goal_item_id')) {
                $table->unsignedBigInteger('charity_goal_item_id')->nullable()->index();
            }
            // For item donations (e.g. 2 bags of rice) rather than a money amount.
            if (!Schema::hasColumn('invoice_item', 'quantity')) {
                $table->unsignedInteger('quantity')->nullable();
            }
        });
    }

    public function down(): void
    {
        // No-op: additive.
    }
};

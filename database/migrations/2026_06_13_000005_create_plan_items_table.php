<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('plan_items')) {
            return;
        }
        Schema::create('plan_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('plan_id')->index();
            $table->string('name');
            $table->unsignedInteger('quantity')->default(1);
            $table->string('unit', 32)->nullable();
            $table->unsignedBigInteger('unit_price')->nullable();   // optional ₦/unit
            $table->string('status', 12)->default('pending');       // pending | purchased | deferred
            $table->unsignedBigInteger('claimed_by')->nullable();   // who will buy it
            $table->boolean('priority')->default(false);            // carried over / prioritised
            $table->string('note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('purchased_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_items');
    }
};

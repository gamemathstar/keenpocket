<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Group-buying / shopping list items attached to a pocket. Guarded create —
 * a no-op if the table already exists.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pocket_shopping_items')) {
            return;
        }

        Schema::create('pocket_shopping_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pocket_id')->index();
            $table->string('name');
            $table->unsignedBigInteger('unit_price')->default(0);
            $table->unsignedInteger('person_count')->default(1);
            $table->string('category', 64)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pocket_shopping_items');
    }
};

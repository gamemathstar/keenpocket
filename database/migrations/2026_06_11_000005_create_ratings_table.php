<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rater_id')->index();
            $table->unsignedBigInteger('ratee_id')->index();
            $table->string('context_type', 16);  // pocket | adashi
            $table->unsignedBigInteger('context_id');
            $table->unsignedTinyInteger('stars'); // 1..5
            $table->text('comment')->nullable();
            $table->timestamps();

            // One rating per rater per group.
            $table->unique(['rater_id', 'context_type', 'context_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};

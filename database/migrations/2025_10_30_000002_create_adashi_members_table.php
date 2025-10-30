<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adashi_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('adashi_id')->constrained('adashis')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->integer('position');
            $table->boolean('has_received')->default(false);
            $table->dateTime('next_receiver_date')->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['adashi_id','user_id']);
            $table->index('position');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adashi_members');
    }
};



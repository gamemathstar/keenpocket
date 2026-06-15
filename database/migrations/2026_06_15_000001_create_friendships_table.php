<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('friendships')) {
            Schema::create('friendships', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();    // requester
                $table->unsignedBigInteger('friend_id')->index();  // recipient
                $table->string('status', 16)->default('pending');  // pending | accepted
                $table->timestamps();
                $table->unique(['user_id', 'friend_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('friendships');
    }
};

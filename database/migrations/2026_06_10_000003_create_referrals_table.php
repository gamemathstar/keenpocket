<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('referrer_id')->index();
            $table->unsignedBigInteger('referred_id')->unique(); // a user is referred at most once
            $table->string('code', 16);
            $table->string('status', 16)->default('pending'); // pending | qualified | rewarded
            $table->unsignedBigInteger('reward_amount')->default(0);
            $table->timestamp('qualified_at')->nullable();
            $table->timestamp('rewarded_at')->nullable();
            $table->timestamps();

            $table->index(['referrer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};

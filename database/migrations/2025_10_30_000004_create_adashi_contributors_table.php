<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adashi_contributors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('adashi_member_id')->constrained('adashi_members')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->unsignedBigInteger('share_amount');
            $table->boolean('is_active')->default(true);
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();
            $table->unique(['adashi_member_id','user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adashi_contributors');
    }
};



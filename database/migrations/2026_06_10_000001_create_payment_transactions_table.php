<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('provider', 32);
            $table->string('reference', 64)->unique();
            $table->unsignedBigInteger('amount'); // minor unit handling is done in the service
            $table->string('currency', 8)->default('NGN');
            $table->string('status', 16)->default('pending'); // pending | success | failed
            $table->string('authorization_url', 512)->nullable();
            $table->text('gateway_response')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('payment_transactions');
    }
};

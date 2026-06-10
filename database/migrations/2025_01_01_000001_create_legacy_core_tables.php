<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reconstructed migrations for the core "legacy" tables that were provisioned
 * directly in the production database and never had Laravel migrations.
 *
 * Every table is created only if absent, so this is a complete no-op against the
 * existing production DB and only builds the schema in fresh / test environments
 * (enabling the money-path test suite and clean new deployments).
 *
 * Source: the May-2022 SQL dump + current code usage. Types/lengths are
 * best-effort — RECONCILE against `php artisan schema:dump` of the live DB
 * before relying on this for a production rebuild. Enum columns are modelled as
 * strings for cross-driver (sqlite) compatibility; allowed values noted inline.
 * Adashi link columns on `invoices` are added by the 2025_10_30 alter migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pockets')) {
            Schema::create('pockets', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('user_id')->index();
                $table->string('pocket_type')->default('Ramadan'); // Ramadan | Adashe | Layya
                $table->string('title');
                $table->text('description')->nullable();
                $table->integer('year');
                $table->integer('start_month');
                $table->integer('month_count');
                $table->integer('max_keens')->default(0);
                $table->integer('amount_per_hand')->default(0);
                $table->text('per_hand_allowed')->nullable();
                $table->boolean('open_purchasing_item')->default(false);
                $table->boolean('status')->default(false); // 0 = invitation-only
                $table->string('bank')->nullable();
                $table->string('nuban')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('pocket_slots')) {
            Schema::create('pocket_slots', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('pocket_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->integer('slot_number')->default(0);
                $table->integer('hand_count')->default(0);
                $table->decimal('plan_value', 10, 0)->nullable();
                $table->integer('amount_paying')->default(0);
                $table->tinyInteger('status')->default(0); // 1 = active member
                $table->text('comment')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('invoices')) {
            Schema::create('invoices', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('pocket_slot_id')->nullable()->index();
                $table->string('invoice_no', 24);
                $table->decimal('amount', 10, 2)->default(0);
                $table->string('reference_no', 24)->nullable();
                $table->string('payment_status')->default('Not Paid'); // Paid | Not Paid
                $table->dateTime('payment_date')->nullable();
                $table->string('paid_through')->default('Pending'); // Online | Manual | Pending
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('invoice_item')) {
            Schema::create('invoice_item', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('invoice_id')->index();
                $table->unsignedBigInteger('item_id');
                $table->decimal('amount', 10, 0)->default(0);
                $table->string('type')->nullable(); // Paid | Donation
                $table->integer('month')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('items')) {
            Schema::create('items', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->string('category')->nullable(); // Paid | Donation
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('pocket_items')) {
            Schema::create('pocket_items', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('pocket_id')->index();
                $table->unsignedBigInteger('item_id');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedBigInteger('sender_id')->nullable();
                $table->string('title')->nullable();
                $table->text('body')->nullable();
                $table->string('type')->nullable();
                $table->unsignedBigInteger('model_id')->nullable();
                $table->string('status')->default('Not Read'); // Not Read | Read
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('invitations')) {
            Schema::create('invitations', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('pocket_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('phone_number', 20)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('banned_users')) {
            Schema::create('banned_users', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('user_id')->index();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('posts')) {
            Schema::create('posts', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('title');
                $table->text('body');
                $table->string('featured_image')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // No-op: these tables predate Laravel migrations in production; dropping
        // them on rollback would be destructive. Manage teardown manually if needed.
    }
};

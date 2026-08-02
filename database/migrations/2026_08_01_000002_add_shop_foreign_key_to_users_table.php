<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Completes the deferred constraint from the users migration.
 *
 * users.shop_id was created as a plain indexed column because the shops table
 * did not exist yet. Now that it does, the referential integrity can be
 * enforced. Split into its own migration to avoid a circular dependency:
 * shops.manager_id points at users, and users.shop_id points at shops.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Detaching staff is correct when a shop is removed; deleting the
            // staff records with it would destroy their history.
            $table->foreign('shop_id')
                ->references('id')
                ->on('shops')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['shop_id']);
        });
    }
};

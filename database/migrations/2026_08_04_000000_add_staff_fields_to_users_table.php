<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {

            // Secondary contact number. `phone` remains the primary mobile.
            $table->string('alternate_phone', 20)->nullable()->after('phone');

            $table->date('joining_date')->nullable()->after('alternate_phone');

            // decimal, never float — money must not carry binary rounding
            // error. 12,2 covers up to 9,999,999,999.99.
            $table->decimal('salary', 12, 2)->nullable()->after('joining_date');

            $table->text('description')->nullable()->after('salary');

            // Staff accumulate leads, tasks and login history. Soft deleting
            // keeps those references resolvable. It also removes the row from
            // authentication: Laravel's EloquentUserProvider builds its query
            // with newQuery(), which applies the SoftDeletingScope.
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn([
                'alternate_phone',
                'joining_date',
                'salary',
                'description',
            ]);
        });
    }
};

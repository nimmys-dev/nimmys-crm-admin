<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {

            // Next scheduled salary review.
            $table->date('increment_date')->nullable()->after('salary');

            // Default raise applied on that date. decimal, never float —
            // money must not carry binary rounding error.
            $table->decimal('increment_amount', 10, 2)->nullable()->after('increment_date');

            // Opt out of the reminder per employee. Defaults on, because the
            // reminder exists to stop reviews being missed.
            $table->boolean('increment_notification')->default(true)->after('increment_amount');

            // Grants an Employee access to the Lead module. Defaults off:
            // least privilege, and it is the Admin's decision to open it.
            $table->boolean('lead_module_access')->default(false)->after('increment_notification');

            /*
             * The daily reminder sweep asks: which active staff have the
             * reminder on and an increment_date inside the next five days?
             * This composite covers that predicate so the job never scans
             * the whole table as headcount grows.
             */
            $table->index(['increment_notification', 'increment_date'], 'users_increment_reminder_index');

            // The Lead module will filter staff by access; a plain index is
            // enough for a boolean with low cardinality used alongside role.
            $table->index(['lead_module_access'], 'users_lead_access_index');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_increment_reminder_index');
            $table->dropIndex('users_lead_access_index');

            $table->dropColumn([
                'increment_date',
                'increment_amount',
                'increment_notification',
                'lead_module_access',
            ]);
        });
    }
};

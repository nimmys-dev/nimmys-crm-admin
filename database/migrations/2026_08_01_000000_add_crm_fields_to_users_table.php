<?php

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {

            // Tenant scope. Nullable because Admin is organisation-wide and
            // belongs to no single shop. Indexed because almost every future
            // module query filters by it. The foreign key is added by the
            // shops migration, since that table does not exist yet.
            $table->unsignedBigInteger('shop_id')->nullable()->after('id')->index();

            // Human-readable staff identifier for mobile login and payroll
            // reference. Nullable so Admin accounts need not carry one;
            // unique so it can be used as a login handle later.
            $table->string('employee_code', 32)->nullable()->unique()->after('shop_id');

            // Drives every authorization decision. Indexed because role
            // filters appear in staff listings and scoped queries.
            $table->string('role', 20)->default(UserRole::Employee->value)->after('name')->index();

            // Defaulting to Employee is the safe failure mode: a row created
            // without an explicit role gets the least privilege, not the most.

            $table->string('phone', 20)->nullable()->after('email');

            // Relative path on the configured disk, not a blob. Keeps the
            // row small and lets the storage driver change without a migration.
            $table->string('photo')->nullable()->after('phone');

            // Reversible deactivation. Preserves the audit trail on leads and
            // tasks that deleting the user would orphan.
            $table->string('status', 20)->default(UserStatus::Active->value)->after('password')->index();

            // FCM / APNs push token, written by the mobile app after login and
            // cleared on logout. Nullable and text-length because vendor
            // tokens are long and rotate.
            $table->string('device_token', 512)->nullable()->after('status');

            // Named with the Laravel `_at` timestamp convention rather than a
            // bare `last_login`, so Eloquent's datetime cast applies cleanly.
            $table->timestamp('last_login_at')->nullable()->after('device_token');

            // Supports "unusual sign-in" checks and session forensics.
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['shop_id']);
            $table->dropIndex(['role']);
            $table->dropIndex(['status']);
            $table->dropUnique(['employee_code']);

            $table->dropColumn([
                'shop_id',
                'employee_code',
                'role',
                'phone',
                'photo',
                'status',
                'device_token',
                'last_login_at',
                'last_login_ip',
            ]);
        });
    }
};

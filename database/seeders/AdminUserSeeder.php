<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * One account per role, so all three access paths can be exercised.
     *
     * Idempotent — re-running resets passwords rather than erroring on a
     * duplicate email. Override the admin defaults per environment with
     * ADMIN_EMAIL / ADMIN_PASSWORD in .env.
     *
     * The manager and employee accounts are development fixtures. The
     * employee exists specifically to prove the web portal rejects that role.
     */
    public function run(): void
    {
        $password = env('ADMIN_PASSWORD', 'password');

        // The User model casts 'password' => 'hashed', so these are hashed on
        // save. Do not wrap them in bcrypt().

        User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@nimmys.test')],
            [
                'name' => env('ADMIN_NAME', 'Admin'),
                'role' => UserRole::Admin,
                'status' => UserStatus::Active,
                'password' => $password,
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'manager@nimmys.test'],
            [
                'name' => 'Manager',
                'role' => UserRole::Manager,
                'status' => UserStatus::Active,
                'employee_code' => 'MGR-001',
                'password' => $password,
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'employee@nimmys.test'],
            [
                'name' => 'Employee',
                'role' => UserRole::Employee,
                'status' => UserStatus::Active,
                'employee_code' => 'EMP-001',
                'password' => $password,
                'email_verified_at' => now(),
            ]
        );
    }
}

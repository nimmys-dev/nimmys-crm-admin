<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Create the first admin account so the panel is reachable.
     *
     * Idempotent — re-running resets the password rather than erroring on a
     * duplicate email. Override the defaults per environment with
     * ADMIN_EMAIL / ADMIN_PASSWORD in .env.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@nimmys.test')],
            [
                'name' => env('ADMIN_NAME', 'Admin'),
                // The User model casts 'password' => 'hashed', so this is
                // hashed on save. Do not wrap it in bcrypt().
                'password' => env('ADMIN_PASSWORD', 'password'),
                'email_verified_at' => now(),
            ]
        );
    }
}

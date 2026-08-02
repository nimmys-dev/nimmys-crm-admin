<?php

namespace App\Enums;

/**
 * The three system roles.
 *
 * Deliberately a backed enum rather than a roles table: the set is fixed,
 * the permission matrix is static (config/permissions.php), and nobody edits
 * roles at runtime. This gives compile-time safety and costs zero queries.
 *
 * Switch to spatie/laravel-permission only when the product needs roles or
 * permissions created by users at runtime. The `role` column survives that
 * migration, so it is additive rather than a rewrite.
 */
enum UserRole: string
{
    case Admin = 'admin';
    case Manager = 'manager';
    case Employee = 'employee';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Manager => 'Manager',
            self::Employee => 'Employee',
        };
    }

    /**
     * Whether this role may sign in to the web portal.
     *
     * Employees are mobile-only — this is the single source of truth for that
     * rule, enforced at login and again by middleware on every request.
     */
    public function canAccessWeb(): bool
    {
        return match ($this) {
            self::Admin, self::Manager => true,
            self::Employee => false,
        };
    }

    /**
     * Whether this role may sign in to the mobile application.
     * All three roles can; kept explicit so the rule has one home.
     */
    public function canAccessMobile(): bool
    {
        return true;
    }

    /**
     * Landing route after a successful web login.
     *
     * Admin and Manager share the dashboard route today and diverge by
     * permission inside it. Point a role at its own route here when the
     * dashboards genuinely split.
     */
    public function dashboardRoute(): string
    {
        return match ($this) {
            self::Admin, self::Manager => 'dashboard',
            self::Employee => 'login',
        };
    }

    /**
     * Roles permitted to use the web portal.
     *
     * @return array<int, self>
     */
    public static function webRoles(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $role) => $role->canAccessWeb(),
        ));
    }

    /**
     * @return array<string, string> value => label, for select inputs
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $role) => [$role->value => $role->label()])
            ->all();
    }
}

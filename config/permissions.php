<?php

use App\Enums\UserRole;

/*
|--------------------------------------------------------------------------
| Role → Ability Matrix
|--------------------------------------------------------------------------
|
| The single source of truth for authorization. AuthServiceProvider turns
| every ability listed here into a Gate, so `@can`, `Gate::allows()`,
| `$user->can()` and the `can:` route middleware all resolve from this file.
|
| Two surfaces are defined separately because a role's reach differs by
| device. An Employee has no web abilities at all but a real mobile set.
|
| '*' grants every ability on that surface. Use it only for Admin — it means
| "new modules are automatically available", which is the intent for an
| owner role and dangerous for any other.
|
| Naming: <module>.<action>. Mobile abilities are prefixed 'mobile.' so the
| two surfaces can never collide. '.own' means the record must belong to the
| authenticated user — enforced by policies once the models exist, since a
| Gate alone cannot see the record.
|
*/

return [

    /*
    |----------------------------------------------------------------------
    | Web portal — Admin and Manager only
    |----------------------------------------------------------------------
    */

    'web' => [

        UserRole::Admin->value => ['*'],

        UserRole::Manager->value => [
            'dashboard.view',
            'leads.manage',
            'tasks.manage',
            'profile.view',

            /*
             * To let Managers read the staff directory without being able to
             * change anything, add 'staff.view' here. Index and show are
             * gated on staff.view; create, edit, delete and the privileged
             * toggles stay on staff.manage / staff.settings.manage, which
             * Managers do not hold. Left out for now because the documented
             * matrix keeps staff Admin-only.
             */
        ],

        // Employees are mobile-only. An empty set is the second line of
        // defence behind the login check and the web-access middleware.
        UserRole::Employee->value => [],

    ],

    /*
    |----------------------------------------------------------------------
    | Mobile application — all three roles
    |----------------------------------------------------------------------
    |
    | Consumed by the future Sanctum API. Listing them now means token
    | abilities can be issued straight from this matrix at login.
    |
    */

    'mobile' => [

        UserRole::Admin->value => [
            'mobile.dashboard.view',
            'mobile.leads.view',
            'mobile.tasks.view',
            'mobile.staff.view',
            'mobile.reports.view',
            'mobile.profile.view',
        ],

        UserRole::Manager->value => [
            'mobile.dashboard.view',
            'mobile.leads.view',
            'mobile.tasks.view',
            'mobile.approvals.manage',
            'mobile.profile.view',
        ],

        UserRole::Employee->value => [
            'mobile.dashboard.view',
            'mobile.leads.own',
            'mobile.tasks.own',
            'mobile.profile.view',
        ],

    ],

    /*
    |----------------------------------------------------------------------
    | Every ability the web surface knows about
    |----------------------------------------------------------------------
    |
    | Needed to expand Admin's '*'. Add new web abilities here as modules
    | land, otherwise Admin will not receive them.
    |
    */

    'web_abilities' => [
        'dashboard.view',
        'shops.manage',

        // Reading the staff directory, separate from changing it, so view
        // access can be widened without also granting write access.
        'staff.view',
        'staff.manage',

        // Salary-increment reminders and Lead module access are Admin-only
        // even among users who can otherwise edit staff.
        'staff.settings.manage',

        'leads.manage',
        'tasks.manage',
        'reports.view',
        'settings.manage',
        'profile.view',
    ],

];

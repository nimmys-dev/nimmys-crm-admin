<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeadAssignmentController;
use App\Http\Controllers\LeadCallDetailController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\LeadFollowUpController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Guest
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->name('login.store');
});

/*
|--------------------------------------------------------------------------
| Web portal — Admin and Manager only
|--------------------------------------------------------------------------
|
| Defence in depth, three layers deep:
|   auth        — is there a session at all
|   web.access  — is this role allowed on the web, and still active
|   can:*       — does this role hold the specific ability
|
| The `can:` layer is what actually separates Admin from Manager, and it
| reads from config/permissions.php, so the matrix stays in one file.
|
*/

Route::middleware(['auth', 'web.access'])->group(function () {

    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    Route::redirect('/', '/dashboard')->name('home');

    Route::get('dashboard', [DashboardController::class, 'index'])
        ->middleware('can:dashboard.view')
        ->name('dashboard');

    Route::get('search', SearchController::class)->name('search');

    /*
     | Modules — index only until the models exist. Swap each for
     | Route::resource() then; the middleware line stays as it is.
     */

    // Shop Management — full CRUD, Admin only via shops.manage.
    Route::resource('shops', ShopController::class)
        ->middleware('can:shops.manage');

    // Staff Management — full CRUD, Admin only via staff.manage.
    Route::middleware('can:staff.manage')->group(function () {
        Route::delete('staff/{staff}/photo', [StaffController::class, 'destroyPhoto'])
            ->name('staff.photo.destroy');

        Route::resource('staff', StaffController::class)
            ->parameters(['staff' => 'staff']);
    });

    /*
     | Lead module. The `leads` middleware admits Admins and Managers on their
     | role, and Employees only with lead_module_access switched on — it 404s
     | for anyone else so the module stays invisible rather than merely
     | forbidden. Placeholder until the Lead module is built.
     */
    Route::middleware('leads')->group(function () {
        // Assignment and follow-ups sit before the resource so their nested
        // paths are not swallowed by leads/{lead}.
        Route::put('leads/{lead}/assignment', [LeadAssignmentController::class, 'update'])
            ->name('leads.assignment.update');

        Route::post('leads/{lead}/follow-ups', [LeadFollowUpController::class, 'store'])
            ->name('leads.follow-ups.store');

        Route::patch('leads/{lead}/follow-ups/{followUp}/complete', [LeadFollowUpController::class, 'complete'])
            ->name('leads.follow-ups.complete');

        /*
         | Call details — a child of Lead Management. Nested so the parent is
         | always present for the policy to authorise against, and declared
         | before the lead resource so leads/{lead}/calls/… is not swallowed
         | by leads/{lead}.
         */
        Route::post('leads/{lead}/calls', [LeadCallDetailController::class, 'store'])
            ->name('leads.calls.store');

        Route::get('leads/{lead}/calls/{call}', [LeadCallDetailController::class, 'show'])
            ->name('leads.calls.show');

        Route::get('leads/{lead}/calls/{call}/edit', [LeadCallDetailController::class, 'edit'])
            ->name('leads.calls.edit');

        Route::put('leads/{lead}/calls/{call}', [LeadCallDetailController::class, 'update'])
            ->name('leads.calls.update');

        Route::delete('leads/{lead}/calls/{call}', [LeadCallDetailController::class, 'destroy'])
            ->name('leads.calls.destroy');

        Route::resource('leads', LeadController::class);
    });

    Route::get('tasks', [TaskController::class, 'index'])
        ->middleware('can:tasks.manage')
        ->name('tasks.index');

    Route::get('reports', [ReportController::class, 'index'])
        ->middleware('can:reports.view')
        ->name('reports.index');

    /*
     | Account
     */

    Route::get('profile', [ProfileController::class, 'index'])
        ->middleware('can:profile.view')
        ->name('profile.index');

    Route::get('settings', [SettingsController::class, 'index'])
        ->middleware('can:settings.manage')
        ->name('settings.index');

});

/*
|--------------------------------------------------------------------------
| Mobile API — not yet routed
|--------------------------------------------------------------------------
|
| Sanctum is not installed. When the mobile app is built:
|
|   1. composer require laravel/sanctum
|   2. php artisan install:api            (creates routes/api.php + migration)
|   3. Add HasApiTokens to App\Models\User
|   4. Wrap the API routes:
|
|        Route::middleware(['auth:sanctum', 'mobile'])->group(function () {
|            // issue tokens with $user->abilitiesFor('mobile')
|        });
|
| The `mobile` alias and EnsureUserCanAccessMobile already exist, and
| config/permissions.php already carries the mobile matrix.
|
*/

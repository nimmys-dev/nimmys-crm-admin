<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\ProfileController;
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
| Authenticated
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    Route::redirect('/', '/dashboard')->name('home');

    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('search', SearchController::class)->name('search');

    /*
     | Modules — index only for now. Add store/update/destroy per module as
     | it is built, or swap to Route::resource() once the models exist.
     */

    Route::get('shops', [ShopController::class, 'index'])->name('shops.index');

    Route::get('staff', [StaffController::class, 'index'])->name('staff.index');

    Route::get('leads', [LeadController::class, 'index'])->name('leads.index');

    Route::get('tasks', [TaskController::class, 'index'])->name('tasks.index');

    /*
     | Account
     */

    Route::get('profile', [ProfileController::class, 'index'])->name('profile.index');

    Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');

});

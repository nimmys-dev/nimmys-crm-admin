<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StaffController;
use App\Http\Controllers\Api\LeadController;


Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user-statuses', [AuthController::class, 'getUserStatuses']);
    Route::get('/user-roles', [AuthController::class, 'getUserRoles']);

    //staff
    Route::get('/staff', [StaffController::class, 'staffList']);
    Route::post('/create-staff', [StaffController::class, 'createStaff']);
    Route::get('/branches', [StaffController::class, 'getBranches']);
    Route::get('/view-staff/{id}', [StaffController::class, 'viewStaff']);
    Route::post('/update-staff/{id}', [StaffController::class, 'updateStaff']);
    Route::delete('/delete-staff/{id}', [StaffController::class, 'deleteStaff']);

    //lead
     Route::post('/create-leads', [LeadController::class, 'createLead']);
     Route::get('/view-lead/{id}', [LeadController::class, 'viewLead']);
     Route::post('/update-lead/{id}', [LeadController::class, 'updateLead']);
     Route::get('/leads', [LeadController::class, 'leadList']);
     Route::get('/lead-sources', [LeadController::class, 'getLeadSources']);
     Route::get('/lead-assignees', [LeadController::class, 'getLeadAssignees']);
});
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StaffController;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\DashboardController;


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
     Route::get('/leads/{lead}/quotation/pdf-details',[LeadController::class, 'quotationPdfDetails']);
     Route::post('/leads/{lead}/calls',[LeadController::class, 'addCall']);
     Route::get('/leads/{lead}/calls/{call}',[LeadController::class, 'getCallDetails']);
     Route::put('/leads/{lead}/close',[LeadController::class, 'closeLead']);
     Route::get('/lead-call-history/{lead}/calls',[LeadController::class, 'callHistory']);

    //task
    Route::post('/tasks',[TaskController::class, 'store'])->name('api.tasks.store');
    Route::get('/tasks', [TaskController::class, 'index']);
    Route::get('/tasks/{task}', [TaskController::class, 'viewTask']);
    Route::put('/update-task/{task}', [TaskController::class, 'updateTask']);
    Route::get('/task-types', [TaskController::class, 'taskType']);
    Route::delete('/delete-task/{task}',[TaskController::class, 'deleteTask']);
    Route::post('/complete-task/{task}',[TaskController::class, 'completeTask']);
    Route::get('/my-tasks',[TaskController::class, 'myTasks']);
    Route::post('/reassign-task',[TaskController::class, 'reassign']);
    Route::get('/approval-task',[TaskController::class, 'approvalIndex']);
    Route::post('/tasks/{task}/approve',[TaskController::class, 'approve']);

    //settings
    Route::get('/company-profile',[SettingsController::class, 'companyProfile']);
    Route::post('/company-profile/update',[SettingsController::class, 'updateCompanyProfile']);

    Route::get('/dashboard/task-counts',[DashboardController::class, 'dashboardCounts']);


   
});
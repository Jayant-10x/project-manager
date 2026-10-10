<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\Api\FollowupController;
use App\Http\Controllers\Api\LegacyGatewayController;
use App\Http\Controllers\Api\MilestoneController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\SalesPersonController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// Backward-compatible single endpoint for the frontend SPA
Route::match(['GET', 'POST'], '/api.php', [LegacyGatewayController::class, 'dispatch'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

// Explicit RESTful endpoints
Route::prefix('v1')->group(function () {
    Route::get('/ping', [AuthController::class, 'ping']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/check-reset', [AuthController::class, 'checkReset']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    Route::middleware('crm.auth')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
        Route::get('/load', [DashboardController::class, 'load']);
        Route::get('/dashboard', [DashboardController::class, 'dashboard']);
        Route::get('/search', [DashboardController::class, 'search']);

        Route::post('/customers', [CustomerController::class, 'save']);
        Route::delete('/customers', [CustomerController::class, 'delete']);
        Route::post('/customers/bulk-import', [CustomerController::class, 'bulkImport']);

        Route::post('/projects', [ProjectController::class, 'save']);
        Route::delete('/projects', [ProjectController::class, 'delete']);

        Route::post('/payments', [PaymentController::class, 'savePayment']);
        Route::delete('/payments', [PaymentController::class, 'deletePayment']);
        Route::post('/payment-terms', [PaymentController::class, 'saveTerms']);
        Route::post('/payment-terms/recurring', [PaymentController::class, 'generateRecurring']);
        Route::delete('/payment-terms', [PaymentController::class, 'deleteTerm']);

        Route::post('/milestones', [MilestoneController::class, 'saveMilestones']);

        Route::post('/documents', [DocumentController::class, 'upload']);
        Route::delete('/documents', [DocumentController::class, 'delete']);

        Route::post('/followups', [FollowupController::class, 'save']);
        Route::delete('/followups', [FollowupController::class, 'delete']);

        Route::post('/tasks', [TaskController::class, 'save']);
        Route::post('/tasks/toggle', [TaskController::class, 'toggle']);
        Route::delete('/tasks', [TaskController::class, 'delete']);

        Route::post('/sales-persons', [SalesPersonController::class, 'save']);
        Route::delete('/sales-persons', [SalesPersonController::class, 'delete']);

        Route::post('/users', [UserController::class, 'save']);
        Route::delete('/users', [UserController::class, 'delete']);

        Route::post('/notifications/read', [NotificationController::class, 'markAsRead']);
        Route::post('/settings', [SettingController::class, 'save']);
        Route::get('/audit', [SettingController::class, 'auditFeed']);
        Route::get('/export', [ExportController::class, 'export']);
    });
});

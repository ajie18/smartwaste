<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\TruckController;
use App\Http\Controllers\Admin\RouteController;
use App\Http\Controllers\Admin\PickupController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\AnnouncementController as AdminAnnouncementController;
use App\Http\Controllers\Admin\MRFController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\ProfileController as AdminProfileController;

// ====================== AUTH ======================
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
        Route::get('/profile', [AuthController::class, 'getProfile']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
    });
});

// ====================== ADMIN ======================
Route::prefix('admin')->middleware(['auth:sanctum', 'role:admin'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'stats']);

    // Users
    Route::apiResource('users', UserController::class);

    // Trucks
    Route::apiResource('trucks', TruckController::class);
    Route::put('/trucks/{id}/location', [TruckController::class, 'updateLocation']);

    // Routes
    Route::apiResource('routes', RouteController::class);

    // Pickups
    Route::get('/pickups', [PickupController::class, 'index']);
    Route::post('/assign-route', [PickupController::class, 'assign']);
    Route::put('/pickups/{id}', [PickupController::class, 'update']);
    Route::delete('/pickups/{id}', [PickupController::class, 'destroy']);
    Route::post('/notify-pickup-today', [PickupController::class, 'notifyPickupToday']);

    // Reports
    Route::get('/reports', [AdminReportController::class, 'index']);
    Route::get('/reports/{id}', [AdminReportController::class, 'show']);
    Route::put('/reports/{id}/status', [AdminReportController::class, 'updateStatus']);
    Route::delete('/reports/{id}', [AdminReportController::class, 'destroy']);

    // Analytics
    Route::get('/analytics', [AnalyticsController::class, 'index']);
    Route::get('/export-analytics', [AnalyticsController::class, 'export']);

    // Announcements
    Route::get('/announcements', [AdminAnnouncementController::class, 'index']);
    Route::post('/announcements', [AdminAnnouncementController::class, 'store']);
    Route::delete('/announcements/{id}', [AdminAnnouncementController::class, 'destroy']);

    // MRF
    Route::apiResource('mrf-locations', MRFController::class);

    // Profile
    Route::get('/profile', [AdminProfileController::class, 'show']);
    Route::put('/profile', [AdminProfileController::class, 'update']);
});

// ====================== DRIVER (coming next) ======================
// Route::prefix('driver')->middleware(['auth:sanctum', 'role:driver'])->group(function () { ... });

// ====================== RESIDENT (coming next) ======================
// Route::prefix('resident')->middleware(['auth:sanctum', 'role:resident'])->group(function () { ... });
<?php

use App\Http\Controllers\Admin\AiInsightController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MRFController;
use App\Http\Controllers\Admin\PickupController;
use App\Http\Controllers\Admin\ProfileController as AdminProfileController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\RouteController;
use App\Http\Controllers\Admin\TruckController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Chat\ChatController;
use App\Http\Controllers\Driver\DriverController;
use App\Http\Controllers\Resident\ResidentController;
use Illuminate\Support\Facades\Route;


Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    Route::post('/verify-email', [AuthController::class, 'verifyEmailPin']);
    Route::get('/verify-email/{token}', [AuthController::class, 'verifyEmailLink']);
    Route::post('/resend-verification', [AuthController::class, 'resendVerification']);
});

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/resident-locations', [ResidentController::class, 'getResidentLocations'])
        ->middleware('role:resident|driver|admin');

    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
        Route::get('/profile', [AuthController::class, 'getProfile']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
    });


    Route::prefix('chat')->middleware('role:resident|driver')->group(function () {
        Route::get('/conversations', [ChatController::class, 'index']);
        Route::get('/conversations/{id}/messages', [ChatController::class, 'messages']);
        Route::post('/conversations/{id}/messages', [ChatController::class, 'send']);
        Route::put('/conversations/{id}/read', [ChatController::class, 'markRead']);
    });

    Route::prefix('admin')->middleware('role:admin')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'stats']);
        Route::get('/analytics', [ReportController::class, 'analytics']);
        Route::get('/export-analytics', [AnalyticsController::class, 'export']);

        Route::get('/ai/analytics-insight', [AiInsightController::class, 'analyticsInsight']);
        Route::post('/ai/reports/{id}/triage', [AiInsightController::class, 'triageReport']);

        Route::get('/profile', [AdminProfileController::class, 'show']);
        Route::put('/profile', [AdminProfileController::class, 'update']);

        Route::apiResource('users', UserController::class);

        Route::get('/trucks', [TruckController::class, 'index']);
        Route::post('/trucks', [TruckController::class, 'store']);
        Route::put('/trucks/{id}', [TruckController::class, 'update']);
        Route::delete('/trucks/{id}', [TruckController::class, 'destroy']);
        Route::put('/trucks/{id}/location', [TruckController::class, 'updateLocation']);

        Route::get('/routes', [RouteController::class, 'index']);
        Route::post('/routes', [RouteController::class, 'store']);
        Route::put('/routes/{id}', [RouteController::class, 'update']);
        Route::delete('/routes/{id}', [RouteController::class, 'destroy']);

        Route::get('/pickups', [PickupController::class, 'index']);
        Route::post('/pickups', [PickupController::class, 'assign']);
        Route::put('/pickups/{id}', [PickupController::class, 'update']);
        Route::delete('/pickups/{id}', [PickupController::class, 'destroy']);
        Route::post('/pickups/notify-today', [PickupController::class, 'notifyPickupToday']);

        Route::get('/reports', [ReportController::class, 'index']);
        Route::get('/reports/{id}', [ReportController::class, 'show']);
        Route::put('/reports/{id}/status', [ReportController::class, 'updateStatus']);
        Route::delete('/reports/{id}', [ReportController::class, 'destroy']);

        Route::get('/announcements', [AnnouncementController::class, 'index']);
        Route::post('/announcements', [AnnouncementController::class, 'store']);
        Route::delete('/announcements/{id}', [AnnouncementController::class, 'destroy']);

        Route::get('/mrf', [MRFController::class, 'index']);
        Route::get('/mrf/{id}', [MRFController::class, 'show']);
        Route::post('/mrf', [MRFController::class, 'store']);
        Route::put('/mrf/{id}', [MRFController::class, 'update']);
        Route::delete('/mrf/{id}', [MRFController::class, 'destroy']);

        Route::get('/mrf-locations', [MRFController::class, 'index']);
        Route::post('/mrf-locations', [MRFController::class, 'store']);
        Route::put('/mrf-locations/{id}', [MRFController::class, 'update']);
        Route::delete('/mrf-locations/{id}', [MRFController::class, 'destroy']);
    });


    Route::prefix('driver')->middleware('role:driver')->group(function () {

        Route::get('/today-assignment', [DriverController::class, 'getTodayAssignment']);
        Route::get('/today-route-details', [DriverController::class, 'getTodayRouteDetails']);
        Route::put('/assignments/{id}/status', [DriverController::class, 'updateAssignmentStatus']);
        Route::get('/my-schedule', [DriverController::class, 'getMySchedule']);
        Route::post('/location', [DriverController::class, 'updateMyLocation']);
        Route::get('/location-history', [DriverController::class, 'getLocationHistory']);
        Route::post('/route-progress', [DriverController::class, 'updateRouteProgress']);
        Route::get('/route-waypoints', [DriverController::class, 'getRouteWaypoints']);

        Route::post('/reports', [DriverController::class, 'submitReport']);
        Route::post('/reports/quick', [DriverController::class, 'createQuickReport']);
        Route::get('/reports', [DriverController::class, 'getMyReports']);
        Route::get('/reports/open', [DriverController::class, 'getOpenReports']);
        Route::put('/reports/{id}/status', [DriverController::class, 'updateReportStatus']);
        Route::patch('/reports/{id}/status', [DriverController::class, 'updateReportStatus']);

        Route::get('/announcements', [DriverController::class, 'getAnnouncements']);
        Route::get('/trip-history', [DriverController::class, 'getTripHistory']);
        Route::get('/stats', [DriverController::class, 'getStats']);
        Route::get('/performance-stats', [DriverController::class, 'getPerformanceStats']);
        Route::get('/connected-drivers', [DriverController::class, 'getConnectedDrivers']);


        Route::get('/notifications', [DriverController::class, 'getNotifications']);
        Route::put('/notifications/{id}/read', [DriverController::class, 'markNotificationRead']);

        Route::get('/profile', [DriverController::class, 'getProfile']);
        Route::put('/profile', [DriverController::class, 'updateProfile']);

        Route::get('/today', [DriverController::class, 'getTodayAssignment']);
        Route::get('/schedule', [DriverController::class, 'getMySchedule']);
        Route::get('/trips', [DriverController::class, 'getTripHistory']);
        Route::patch('/assignment/{id}/status', [DriverController::class, 'updateAssignmentStatus']);
    });

    Route::prefix('resident')->middleware('role:resident')->group(function () {
        Route::get('/today-truck', [ResidentController::class, 'getTodayTruck']);
        Route::get('/route-waypoints', [ResidentController::class, 'getRouteWaypoints']);
        Route::get('/pickup-schedule', [ResidentController::class, 'getPickupSchedule']);
        Route::get('/trucks/{truckId}/location', [ResidentController::class, 'getTruckLocation']);

        Route::get('/truck/{truckId}/location', [ResidentController::class, 'getTruckLocation']);

        Route::post('/reports', [ResidentController::class, 'createReport']);
        Route::get('/reports', [ResidentController::class, 'getReports']);

        Route::get('/notifications', [ResidentController::class, 'getNotifications']);
        Route::get('/announcements', [ResidentController::class, 'getAnnouncements']);

        Route::post('/location', [ResidentController::class, 'shareMyLocation']);
        Route::post('/share-location', [ResidentController::class, 'shareMyLocation']);
        Route::get('/mrf-location', [ResidentController::class, 'getMRFLocation']);

        Route::get('/profile', [ResidentController::class, 'getProfile']);
        Route::put('/profile', [ResidentController::class, 'updateProfile']);
        Route::post('/change-password', [ResidentController::class, 'changePassword']);

        Route::post('/chat/start', [ChatController::class, 'startWithTodaysDriver']);
    });
});

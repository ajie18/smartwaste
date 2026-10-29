<?php

use App\Http\Controllers\Admin\MapController;
use App\Http\Controllers\Auth\AdminSessionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/admin/login', [AdminSessionController::class, 'create'])->name('login');
Route::post('/admin/login', [AdminSessionController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('admin.login.store');

Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/map', [MapController::class, 'index'])->name('admin.map');
    Route::post('/logout', [AdminSessionController::class, 'destroy'])->name('admin.logout');
});

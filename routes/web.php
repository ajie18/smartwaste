<?php

use Illuminate\Support\Facades\Route;


Route::prefix('admin')->name('admin.')->group(function () {
    Route::view('/dashboard', 'admin.dashboard')->name('dashboard');
    Route::view('/users', 'admin.users')->name('users');
    Route::view('/trucks', 'admin.trucks')->name('trucks');
    Route::view('/routes', 'admin.routes')->name('routes');
    Route::view('/schedules', 'admin.schedules')->name('schedules');
    Route::view('/reports', 'admin.reports')->name('reports');
    Route::view('/announcements', 'admin.announcements')->name('announcements');
    Route::view('/analytics', 'admin.analytics')->name('analytics');
});

Route::get('/driver/dashboard', fn() => view('driver.dashboard'))->name('driver.dashboard');
Route::get('/resident/dashboard', fn() => view('resident.dashboard'))->name('resident.dashboard');


Route::view('/login', 'auth.login')->name('login');
Route::view('/register', 'auth.register')->name('register');
Route::view('/forgot-password', 'auth.forgot-password')->name('forgot-password');
Route::view('/verify-email', 'auth.verify-email')->name('verify-email');

Route::get('/login.html', fn() => redirect()->route('login'));

Route::get('/', fn() => redirect()->route('login'));

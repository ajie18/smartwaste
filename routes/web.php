<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin panel pages
|--------------------------------------------------------------------------
| These just render the shell — each page's JS (public/js/admin/*.js) calls
| checkAuth() on load and redirects to /login if there's no token in
| localStorage, then talks to the /api/admin/* endpoints directly. No
| server-side session/auth is enforced here on purpose, mirroring the
| original static-HTML-served-by-Express setup; the API itself is the
| real security boundary (role:admin middleware in routes/api.php).
*/
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

Route::get('/driver/dashboard', fn () => view('driver.dashboard'))->name('driver.dashboard');
Route::get('/resident/dashboard', fn () => view('resident.dashboard'))->name('resident.dashboard');

/*
|--------------------------------------------------------------------------
| Auth pages
|--------------------------------------------------------------------------
| Plain Blade views — no server-side session guard. They call the /api/auth/*
| endpoints directly and manage the Sanctum token client-side in
| localStorage, same pattern as every other page in this app.
*/
Route::view('/login', 'auth.login')->name('login');
Route::view('/register', 'auth.register')->name('register');
Route::view('/forgot-password', 'auth.forgot-password')->name('forgot-password');
Route::view('/verify-email', 'auth.verify-email')->name('verify-email');

// The old static frontend linked to /login.html everywhere (see the top of
// every public/js/**/*.js file) — redirect that legacy path so nothing 404s.
Route::get('/login.html', fn () => redirect()->route('login'));

Route::get('/', fn () => redirect()->route('login'));

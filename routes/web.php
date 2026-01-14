<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;

Route::get('/', function () {
    return view('welcome');
});

// Authentication routes - use web middleware for sessions
Route::prefix('api/auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth');
    Route::get('/me', [AuthController::class, 'me'])->middleware('auth');
    Route::post('/forgot-password', [AuthController::class, 'sendPasswordResetLink']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

// Login route - Vue.js SPA
Route::get('/login', function () {
    return view('admin');
});

// Admin panel route - Vue.js SPA
Route::get('/admin/{any?}', function () {
    return view('admin');
})->where('any', '.*')->name('admin');

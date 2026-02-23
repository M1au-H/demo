<?php

use App\Http\Controllers\Api\AuthController;

// User routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/verify_token', [AuthController::class, 'verifyToken']);
Route::post('/logout', [AuthController::class, 'logout']);
Route::post('/profile/update', [AuthController::class, 'updateProfile']);
Route::post('/profile/avatar', [AuthController::class, 'uploadAvatar']);  // <-- baru

// Admin routes
Route::post('/admin/login', [AuthController::class, 'adminLogin']);
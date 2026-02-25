<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;



// ── Public routes ──
Route::post('/login',        [AuthController::class, 'login']);
Route::post('/register',     [AuthController::class, 'register']);
Route::post('/admin/login',  [AuthController::class, 'adminLogin']);
Route::post('/verify_token', [AuthController::class, 'verifyToken']);

// ── Protected routes (auth dicek manual via getUserFromToken di controller) ──
Route::post('/logout',                  [AuthController::class, 'logout']);
Route::post('/profile/update',          [AuthController::class, 'updateProfile']);
Route::post('/profile/avatar',          [AuthController::class, 'uploadAvatar']);
Route::post('/profile/change-password', [AuthController::class, 'changePassword']);
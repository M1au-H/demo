<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\PerformanceController;
use App\Http\Controllers\Api\Admin\AdminAttendanceController;
use App\Http\Controllers\Api\Admin\AdminPerformanceController;
use App\Http\Controllers\Api\Admin\PositionController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\LeaveController;
use Illuminate\Support\Facades\Route;

// ══════════════════════════════════════════
// PUBLIC ROUTES (tidak perlu token)
// ══════════════════════════════════════════
Route::post('/login',        [AuthController::class, 'login']);
Route::post('/register',     [AuthController::class, 'register']);
Route::post('/admin/login',  [AuthController::class, 'adminLogin']);
Route::post('/verify_token', [AuthController::class, 'verifyToken']);

// ══════════════════════════════════════════
// PROTECTED ROUTES — semua pakai auth.token
// ══════════════════════════════════════════
Route::middleware('auth.token')->group(function () {

    // Auth
    Route::post('/logout',                  [AuthController::class, 'logout']);
    Route::post('/profile/update',          [AuthController::class, 'updateProfile']);
    Route::post('/profile/avatar',          [AuthController::class, 'uploadAvatar']);
    Route::post('/profile/change-password', [AuthController::class, 'changePassword']);

    // ══════════════════════════════════════════
    // USER ROUTES — ABSENSI
    // ══════════════════════════════════════════
    Route::post('/attendance/check-in',         [AttendanceController::class, 'checkIn']);
    Route::post('/attendance/check-out',        [AttendanceController::class, 'checkOut']);
    Route::get('/attendance/today',             [AttendanceController::class, 'todayStatus']);
    Route::get('/attendance/my-history',        [AttendanceController::class, 'myHistory']);
    Route::get('/attendance/photo/{id}/{type}', [AttendanceController::class, 'servePhoto']);

    // ══════════════════════════════════════════
    // USER ROUTES — PERFORMA, SANKSI, TUGAS
    // ══════════════════════════════════════════
    Route::get('/performance/my', [PerformanceController::class, 'myPerformance']);
    Route::get('/sanctions/my',   [PerformanceController::class, 'mySanctions']);
    Route::get('/tasks/my',       [PerformanceController::class, 'myTasks']);
    Route::delete('/performance/{id}',        [PerformanceController::class, 'deleteReview']);
    Route::post('/sanctions/{id}/complete',   [PerformanceController::class, 'completeSanction']);
    Route::post('/tasks/{id}/complete',       [PerformanceController::class, 'completeTask']);

    // ══════════════════════════════════════════
    // USER ROUTES — IZIN
    // ══════════════════════════════════════════
    Route::get('/leaves/my',      [LeaveController::class, 'myLeaves']);
    Route::post('/leaves',        [LeaveController::class, 'store']);
    Route::delete('/leaves/{id}', [LeaveController::class, 'destroy']);

    // ══════════════════════════════════════════
    // USER ROUTES — NOTIFIKASI
    // ══════════════════════════════════════════
    Route::get('/notifications',            [NotificationController::class, 'index']);
    Route::post('/notifications/read-all',  [NotificationController::class, 'readAll']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'read']);

    // ══════════════════════════════════════════
    // ADMIN ROUTES — HR MANAGEMENT
    // ══════════════════════════════════════════
    Route::prefix('admin')->middleware('role:admin')->group(function () {

        // Absensi
        Route::get('attendance/today',   [AdminAttendanceController::class, 'today']);
        Route::get('attendance/monthly', [AdminAttendanceController::class, 'monthly']);
        Route::put('attendance/{id}',    [AdminAttendanceController::class, 'edit']);

        // Performa
        Route::get('performance/{userId}',  [AdminPerformanceController::class, 'userHistory']);
        Route::post('performance/{userId}', [AdminPerformanceController::class, 'store']);
        Route::delete('/performance/{id}',        [PerformanceController::class, 'deleteReview']);

// Selesaikan sanksi & upload foto bukti
Route::post('/sanctions/{id}/complete',   [PerformanceController::class, 'completeSanction']);

// Selesaikan tugas & upload foto bukti
Route::post('/tasks/{id}/complete',       [PerformanceController::class, 'completeTask']);

        // Sanksi
        Route::post('sanction/{userId}', [AdminPerformanceController::class, 'giveSanction']);

        // Tugas
        Route::post('task/{userId}',     [AdminPerformanceController::class, 'giveTask']);
        Route::put('task/{taskId}/done', [AdminPerformanceController::class, 'markTaskDone']);

        // Jabatan
        Route::get('positions',              [PositionController::class, 'index']);
        Route::post('positions',             [PositionController::class, 'store']);
        Route::put('positions/{id}',         [PositionController::class, 'update']);
        Route::delete('positions/{id}',      [PositionController::class, 'destroy']);
        Route::post('positions/{id}/assign', [PositionController::class, 'assign']);
        Route::post('positions/{id}/revoke', [PositionController::class, 'revoke']);

        // List pegawai (untuk dropdown)
        Route::get('employees', [AdminPerformanceController::class, 'employees']);

        // Izin pegawai
        Route::get('leaves', [LeaveController::class, 'adminIndex']);
    });

}); // end auth.token
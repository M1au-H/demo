<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\FaceController;
use App\Http\Controllers\Api\FaceLoginController;
use App\Http\Controllers\Api\PerformanceController;
use App\Http\Controllers\Api\Admin\AdminAttendanceController;
use App\Http\Controllers\Api\Admin\AdminPerformanceController;
use App\Http\Controllers\Api\Admin\PositionController;
use App\Http\Controllers\Api\Admin\PayrollController;
use App\Http\Controllers\Api\Admin\KpiController; // ← TAMBAH INI
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

// Face — PUBLIC karena dipanggil sebelum user login
Route::get('/face/profiles', [FaceController::class, 'allProfiles']);
Route::post('/face/login',   [FaceLoginController::class, 'login']);

// ══════════════════════════════════════════
// IZIN & CUTI via FACE — PUBLIC (tanpa token)
// ══════════════════════════════════════════
Route::post('/face/leaves',         [LeaveController::class, 'storeByFace']);
Route::get('/face/leaves/{userId}', [LeaveController::class, 'myLeavesByFace']);
Route::delete('/face/leaves/{id}',  [LeaveController::class, 'destroyByFace']);

// ══════════════════════════════════════════
// PROTECTED ROUTES — semua pakai auth.token
// ══════════════════════════════════════════
Route::middleware('auth.token')->group(function () {

    // Auth
    Route::post('/logout',                  [AuthController::class, 'logout']);
    Route::post('/profile/update',          [AuthController::class, 'updateProfile']);
    Route::post('/profile/avatar',          [AuthController::class, 'uploadAvatar']);
    Route::post('/profile/change-password', [AuthController::class, 'changePassword']);

    // ── Absensi User ──
    Route::post('/attendance/check-in',  [AttendanceController::class, 'checkIn']);
    Route::post('/attendance/check-out', [AttendanceController::class, 'checkOut']);
    Route::get('/attendance/today',      [AttendanceController::class, 'todayStatus']);
    Route::get('/attendance/my-history', [AttendanceController::class, 'myHistory']);

    // ── Performa, Sanksi, Tugas User ──
    Route::get('/performance/my',           [PerformanceController::class, 'myPerformance']);
    Route::delete('/performance/{id}',      [PerformanceController::class, 'deleteReview']);
    Route::get('/sanctions/my',             [PerformanceController::class, 'mySanctions']);
    Route::post('/sanctions/{id}/complete', [PerformanceController::class, 'completeSanction']);
    Route::get('/tasks/my',                 [PerformanceController::class, 'myTasks']);
    Route::post('/tasks/{id}/complete',     [PerformanceController::class, 'completeTask']);

    // ── Izin User (dengan auth token) ──
    Route::get('/leaves/my',      [LeaveController::class, 'myLeaves']);
    Route::post('/leaves',        [LeaveController::class, 'store']);
    Route::delete('/leaves/{id}', [LeaveController::class, 'destroy']);

    // ── Notifikasi ──
    Route::get('/notifications',            [NotificationController::class, 'index']);
    Route::post('/notifications/read-all',  [NotificationController::class, 'readAll']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'read']);

    // ── Slip Gaji User ──
    Route::get('/user/payroll', [PayrollController::class, 'myPayroll']);

    // ── KPI User (lihat KPI sendiri) ── // ← TAMBAH INI
    Route::get('/kpi/my', [KpiController::class, 'myKpi']);

    // ══════════════════════════════════════════
    // ADMIN ROUTES
    // ══════════════════════════════════════════
    Route::prefix('admin')->middleware('role:admin')->group(function () {

        // ── Absensi Admin ──
        Route::get('attendance/today',             [AdminAttendanceController::class, 'today']);
        Route::get('attendance/monthly',           [AdminAttendanceController::class, 'monthly']);
        Route::get('attendance/photo/{id}/{type}', [AdminAttendanceController::class, 'photo']);
        Route::put('attendance/{id}',              [AdminAttendanceController::class, 'edit']);

        // Performa
        Route::get('performance/{userId}',  [AdminPerformanceController::class, 'userHistory']);
        Route::post('performance/{userId}', [AdminPerformanceController::class, 'store']);

        // Sanksi
        Route::get('sanctions/completed',    [AdminPerformanceController::class, 'completedSanctions']);
        Route::post('sanctions/{id}/seen',   [AdminPerformanceController::class, 'markSanctionSeen']);
        Route::post('sanction/{userId}',     [AdminPerformanceController::class, 'giveSanction']);

        // Tugas
        Route::get('tasks/completed',        [AdminPerformanceController::class, 'completedTasks']);
        Route::post('tasks/{id}/seen',       [AdminPerformanceController::class, 'markTaskSeen']);
        Route::post('task/{userId}',         [AdminPerformanceController::class, 'giveTask']);
        Route::put('task/{taskId}/done',     [AdminPerformanceController::class, 'markTaskDone']);

        // Face Management
        Route::get('face/status',             [FaceController::class, 'status']);
        Route::post('face/enroll/{userId}',   [FaceController::class, 'enroll']);
        Route::delete('face/delete/{userId}', [FaceController::class, 'delete']);

        // Jabatan
        Route::get('positions',              [PositionController::class, 'index']);
        Route::post('positions',             [PositionController::class, 'store']);
        Route::put('positions/{id}',         [PositionController::class, 'update']);
        Route::delete('positions/{id}',      [PositionController::class, 'destroy']);
        Route::post('positions/{id}/assign', [PositionController::class, 'assign']);
        Route::post('positions/{id}/revoke', [PositionController::class, 'revoke']);

        // List pegawai
        Route::get('employees', [AdminPerformanceController::class, 'employees']);

        // Izin & Cuti (admin view)
        Route::get('leaves', [LeaveController::class, 'adminIndex']);

        // ── Payroll ──
        Route::get('payroll',                 [PayrollController::class, 'index']);
        Route::post('payroll/generate',       [PayrollController::class, 'generate']);
        Route::get('payroll/{id}',            [PayrollController::class, 'show']);
        Route::put('payroll/{id}',            [PayrollController::class, 'update']);
        Route::post('payroll/{id}/approve',   [PayrollController::class, 'approve']);
        Route::post('payroll/{id}/mark-paid', [PayrollController::class, 'markPaid']);
        Route::delete('payroll/{id}',         [PayrollController::class, 'destroy']);

        // ── Salary Settings ──
        Route::get('salary-settings',          [PayrollController::class, 'salaryIndex']);
        Route::put('salary-settings/{userId}', [PayrollController::class, 'salaryUpdate']);

        // ── KPI Admin ── // ← TAMBAH INI
        Route::get('kpi',                      [KpiController::class, 'index']);
        Route::post('kpi/calculate-all',       [KpiController::class, 'calculateAll']);
        Route::put('kpi/{userId}/rating',      [KpiController::class, 'updateRating']);

    });

}); // end auth.token
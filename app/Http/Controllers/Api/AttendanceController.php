<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Payroll;
use App\Models\Salary;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AttendanceController extends Controller
{
    private float $storeLat;
    private float $storeLng;
    private int   $storeRadius;

    public function __construct()
    {
        $this->storeLat    = (float) env('STORE_LAT',    -7.2750211);
        $this->storeLng    = (float) env('STORE_LNG',    112.6518010);
        $this->storeRadius = (int)   env('STORE_RADIUS', 100);
    }

    private function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $R  = 6371000;
        $φ1 = deg2rad($lat1);
        $φ2 = deg2rad($lat2);
        $Δφ = deg2rad($lat2 - $lat1);
        $Δλ = deg2rad($lng2 - $lng1);
        $a  = sin($Δφ / 2) ** 2 + cos($φ1) * cos($φ2) * sin($Δλ / 2) ** 2;
        return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function validateLocation(Request $request): void
    {
        $lat = $request->input('lat');
        $lng = $request->input('lng');

        if ($lat === null || $lng === null) {
            abort(422, 'Data lokasi GPS tidak ditemukan. Pastikan izin lokasi diaktifkan.');
        }

        $jarak = $this->haversine((float) $lat, (float) $lng, $this->storeLat, $this->storeLng);

        if ($jarak > $this->storeRadius) {
            abort(422, 'Kamu berada ' . round($jarak) . 'm dari toko. Absensi hanya bisa dilakukan dalam radius ' . $this->storeRadius . 'm.');
        }
    }

    private function getUserFromToken(Request $request): ?User
    {
        $header = $request->header('Authorization', '');
        $token  = preg_replace('/^(Token|Bearer)\s+/i', '', trim($header));
        return User::where('api_token', $token)->first();
    }

    private function savePhoto(Request $request, string $field = 'photo'): ?string
    {
        if ($request->hasFile($field)) {
            $file     = $request->file($field);
            $filename = 'attendance-photos/' . uniqid('att_', true) . '.jpg';
            Storage::disk('public')->put($filename, file_get_contents($file->getRealPath()));
            return $filename;
        }

        $base64 = $request->input($field);
        if (!$base64) return null;

        try {
            if (!str_contains($base64, ',')) return null;
            $imageData = explode(',', $base64, 2)[1];
            $decoded   = base64_decode($imageData);
            if (!$decoded) return null;
            $filename = 'attendance-photos/' . uniqid('att_', true) . '.jpg';
            Storage::disk('public')->put($filename, $decoded);
            return $filename;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function getShiftDeadline(Carbon $date): Carbon
    {
        return $date->copy()->setTime(8, 0, 0);
    }

    private function getShiftStart(Carbon $date): Carbon
    {
        return $date->copy()->setTime(7, 0, 0);
    }

    private function getShiftEnd(Carbon $date): Carbon
    {
        return $date->copy()->setTime($date->dayOfWeek === 5 ? 15 : 16, 0, 0);
    }

    // ── POST /api/attendance/check-in ────────────────────────────────────
    public function checkIn(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['message' => 'Unauthorized'], 401);

        $this->validateLocation($request);

        $today         = Carbon::today()->toDateString();
        $now           = Carbon::now();
        $shiftDeadline = $this->getShiftDeadline(Carbon::today());

        if (Attendance::where('user_id', $user->id)->where('date', $today)->exists()) {
            return response()->json(['message' => 'Kamu sudah absen masuk hari ini.'], 422);
        }

        $status      = $now->greaterThan($shiftDeadline) ? 'late' : 'on_time';
        $lateMinutes = $status === 'late' ? (int) $now->diffInMinutes($shiftDeadline) : 0;

        $salary = Salary::where('user_id', $user->id)
            ->select('user_id', 'base_salary', 'late_rate', 'overtime_rate', 'position_allowance')
            ->first();

        $lateDeductionAmount = $salary ? round($lateMinutes * (float) $salary->late_rate) : 0;

        $photoPath = $this->savePhoto($request, 'photo');

        $attendance = Attendance::create([
            'user_id'               => $user->id,
            'date'                  => $today,
            'check_in_time'         => $now->toTimeString(),
            'check_in_photo'        => $photoPath,
            'check_in_lat'          => $request->input('lat'),
            'check_in_lng'          => $request->input('lng'),
            'status'                => $status,
            'late_minutes'          => $lateMinutes,
            'late_deduction_amount' => $lateDeductionAmount,
        ]);

        $this->bustCache($user->id);

        $message = $status === 'late'
            ? "Check-in berhasil. Terlambat {$lateMinutes} menit (batas masuk 08:00)."
            : 'Check-in berhasil. Tepat waktu!';

        return response()->json(['message' => $message, 'data' => $attendance]);
    }

    // ── POST /api/attendance/check-out ───────────────────────────────────
    public function checkOut(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['message' => 'Unauthorized'], 401);

        $this->validateLocation($request);

        $today     = Carbon::today()->toDateString();
        $now       = Carbon::now();
        $isWeekend = Carbon::today()->isWeekend();
        $shiftEnd  = $this->getShiftEnd(Carbon::today());

        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->whereNotNull('check_in_time')
            ->whereNull('check_out_time')
            ->first();

        if (!$attendance) {
            return response()->json(['message' => 'Kamu belum absen masuk hari ini.'], 422);
        }

        $checkoutStatus    = 'normal';
        $earlyLeaveMinutes = 0;
        $overtimeMinutes   = 0;

        if ($isWeekend) {
            $checkoutStatus  = 'overtime';
            $checkInTime     = Carbon::parse($today . ' ' . $attendance->check_in_time);
            $overtimeMinutes = (int) $now->diffInMinutes($checkInTime);
        } elseif ($now->lessThan($shiftEnd)) {
            $checkoutStatus    = 'early_leave';
            $earlyLeaveMinutes = (int) $now->diffInMinutes($shiftEnd);
        } elseif ($now->greaterThan($shiftEnd)) {
            $checkoutStatus  = 'overtime';
            $overtimeMinutes = (int) $now->diffInMinutes($shiftEnd);
        }

        $salary = Salary::where('user_id', $user->id)
            ->select('user_id', 'base_salary', 'late_rate', 'overtime_rate', 'position_allowance')
            ->first();

        $earlyLeaveDeductionAmount = $salary
            ? round($earlyLeaveMinutes * (float) $salary->late_rate) : 0;

        $overtimePayAmount = $salary
            ? round($overtimeMinutes * (float) $salary->overtime_rate) : 0;

        $photoPath = $this->savePhoto($request, 'photo');

        $attendance->update([
            'check_out_time'               => $now->toTimeString(),
            'check_out_photo'              => $photoPath,
            'check_out_lat'                => $request->input('lat'),
            'check_out_lng'                => $request->input('lng'),
            'checkout_status'              => $checkoutStatus,
            'early_leave_minutes'          => $earlyLeaveMinutes,
            'overtime_minutes'             => $overtimeMinutes,
            'early_leave_deduction_amount' => $earlyLeaveDeductionAmount,
            'overtime_pay_amount'          => $overtimePayAmount,
        ]);

        // Auto recalculate payroll — panggil static method langsung tanpa import circular
        $this->recalculatePayroll($user->id, (int) Carbon::today()->month, (int) Carbon::today()->year);

        $this->bustCache($user->id);

        $message = match (true) {
            $isWeekend                        => "Check-out berhasil. Lembur akhir pekan {$overtimeMinutes} menit. Bonus: Rp " . number_format($overtimePayAmount, 0, ',', '.'),
            $checkoutStatus === 'early_leave' => "Check-out berhasil. Pulang cepat {$earlyLeaveMinutes} menit.",
            $checkoutStatus === 'overtime'    => "Check-out berhasil. Lembur {$overtimeMinutes} menit. Bonus: Rp " . number_format($overtimePayAmount, 0, ',', '.'),
            default                           => 'Check-out berhasil. Tepat waktu!',
        };

        return response()->json(['message' => $message, 'data' => $attendance]);
    }

    // ── PRIVATE: Recalculate payroll ─────────────────────────────────────
    // FIX: Tidak lagi import PayrollController (circular dependency)
    // Langsung panggil static method via fully-qualified class name
    private function recalculatePayroll(int $userId, int $month, int $year): void
    {
        try {
            $existing = Payroll::where('user_id', $userId)
                ->where('month', $month)->where('year', $year)->first();

            if ($existing && in_array($existing->status, ['approved', 'paid'])) return;

            Salary::firstOrCreate(
                ['user_id' => $userId],
                ['base_salary' => 0, 'position_allowance' => 0, 'overtime_rate' => 0, 'late_rate' => 0]
            );

            // Panggil via fully-qualified name — TANPA use/import di atas
            $calc = \App\Http\Controllers\Api\Admin\PayrollController::calculatePayroll($userId, $month, $year);

            Payroll::updateOrCreate(
                ['user_id' => $userId, 'month' => $month, 'year' => $year],
                $calc
            );
        } catch (\Throwable $e) {
            Log::error('Auto payroll recalculate error: ' . $e->getMessage(), [
                'user_id' => $userId, 'month' => $month, 'year' => $year,
            ]);
        }
    }

    // ── GET /api/attendance/today ─────────────────────────────────────────
    public function todayStatus(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['message' => 'Unauthorized'], 401);

        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', Carbon::today()->toDateString())
            ->first();

        return response()->json(['data' => $attendance]);
    }

    // ── GET /api/attendance/my-history ───────────────────────────────────
    public function myHistory(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['message' => 'Unauthorized'], 401);

        $month = $request->month ?? Carbon::now()->month;
        $year  = $request->year  ?? Carbon::now()->year;

        $cacheKey = "att_history_{$user->id}_{$month}_{$year}";

        $attendances = Cache::remember($cacheKey, 300, function () use ($user, $month, $year) {
            return Attendance::where('user_id', $user->id)
                ->whereMonth('date', $month)
                ->whereYear('date',  $year)
                ->orderBy('date', 'desc')
                ->get();
        });

        $summary = [
            'total_hadir'                 => $attendances->whereNotNull('check_in_time')->count(),
            'total_late_minutes'          => (int)   $attendances->sum('late_minutes'),
            'total_early_leave_minutes'   => (int)   $attendances->sum('early_leave_minutes'),
            'total_overtime_minutes'      => (int)   $attendances->sum('overtime_minutes'),
            'total_late_deduction'        => (float) $attendances->sum('late_deduction_amount'),
            'total_early_leave_deduction' => (float) $attendances->sum('early_leave_deduction_amount'),
            'total_overtime_pay'          => (float) $attendances->sum('overtime_pay_amount'),
        ];

        return response()->json(['data' => $attendances, 'summary' => $summary]);
    }

    // ── Helper: bust cache ────────────────────────────────────────────────
    private function bustCache(int $userId): void
    {
        $month = now()->month;
        $year  = now()->year;
        Cache::forget("att_history_{$userId}_{$month}_{$year}");
        Cache::forget('dashboard_att_today');
        Cache::forget('dashboard_stats');
    }
}
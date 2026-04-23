<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Admin\PayrollController;
use App\Models\Attendance;
use App\Models\Payroll;
use App\Models\Salary;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AttendanceController extends Controller
{
    // ── Koordinat toko (dari .env) ────────────────────────────────────────────
    private float $storeLat;
    private float $storeLng;
    private int   $storeRadius;

    public function __construct()
    {
        $this->storeLat    = (float) env('STORE_LAT',    -7.2750211);
        $this->storeLng    = (float) env('STORE_LNG',    112.6518010);
        $this->storeRadius = (int)   env('STORE_RADIUS', 100); // meter
    }

    // ── Haversine: hitung jarak 2 koordinat dalam meter ──────────────────────
    private function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $R  = 6371000; // radius bumi dalam meter
        $φ1 = deg2rad($lat1);
        $φ2 = deg2rad($lat2);
        $Δφ = deg2rad($lat2 - $lat1);
        $Δλ = deg2rad($lng2 - $lng1);
        $a  = sin($Δφ / 2) ** 2 + cos($φ1) * cos($φ2) * sin($Δλ / 2) ** 2;
        return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    // ── Validasi lokasi user vs toko ─────────────────────────────────────────
    private function validateLocation(Request $request): void
    {
        $lat = $request->input('lat');
        $lng = $request->input('lng');

        // Jika tidak ada GPS sama sekali → tolak
        if ($lat === null || $lng === null) {
            abort(422, 'Data lokasi GPS tidak ditemukan. Pastikan izin lokasi diaktifkan.');
        }

        $jarak = $this->haversine(
            (float) $lat,
            (float) $lng,
            $this->storeLat,
            $this->storeLng
        );

        if ($jarak > $this->storeRadius) {
            abort(422, 'Kamu berada ' . round($jarak) . 'm dari toko. Absensi hanya bisa dilakukan dalam radius ' . $this->storeRadius . 'm.');
        }
    }

    // ── Helper: ambil user dari token ────────────────────────────────────────
    private function getUserFromToken(Request $request)
    {
        $header = $request->header('Authorization', '');
        $token  = preg_replace('/^(Token|Bearer)\s+/i', '', trim($header));
        return \App\Models\User::where('api_token', $token)->first();
    }

    // ── Helper: simpan foto (support file upload & base64) ───────────────────
    private function savePhoto(Request $request, string $field = 'photo'): ?string
    {
        // 1. Coba sebagai file upload (FormData)
        if ($request->hasFile($field)) {
            $file     = $request->file($field);
            $filename = 'attendance-photos/' . uniqid('att_', true) . '.jpg';
            Storage::disk('public')->put($filename, file_get_contents($file->getRealPath()));
            return $filename;
        }

        // 2. Fallback: coba sebagai base64 string
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

    // ── Helper: shift timing ─────────────────────────────────────────────────
    private function getShiftDeadline(Carbon $date): Carbon
    {
        return $date->copy()->setTime(8, 0, 0);
    }

    private function getShiftStart(Carbon $date): Carbon
    {
        return $date->copy()->setTime(7, 0, 0);
    }

    // Shift selesai: Jumat = 15:00, lainnya = 16:00
    private function getShiftEnd(Carbon $date): Carbon
    {
        return $date->copy()->setTime($date->dayOfWeek === 5 ? 15 : 16, 0, 0);
    }

    // ── POST /api/attendance/check-in ────────────────────────────────────────
    public function checkIn(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['message' => 'Unauthorized'], 401);

        // ✅ Validasi lokasi GPS dulu sebelum proses apapun
        $this->validateLocation($request);

        $today         = Carbon::today()->toDateString();
        $now           = Carbon::now();
        $shiftDeadline = $this->getShiftDeadline(Carbon::today());

        if (Attendance::where('user_id', $user->id)->where('date', $today)->exists()) {
            return response()->json(['message' => 'Kamu sudah absen masuk hari ini.'], 422);
        }

        $status      = $now->greaterThan($shiftDeadline) ? 'late' : 'on_time';
        $lateMinutes = $status === 'late' ? (int) $now->diffInMinutes($shiftDeadline) : 0;

        $salary              = Salary::where('user_id', $user->id)->first();
        $lateDeductionAmount = $salary ? round($lateMinutes * (float) $salary->late_rate) : 0;

        $photoPath = $this->savePhoto($request, 'photo');

        $attendance = Attendance::create([
            'user_id'               => $user->id,
            'date'                  => $today,
            'check_in_time'         => $now->toTimeString(),
            'check_in_photo'        => $photoPath,
            'check_in_lat'          => $request->input('lat'),  // ✅ simpan GPS
            'check_in_lng'          => $request->input('lng'),  // ✅ simpan GPS
            'status'                => $status,
            'late_minutes'          => $lateMinutes,
            'late_deduction_amount' => $lateDeductionAmount,
        ]);

        $message = $status === 'late'
            ? "Check-in berhasil. Terlambat {$lateMinutes} menit (batas masuk 08:00)."
            : 'Check-in berhasil. Tepat waktu!';

        return response()->json(['message' => $message, 'data' => $attendance]);
    }

    // ── POST /api/attendance/check-out ───────────────────────────────────────
    public function checkOut(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['message' => 'Unauthorized'], 401);

        // ✅ Validasi lokasi GPS dulu sebelum proses apapun
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

        $salary = Salary::where('user_id', $user->id)->first();

        $earlyLeaveDeductionAmount = $salary
            ? round($earlyLeaveMinutes * (float) $salary->late_rate)
            : 0;

        $overtimePayAmount = $salary
            ? round($overtimeMinutes * (float) $salary->overtime_rate)
            : 0;

        $photoPath = $this->savePhoto($request, 'photo');

        $attendance->update([
            'check_out_time'               => $now->toTimeString(),
            'check_out_photo'              => $photoPath,
            'check_out_lat'                => $request->input('lat'),  // ✅ simpan GPS
            'check_out_lng'                => $request->input('lng'),  // ✅ simpan GPS
            'checkout_status'              => $checkoutStatus,
            'early_leave_minutes'          => $earlyLeaveMinutes,
            'overtime_minutes'             => $overtimeMinutes,
            'early_leave_deduction_amount' => $earlyLeaveDeductionAmount,
            'overtime_pay_amount'          => $overtimePayAmount,
        ]);

        // Auto recalculate payroll
        $this->recalculatePayroll($user->id, (int) Carbon::today()->month, (int) Carbon::today()->year);

        $message = match (true) {
            $isWeekend                        => "Check-out berhasil. Lembur akhir pekan {$overtimeMinutes} menit. Bonus: Rp " . number_format($overtimePayAmount, 0, ',', '.'),
            $checkoutStatus === 'early_leave' => "Check-out berhasil. Pulang cepat {$earlyLeaveMinutes} menit.",
            $checkoutStatus === 'overtime'    => "Check-out berhasil. Lembur {$overtimeMinutes} menit. Bonus: Rp " . number_format($overtimePayAmount, 0, ',', '.'),
            default                           => 'Check-out berhasil. Tepat waktu!',
        };

        return response()->json(['message' => $message, 'data' => $attendance]);
    }

    // ── PRIVATE: Recalculate payroll ─────────────────────────────────────────
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

            $calc = PayrollController::calculatePayroll($userId, $month, $year);

            Payroll::updateOrCreate(
                ['user_id' => $userId, 'month' => $month, 'year' => $year],
                $calc
            );
        } catch (\Throwable $e) {
            \Log::error('Auto payroll recalculate error: ' . $e->getMessage(), [
                'user_id' => $userId, 'month' => $month, 'year' => $year,
            ]);
        }
    }

    // ── GET /api/attendance/today ────────────────────────────────────────────
    public function todayStatus(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['message' => 'Unauthorized'], 401);

        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', Carbon::today()->toDateString())
            ->first();

        return response()->json(['data' => $attendance]);
    }

    // ── GET /api/attendance/my-history ───────────────────────────────────────
    public function myHistory(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['message' => 'Unauthorized'], 401);

        $month = $request->month ?? Carbon::now()->month;
        $year  = $request->year  ?? Carbon::now()->year;

        $attendances = Attendance::where('user_id', $user->id)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->orderBy('date', 'desc')
            ->get();

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
}
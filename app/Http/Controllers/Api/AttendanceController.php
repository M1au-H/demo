<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AttendanceController extends Controller
{
    private function getUserFromToken(Request $request)
    {
        $header = $request->header('Authorization', '');
        $token  = preg_replace('/^(Token|Bearer)\s+/i', '', trim($header));
        return \App\Models\User::where('api_token', $token)->first();
    }

    // Simpan foto base64 ke storage/public/attendance-photos/
    private function savePhoto(?string $base64): ?string
    {
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

    // POST /api/attendance/check-in
    public function checkIn(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['message' => 'Unauthorized'], 401);

        $today = Carbon::today()->toDateString();

        if (Attendance::where('user_id', $user->id)->where('date', $today)->exists()) {
            return response()->json(['message' => 'Kamu sudah absen masuk hari ini'], 422);
        }

        $now         = Carbon::now();
        $shiftStart  = Carbon::today()->setTime(7, 0, 0);
        $status      = $now->greaterThan($shiftStart) ? 'late' : 'on_time';
        $lateMinutes = $status === 'late' ? (int) $now->diffInMinutes($shiftStart) : 0;

        $photoPath = $this->savePhoto($request->input('photo'));

        $attendance = Attendance::create([
            'user_id'        => $user->id,
            'date'           => $today,
            'check_in_time'  => $now->toTimeString(),
            'check_in_photo' => $photoPath,
            'status'         => $status,
            'late_minutes'   => $lateMinutes,
        ]);

        return response()->json([
            'message' => $status === 'late'
                ? "Check-in berhasil. Terlambat {$lateMinutes} menit."
                : 'Check-in berhasil. Tepat waktu! 👍',
            'data' => $attendance,
        ]);
    }

    // POST /api/attendance/check-out
    public function checkOut(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['message' => 'Unauthorized'], 401);

        $today = Carbon::today()->toDateString();

        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->whereNotNull('check_in_time')
            ->whereNull('check_out_time')
            ->first();

        if (!$attendance) {
            return response()->json(['message' => 'Kamu belum absen masuk hari ini'], 422);
        }

        $dayOfWeek    = Carbon::today()->dayOfWeek;
        $shiftEndHour = ($dayOfWeek === 5) ? 15 : 16;
        $shiftEnd     = Carbon::today()->setTime($shiftEndHour, 0, 0);

        $now             = Carbon::now();
        $checkoutStatus  = 'normal';
        $overtimeMinutes = 0;

        if ($now->lessThan($shiftEnd)) {
            $checkoutStatus = 'early_leave';
        } elseif ($now->greaterThan($shiftEnd)) {
            $checkoutStatus  = 'overtime';
            $overtimeMinutes = (int) $now->diffInMinutes($shiftEnd);
        }

        $photoPath = $this->savePhoto($request->input('photo'));

        $attendance->update([
            'check_out_time'   => $now->toTimeString(),
            'check_out_photo'  => $photoPath,
            'checkout_status'  => $checkoutStatus,
            'overtime_minutes' => $overtimeMinutes,
        ]);

        $msg = match($checkoutStatus) {
            'overtime'    => "Check-out berhasil. Lembur {$overtimeMinutes} menit. Terima kasih! 💪",
            'early_leave' => 'Check-out berhasil. Kamu pulang lebih awal.',
            default       => 'Check-out berhasil. Sampai jumpa besok! 👋',
        };

        return response()->json(['message' => $msg, 'data' => $attendance]);
    }

    // GET /api/attendance/today
    public function todayStatus(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['message' => 'Unauthorized'], 401);

        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', Carbon::today()->toDateString())
            ->first();

        return response()->json(['data' => $attendance]);
    }

    // GET /api/attendance/my-history
    public function myHistory(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['message' => 'Unauthorized'], 401);

        $attendances = Attendance::where('user_id', $user->id)
            ->orderBy('date', 'desc')
            ->paginate(20);

        return response()->json($attendances);
    }
}
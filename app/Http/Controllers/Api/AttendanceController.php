<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AttendanceController extends Controller
{
    // ── Helper: ambil user dari token ──
    private function getUserFromToken(Request $request)
    {
        $header = $request->header('Authorization', '');
        $token  = preg_replace('/^(Token|Bearer)\s+/i', '', trim($header));
        return \App\Models\User::where('api_token', $token)->first();
    }

    // ── Helper: simpan foto ke storage/private ──
    private function storePhoto($file, string $type): string
    {
        $date     = Carbon::today();
        $folder   = "attendance/{$date->year}/{$date->month}/{$date->day}";
        $filename = "{$type}_" . uniqid() . '.' . $file->extension();
        return $file->storeAs($folder, $filename, 'private');
    }

    // POST /api/attendance/check-in
    public function checkIn(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['message' => 'Unauthorized'], 401);

        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png|max:2048',
        ]);

        $today = Carbon::today()->toDateString();

        // Cek sudah absen hari ini
        if (Attendance::where('user_id', $user->id)->where('date', $today)->exists()) {
            return response()->json(['message' => 'Kamu sudah absen masuk hari ini'], 422);
        }

        // Simpan foto
        $photoPath = $this->storePhoto($request->file('photo'), 'checkin');

        // Tentukan status (jam diambil dari server, bukan frontend)
        $now        = Carbon::now();
        $shiftStart = Carbon::today()->setTime(7, 0, 0);
        $status     = $now->greaterThan($shiftStart) ? 'late' : 'on_time';
        $lateMinutes = $status === 'late' ? $now->diffInMinutes($shiftStart) : 0;

        $attendance = Attendance::create([
            'user_id'        => $user->id,
            'date'           => $today,
            'check_in_time'  => $now->toTimeString(),
            'check_in_photo' => $photoPath,
            'status'         => $status,
            'late_minutes'   => $lateMinutes,
        ]);

        return response()->json([
            'message' => 'Check-in berhasil',
            'data'    => $attendance,
        ]);
    }

    // POST /api/attendance/check-out
    public function checkOut(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['message' => 'Unauthorized'], 401);

        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png|max:2048',
        ]);

        $today = Carbon::today()->toDateString();

        // Cek sudah check-in dan belum check-out
        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $today)
            ->whereNotNull('check_in_time')
            ->whereNull('check_out_time')
            ->first();

        if (!$attendance) {
            return response()->json(['message' => 'Kamu belum absen masuk hari ini'], 422);
        }

        // Jam shift berdasarkan hari (5 = Jumat)
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
            $overtimeMinutes = $now->diffInMinutes($shiftEnd);
        }

        // Simpan foto
        $photoPath = $this->storePhoto($request->file('photo'), 'checkout');

        $attendance->update([
            'check_out_time'   => $now->toTimeString(),
            'check_out_photo'  => $photoPath,
            'checkout_status'  => $checkoutStatus,
            'overtime_minutes' => $overtimeMinutes,
        ]);

        return response()->json([
            'message' => 'Check-out berhasil',
            'data'    => $attendance,
        ]);
    }

    // GET /api/attendance/today
    public function todayStatus(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['message' => 'Unauthorized'], 401);

        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', Carbon::today()->toDateString())
            ->first();

        return response()->json([
            'data' => $attendance,
        ]);
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

    // GET /api/attendance/photo/{attendanceId}/{type}
    public function servePhoto(Request $request, $attendanceId, $type)
    {
        $user       = $this->getUserFromToken($request);
        $attendance = Attendance::findOrFail($attendanceId);

        // Hanya admin atau pemilik yang boleh lihat foto
        if (!$user || ($user->role !== 'admin' && $user->id !== $attendance->user_id)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $field = $type === 'in' ? 'check_in_photo' : 'check_out_photo';
        $path  = $attendance->$field;

        if (!$path || !Storage::disk('private')->exists($path)) {
            return response()->json(['message' => 'Foto tidak ditemukan'], 404);
        }

        return response()->file(Storage::disk('private')->path($path));
    }
}
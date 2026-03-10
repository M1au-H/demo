<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminAttendanceController extends Controller
{
    // GET /api/admin/attendance/today
    public function today()
    {
        $today = Carbon::today()->toDateString();

        $attendances = Attendance::with(['user:id,name,avatar,position_id', 'user.position:id,name'])
            ->where('date', $today)
            ->orderBy('check_in_time', 'asc')
            ->get()
            ->map(fn($a) => [
                'id'               => $a->id,
                'date'             => $a->date,
                'check_in_time'    => $a->check_in_time,
                'check_out_time'   => $a->check_out_time,
                'check_in_photo'   => $a->check_in_photo,
                'check_out_photo'  => $a->check_out_photo,
                'status'           => $a->status,
                'checkout_status'  => $a->checkout_status,
                'late_minutes'     => $a->late_minutes    ?? 0,
                'overtime_minutes' => $a->overtime_minutes ?? 0,
                'user' => [
                    'id'       => $a->user->id,
                    'name'     => $a->user->name,
                    'avatar'   => $a->user->avatar ? url('storage/' . $a->user->avatar) : null,
                    'position' => ['name' => $a->user->position?->name],
                ],
            ]);

        $summary = [
            'total_hadir'        => $attendances->count(),
            'total_terlambat'    => $attendances->where('status', 'late')->count(),
            'total_lembur'       => $attendances->where('checkout_status', 'overtime')->count(),
            'total_pulang_cepat' => $attendances->where('checkout_status', 'early_leave')->count(),
        ];

        return response()->json([
            'data'    => $attendances,
            'summary' => $summary,
        ]);
    }

    // GET /api/admin/attendance/monthly?month=3&year=2026
    public function monthly(Request $request)
    {
        $month = $request->query('month', now()->month);
        $year  = $request->query('year',  now()->year);

        $attendances = Attendance::with(['user:id,name,avatar,position_id', 'user.position:id,name'])
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->orderBy('date', 'desc')
            ->orderBy('check_in_time', 'asc')
            ->get()
            ->map(fn($a) => [
                'id'               => $a->id,
                'date'             => $a->date,
                'check_in_time'    => $a->check_in_time,
                'check_out_time'   => $a->check_out_time,
                'check_in_photo'   => $a->check_in_photo,
                'check_out_photo'  => $a->check_out_photo,
                'status'           => $a->status,
                'checkout_status'  => $a->checkout_status,
                'late_minutes'     => $a->late_minutes    ?? 0,
                'overtime_minutes' => $a->overtime_minutes ?? 0,
                'user' => [
                    'id'       => $a->user->id,
                    'name'     => $a->user->name,
                    'avatar'   => $a->user->avatar ? url('storage/' . $a->user->avatar) : null,
                    'position' => ['name' => $a->user->position?->name],
                ],
            ]);

        return response()->json(['data' => $attendances]);
    }

    // PUT /api/admin/attendance/{id}
    public function edit(Request $request, $id)
    {
        $request->validate([
            'check_in_time'  => 'nullable|date_format:H:i:s',
            'check_out_time' => 'nullable|date_format:H:i:s',
            'status'         => 'nullable|in:late,on_time',
            'edit_note'      => 'nullable|string|max:255',
        ]);

        $attendance = Attendance::findOrFail($id);
        $attendance->update([
            'check_in_time'  => $request->check_in_time  ?? $attendance->check_in_time,
            'check_out_time' => $request->check_out_time ?? $attendance->check_out_time,
            'status'         => $request->status         ?? $attendance->status,
            'edit_note'      => $request->edit_note,
            'is_edited'      => true,
        ]);

        return response()->json(['message' => 'Data absensi berhasil diubah.', 'data' => $attendance]);
    }

    // GET /api/attendance/photo/{attendanceId}/{type}
    public function photo($attendanceId, $type)
    {
        $attendance = Attendance::findOrFail($attendanceId);

        $path = $type === 'in'
            ? $attendance->check_in_photo
            : $attendance->check_out_photo;

        if (!$path || !Storage::disk('public')->exists($path)) {
            return response()->json(['message' => 'Foto tidak ditemukan'], 404);
        }

        return response()->file(Storage::disk('public')->path($path));
    }
}
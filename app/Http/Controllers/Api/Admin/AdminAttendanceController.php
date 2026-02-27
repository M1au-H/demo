<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminAttendanceController extends Controller
{
    // GET /api/admin/attendance/today
    public function today()
    {
        $today = Carbon::today()->toDateString();

        $attendances = Attendance::where('date', $today)
            ->with('user:id,name,avatar,position_id', 'user.position:id,name')
            ->orderBy('check_in_time')
            ->get();

        $summary = [
            'total_hadir'        => $attendances->whereNotNull('check_in_time')->count(),
            'total_terlambat'    => $attendances->where('status', 'late')->count(),
            'total_lembur'       => $attendances->where('checkout_status', 'overtime')->count(),
            'total_pulang_cepat' => $attendances->where('checkout_status', 'early_leave')->count(),
        ];

        return response()->json([
            'summary' => $summary,
            'data'    => $attendances,
        ]);
    }

    // GET /api/admin/attendance/monthly?month=2&year=2026
    public function monthly(Request $request)
    {
        $month = $request->query('month', Carbon::now()->month);
        $year  = $request->query('year',  Carbon::now()->year);

        $attendances = Attendance::whereMonth('date', $month)
            ->whereYear('date', $year)
            ->with('user:id,name,avatar,position_id', 'user.position:id,name') // ← tambah avatar
            ->orderBy('date', 'desc')
            ->get();

        return response()->json(['data' => $attendances]);
    }

    // PUT /api/admin/attendance/{id}
    public function edit(Request $request, $id)
    {
        $request->validate([
            'check_in_time'   => 'nullable|date_format:H:i:s',
            'check_out_time'  => 'nullable|date_format:H:i:s',
            'status'          => 'nullable|in:on_time,late,absent',
            'checkout_status' => 'nullable|in:normal,early_leave,overtime',
            'edit_note'       => 'required|string',
        ]);

        $attendance = Attendance::findOrFail($id);
        $attendance->update([
            ...$request->only(['check_in_time', 'check_out_time', 'status', 'checkout_status']),
            'is_edited' => true,
            'edit_note' => $request->edit_note,
        ]);

        return response()->json([
            'message' => 'Absensi berhasil diupdate',
            'data'    => $attendance,
        ]);
    }
}
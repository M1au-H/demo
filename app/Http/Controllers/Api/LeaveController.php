<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Leave;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaveController extends Controller
{
    // Ajukan izin
    public function store(Request $request)
    {
        $request->validate([
            'date'   => 'required|date',
            'type'   => 'required|in:sakit,cuti,keluarga,mendadak',
            'reason' => 'nullable|string|max:500',
        ]);

        $user = Auth::user();

        // Cek duplikat
        $exists = Leave::where('user_id', $user->id)
                        ->where('date', $request->date)->exists();
        if ($exists) {
            return response()->json(['message' => 'Kamu sudah mengajukan izin untuk tanggal ini.'], 422);
        }

        $leave = Leave::create([
            'user_id' => $user->id,
            'date'    => $request->date,
            'type'    => $request->type,
            'reason'  => $request->reason,
        ]);

        $typeLabel = [
            'sakit'    => 'Sakit',
            'cuti'     => 'Cuti Tahunan',
            'keluarga' => 'Keperluan Keluarga',
            'mendadak' => 'Izin Mendadak',
        ][$request->type];

        // Buat notifikasi otomatis
        Notification::create([
            'user_id' => $user->id,
            'title'   => 'Izin Berhasil Dicatat',
            'message' => "Izin kamu ({$typeLabel}) untuk tanggal {$request->date} telah dicatat.",
            'type'    => 'success',
        ]);

        return response()->json(['message' => 'Izin berhasil dicatat.', 'data' => $leave], 201);
    }

    // Riwayat izin user
    public function myLeaves()
    {
        $leaves = Leave::where('user_id', Auth::id())
                        ->orderBy('date', 'desc')
                        ->get();
        return response()->json(['data' => $leaves]);
    }

    // Hapus izin (hanya jika tanggal belum lewat)
    public function destroy($id)
    {
        $leave = Leave::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
        if ($leave->date < now()->toDateString()) {
            return response()->json(['message' => 'Tidak bisa hapus izin yang sudah lewat.'], 422);
        }
        $leave->delete();
        return response()->json(['message' => 'Izin berhasil dibatalkan.']);
    }

    // Admin: lihat semua izin (filter by date/bulan)
public function adminIndex(Request $request)
{
    $query = Leave::with('user:id,name,job_title,avatar')
                  ->orderBy('date', 'desc');

    if ($request->date) {
        $query->where('date', $request->date);
    }

    if ($request->month && $request->year) {
        $query->whereMonth('date', $request->month)
              ->whereYear('date', $request->year);
    }

    $leaves = $query->get();

    return response()->json(['data' => $leaves]);
}
}
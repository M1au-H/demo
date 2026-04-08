<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Leave;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class LeaveController extends Controller
{
    // Maksimal izin per bulan
    const MAX_IZIN_PER_BULAN = 3;

    public function store(Request $request)
    {
        $request->validate([
            'date'         => 'required|date',
            'type'         => 'required|in:sakit,izin', // ← hanya 2 tipe
            'reason'       => 'nullable|string|max:500',
            'surat_dokter' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);
        return $this->createLeave(Auth::user(), $request);
    }

    public function storeByFace(Request $request)
    {
        $request->validate([
            'user_id'      => 'required|exists:users,id',
            'date'         => 'required|date',
            'type'         => 'required|in:sakit,izin', // ← hanya 2 tipe
            'reason'       => 'nullable|string|max:500',
            'surat_dokter' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);
        return $this->createLeave(User::findOrFail($request->user_id), $request);
    }

    public function myLeavesByFace($userId)
    {
        $leaves = Leave::where('user_id', $userId)
            ->orderBy('date', 'desc')->limit(20)->get()
            ->map(fn($l) => $this->formatLeave($l));
        return response()->json(['data' => $leaves]);
    }

    public function destroyByFace(Request $request, $id)
    {
        $request->validate(['user_id' => 'required|exists:users,id']);
        $leave = Leave::where('id', $id)->where('user_id', $request->user_id)->firstOrFail();
        if ($leave->date < now()->toDateString())
            return response()->json(['message' => 'Tidak bisa hapus izin yang sudah lewat.'], 422);
        if ($leave->surat_dokter) Storage::disk('public')->delete($leave->surat_dokter);
        $leave->delete();
        return response()->json(['message' => 'Izin berhasil dibatalkan.']);
    }

    public function myLeaves()
    {
        $leaves = Leave::where('user_id', Auth::id())
            ->orderBy('date', 'desc')->get()
            ->map(fn($l) => $this->formatLeave($l));

        // Hitung sisa kuota izin bulan ini
        $izinBulanIni = Leave::where('user_id', Auth::id())
            ->whereMonth('date', now()->month)
            ->whereYear('date',  now()->year)
            ->where('type', 'izin')
            ->count();

        return response()->json([
            'data'          => $leaves,
            'kuota_izin'    => self::MAX_IZIN_PER_BULAN,
            'izin_terpakai' => $izinBulanIni,
            'sisa_izin'     => max(0, self::MAX_IZIN_PER_BULAN - $izinBulanIni),
        ]);
    }

    public function destroy($id)
    {
        $leave = Leave::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
        if ($leave->date < now()->toDateString())
            return response()->json(['message' => 'Tidak bisa hapus izin yang sudah lewat.'], 422);
        if ($leave->surat_dokter) Storage::disk('public')->delete($leave->surat_dokter);
        $leave->delete();
        return response()->json(['message' => 'Izin berhasil dibatalkan.']);
    }

    public function adminIndex(Request $request)
    {
        $query = Leave::with('user:id,name,job_title,avatar')->orderBy('date', 'desc');
        if ($request->date) $query->where('date', $request->date);
        if ($request->month && $request->year)
            $query->whereMonth('date', $request->month)->whereYear('date', $request->year);

        $leaves = $query->get()->map(fn($l) => $this->formatLeave($l, true));

        // Hitung kuota per user bulan ini
        $month = $request->month ?? now()->month;
        $year  = $request->year  ?? now()->year;

        $kuotaPerUser = User::where('role', 'user')->get()->map(function($user) use ($month, $year) {
            $terpakai = Leave::where('user_id', $user->id)
                ->whereMonth('date', $month)
                ->whereYear('date',  $year)
                ->where('type', 'izin')
                ->count();
            return [
                'user_id'       => $user->id,
                'name'          => $user->name,
                'izin_terpakai' => $terpakai,
                'sisa_izin'     => max(0, self::MAX_IZIN_PER_BULAN - $terpakai),
                'kuota'         => self::MAX_IZIN_PER_BULAN,
            ];
        });

        return response()->json([
            'data'          => $leaves,
            'kuota_per_user'=> $kuotaPerUser,
        ]);
    }

    private function formatLeave($leave, $withUser = false)
    {
        $data = [
            'id'           => $leave->id,
            'user_id'      => $leave->user_id,
            'date'         => $leave->date,
            'type'         => $leave->type,
            'reason'       => $leave->reason,
            'surat_dokter' => $leave->surat_dokter
                ? url('storage/' . $leave->surat_dokter)
                : null,
            'created_at'   => $leave->created_at,
        ];

        if ($withUser && $leave->relationLoaded('user') && $leave->user) {
            $data['user'] = [
                'id'        => $leave->user->id,
                'name'      => $leave->user->name,
                'job_title' => $leave->user->job_title,
                'avatar'    => $leave->user->avatar
                    ? url('storage/' . $leave->user->avatar)
                    : null,
            ];
        }

        return $data;
    }

    private function createLeave($user, Request $request)
    {
        // Cek duplikat tanggal
        $exists = Leave::where('user_id', $user->id)->where('date', $request->date)->exists();
        if ($exists)
            return response()->json(['message' => 'Sudah ada izin untuk tanggal ini.'], 422);

        // Cek kuota izin (hanya untuk type = izin)
        if ($request->type === 'izin') {
            $date         = \Carbon\Carbon::parse($request->date);
            $izinBulanIni = Leave::where('user_id', $user->id)
                ->whereMonth('date', $date->month)
                ->whereYear('date',  $date->year)
                ->where('type', 'izin')
                ->count();

            if ($izinBulanIni >= self::MAX_IZIN_PER_BULAN) {
                return response()->json([
                    'message' => 'Kuota izin bulan ini sudah habis (maksimal ' . self::MAX_IZIN_PER_BULAN . 'x per bulan).',
                ], 422);
            }
        }

        // Upload surat dokter
        $suratDokterPath = null;
        if ($request->hasFile('surat_dokter') && $request->file('surat_dokter')->isValid()) {
            $suratDokterPath = $request->file('surat_dokter')->store(
                'surat-dokter/' . $user->id, 'public'
            );
        }

        $leave = Leave::create([
            'user_id'      => $user->id,
            'date'         => $request->date,
            'type'         => $request->type,
            'reason'       => $request->reason,
            'surat_dokter' => $suratDokterPath,
        ]);

        $typeLabel = ['sakit' => 'Sakit', 'izin' => 'Izin'][$request->type] ?? $request->type;

        Notification::create([
            'user_id' => $user->id,
            'title'   => 'Izin Berhasil Dicatat',
            'message' => "Izin kamu ({$typeLabel}) untuk tanggal {$request->date} telah dicatat.",
            'type'    => 'success',
        ]);

        return response()->json([
            'message' => 'Izin berhasil dicatat.',
            'data'    => $this->formatLeave($leave),
        ], 201);
    }
}
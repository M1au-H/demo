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
    // Maksimal cuti per bulan (otomatis approved)
    const MAX_CUTI_PER_BULAN = 3;

    // Jenis izin khusus (butuh approval admin)
    const IZIN_TYPES = [
        'duka_cita'                 => 'Duka Cita (Keluarga Meninggal)',
        'menikah'                   => 'Pernikahan',
        'melahirkan'                => 'Melahirkan / Persalinan',
        'menemani_istri_melahirkan' => 'Menemani Istri Melahirkan',
        'khitan'                    => 'Khitan Anak',
        'lainnya'                   => 'Keperluan Khusus Lainnya',
    ];

    // ── User: submit izin/cuti ──────────────────────────────────────────────
    public function store(Request $request)
    {
        $request->validate([
            'date'         => 'required|date',
            'type'         => 'required|in:sakit,izin,cuti',
            'cuti_type'    => 'required_if:type,izin|nullable|in:' . implode(',', array_keys(self::IZIN_TYPES)),
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
            'type'         => 'required|in:sakit,izin,cuti',
            'cuti_type'    => 'required_if:type,izin|nullable|in:' . implode(',', array_keys(self::IZIN_TYPES)),
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
        return response()->json(['message' => 'Berhasil dibatalkan.']);
    }

    public function myLeaves()
    {
        $leaves = Leave::where('user_id', Auth::id())
            ->orderBy('date', 'desc')->get()
            ->map(fn($l) => $this->formatLeave($l));

        $cutiBulanIni = Leave::where('user_id', Auth::id())
            ->whereMonth('date', now()->month)
            ->whereYear('date',  now()->year)
            ->where('type', 'cuti')
            ->count();

        return response()->json([
            'data'          => $leaves,
            'kuota_cuti'    => self::MAX_CUTI_PER_BULAN,
            'cuti_terpakai' => $cutiBulanIni,
            'sisa_cuti'     => max(0, self::MAX_CUTI_PER_BULAN - $cutiBulanIni),
            'izin_types'    => self::IZIN_TYPES,
        ]);
    }

    public function destroy($id)
    {
        $leave = Leave::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
        if ($leave->date < now()->toDateString())
            return response()->json(['message' => 'Tidak bisa hapus izin yang sudah lewat.'], 422);
        if ($leave->surat_dokter) Storage::disk('public')->delete($leave->surat_dokter);
        $leave->delete();
        return response()->json(['message' => 'Berhasil dibatalkan.']);
    }

    // ── Admin: list semua izin & cuti ──────────────────────────────────────
    public function adminIndex(Request $request)
    {
        $query = Leave::with('user:id,name,job_title,avatar')->orderBy('date', 'desc');
        if ($request->date)  $query->where('date',   $request->date);
        if ($request->month && $request->year)
            $query->whereMonth('date', $request->month)->whereYear('date', $request->year);
        if ($request->type)   $query->where('type',   $request->type);
        if ($request->status) $query->where('status', $request->status);

        $leaves = $query->get()->map(fn($l) => $this->formatLeave($l, true));

        $month = $request->month ?? now()->month;
        $year  = $request->year  ?? now()->year;

        $kuotaPerUser = User::where('role', 'user')->get()->map(function ($user) use ($month, $year) {
            $cutiTerpakai = Leave::where('user_id', $user->id)
                ->whereMonth('date', $month)->whereYear('date', $year)
                ->where('type', 'cuti')
                ->count();

            $izinPending = Leave::where('user_id', $user->id)
                ->whereMonth('date', $month)->whereYear('date', $year)
                ->where('type', 'izin')
                ->where('status', 'pending')
                ->count();

            return [
                'user_id'       => $user->id,
                'name'          => $user->name,
                'cuti_terpakai' => $cutiTerpakai,
                'sisa_cuti'     => max(0, self::MAX_CUTI_PER_BULAN - $cutiTerpakai),
                'kuota'         => self::MAX_CUTI_PER_BULAN,
                'izin_pending'  => $izinPending,
            ];
        });

        return response()->json([
            'data'           => $leaves,
            'kuota_per_user' => $kuotaPerUser,
            'izin_types'     => self::IZIN_TYPES,
        ]);
    }

    // ── Admin: approve / reject IZIN saja ─────────────────────────────────
    public function adminUpdateStatus(Request $request, $id)
    {
        $request->validate([
            'status'     => 'required|in:approved,rejected',
            'admin_note' => 'nullable|string|max:255',
        ]);

        $leave = Leave::with('user')->findOrFail($id);

        // Hanya izin yang butuh approval — cuti & sakit otomatis
        if ($leave->type !== 'izin') {
            return response()->json([
                'message' => 'Hanya izin yang memerlukan persetujuan admin. Cuti dan sakit langsung approved otomatis.',
            ], 422);
        }

        // Cegah double approve/reject
        if ($leave->status !== 'pending') {
            return response()->json([
                'message' => 'Pengajuan ini sudah diproses sebelumnya (status: ' . $leave->status . ').',
            ], 422);
        }

        $leave->update([
            'status'     => $request->status,
            'admin_note' => $request->admin_note,
        ]);

        $statusLabel = $request->status === 'approved' ? 'disetujui' : 'ditolak';
        $notifType   = $request->status === 'approved' ? 'success' : 'error';

        Notification::create([
            'user_id' => $leave->user_id,
            'title'   => 'Pengajuan Izin ' . ucfirst($statusLabel),
            'message' => "Pengajuan izin kamu untuk tanggal {$leave->date} telah {$statusLabel}."
                       . ($request->admin_note ? " Catatan: {$request->admin_note}" : ''),
            'type'    => $notifType,
        ]);

        return response()->json([
            'message' => "Izin berhasil {$statusLabel}.",
            'data'    => $this->formatLeave($leave->fresh(['user']), true),
        ]);
    }

    // ── Format response ────────────────────────────────────────────────────
    private function formatLeave($leave, $withUser = false)
    {
        $izinTypes = self::IZIN_TYPES;
        $data = [
            'id'              => $leave->id,
            'user_id'         => $leave->user_id,
            'date'            => $leave->date,
            'type'            => $leave->type,
            'cuti_type'       => $leave->cuti_type,
            'cuti_type_label' => $leave->cuti_type ? ($izinTypes[$leave->cuti_type] ?? $leave->cuti_type) : null,
            'status'          => $leave->status,
            'admin_note'      => $leave->admin_note,
            'reason'          => $leave->reason,
            'surat_dokter'    => $leave->surat_dokter
                ? url('storage/' . $leave->surat_dokter)
                : null,
            'created_at'      => $leave->created_at,
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

    // ── Buat izin/cuti baru ────────────────────────────────────────────────
    private function createLeave($user, Request $request)
    {
        // Cek duplikat tanggal
        $exists = Leave::where('user_id', $user->id)->where('date', $request->date)->exists();
        if ($exists)
            return response()->json(['message' => 'Sudah ada izin/cuti untuk tanggal ini.'], 422);

        $date = \Carbon\Carbon::parse($request->date);

        // Cek kuota cuti (maks 3x/bulan, otomatis approved)
        if ($request->type === 'cuti') {
            $cutiBulanIni = Leave::where('user_id', $user->id)
                ->whereMonth('date', $date->month)
                ->whereYear('date',  $date->year)
                ->where('type', 'cuti')
                ->count();

            if ($cutiBulanIni >= self::MAX_CUTI_PER_BULAN) {
                return response()->json([
                    'message' => 'Kuota cuti bulan ini sudah habis (maksimal ' . self::MAX_CUTI_PER_BULAN . '× per bulan). Hubungi admin jika ada keperluan mendesak.',
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

        // LOGIKA STATUS:
        // - sakit → approved otomatis
        // - cuti  → approved otomatis (kuota sudah dicek di atas)
        // - izin  → pending, butuh approval admin
        $status = $request->type === 'izin' ? 'pending' : 'approved';

        $leave = Leave::create([
            'user_id'      => $user->id,
            'date'         => $request->date,
            'type'         => $request->type,
            'cuti_type'    => $request->type === 'izin' ? $request->cuti_type : null,
            'status'       => $status,
            'reason'       => $request->reason,
            'surat_dokter' => $suratDokterPath,
        ]);

        $typeLabel     = ['sakit' => 'Sakit', 'cuti' => 'Cuti', 'izin' => 'Izin'][$request->type] ?? $request->type;
        $needsApproval = $request->type === 'izin';

        $msg = $needsApproval
            ? "Pengajuan izin untuk tanggal {$request->date} sedang menunggu persetujuan admin."
            : "Kamu telah mencatat {$typeLabel} untuk tanggal {$request->date}.";

        Notification::create([
            'user_id' => $user->id,
            'title'   => $needsApproval ? 'Pengajuan Izin Dikirim' : ucfirst($typeLabel) . ' Dicatat',
            'message' => $msg,
            'type'    => $needsApproval ? 'info' : 'success',
        ]);

        return response()->json([
            'message' => $needsApproval
                ? 'Pengajuan izin berhasil dikirim. Menunggu persetujuan admin.'
                : ucfirst($typeLabel) . ' berhasil dicatat.',
            'data'    => $this->formatLeave($leave),
        ], 201);
    }
}
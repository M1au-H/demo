<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\KpiScore;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AdditionalTask;
use App\Models\PerformanceReview;
use App\Models\Leave;
use App\Models\Salary;
use Illuminate\Http\Request;
use Carbon\Carbon;

class KpiController extends Controller
{
    // ── GET /api/admin/kpi?month=&year= ──
    public function index(Request $request)
    {
        $month = $request->month ?? now()->month;
        $year  = $request->year  ?? now()->year;

        $kpis = KpiScore::with('user.position')
            ->where('month', $month)
            ->where('year',  $year)
            ->get()
            ->map(fn($k) => $this->format($k));

        return response()->json(['data' => $kpis]);
    }

    // ── POST /api/admin/kpi/calculate-all ──
    public function calculateAll(Request $request)
    {
        $month = $request->month ?? now()->month;
        $year  = $request->year  ?? now()->year;

        $users = User::with(['position', 'salary'])
            ->where('role', 'user')
            ->get();

        $results = [];
        foreach ($users as $user) {
            $results[] = $this->format(
                $this->doCalculate($user, $month, $year)
            );
        }

        return response()->json([
            'message' => 'KPI berhasil dihitung untuk ' . count($results) . ' karyawan',
            'data'    => $results,
        ]);
    }

    // ── PUT /api/admin/kpi/{userId}/rating ──
    public function updateRating(Request $request, int $userId)
    {
        $request->validate([
            'month'        => 'required|integer|min:1|max:12',
            'year'         => 'required|integer|min:2000',
            'admin_rating' => 'nullable|numeric|min:0|max:100',
            'task_score'   => 'nullable|numeric|min:0|max:100',
            'notes'        => 'nullable|string|max:500',
        ]);

        $kpi = KpiScore::where('user_id', $userId)
            ->where('month', $request->month)
            ->where('year',  $request->year)
            ->firstOrFail();

        $adminRating = $request->admin_rating ?? $kpi->admin_rating;
        $taskScore   = $request->task_score   ?? $kpi->task_score;

        if ($adminRating !== null) {
            $finalKpi = round(($kpi->attendance_score + $taskScore + $adminRating) / 3, 2);
        } else {
            $finalKpi = round(($kpi->attendance_score + $taskScore) / 2, 2);
        }

        $achievement = KpiScore::calcAchievement($finalKpi, $kpi->kpi_target);

        $salary    = Salary::where('user_id', $userId)->first();
        $base      = $salary->base_salary        ?? 0;
        $allowance = $salary->position_allowance ?? 0;

        $kpi->update([
            'admin_rating'   => $adminRating,
            'task_score'     => $taskScore,
            'notes'          => $request->notes,
            'final_kpi'      => $finalKpi,
            'achievement'    => $achievement,
            'kpi_grade'      => KpiScore::calcGrade($achievement),
            'kpi_adjustment' => KpiScore::calcAdjustment($achievement, $base, $allowance),
            'rated_by'       => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Berhasil disimpan',
            'data'    => $this->format($kpi->fresh('user.position')),
        ]);
    }

    // ── GET /api/kpi/my ──
    public function myKpi(Request $request)
    {
        $month = $request->month ?? now()->month;
        $year  = $request->year  ?? now()->year;

        $kpi = KpiScore::where('user_id', auth()->id())
            ->where('month', $month)
            ->where('year',  $year)
            ->first();

        if (!$kpi) {
            return response()->json(['message' => 'KPI belum dihitung untuk bulan ini'], 404);
        }

        return response()->json(['data' => $this->format($kpi)]);
    }

    // ── PRIVATE: hitung KPI 1 user ──────────────────────────────────────
    private function doCalculate(User $user, int $month, int $year): KpiScore
    {
        // ── 1. Attendance Score ──────────────────────────────────────────
        $workDays = $this->countWorkDays($month, $year);

        // Hadir tepat waktu
        $onTimeCount = Attendance::where('user_id', $user->id)
            ->whereMonth('date', $month)->whereYear('date', $year)
            ->where('status', 'on_time')
            ->count();

        // Hadir terlambat
        $lateCount = Attendance::where('user_id', $user->id)
            ->whereMonth('date', $month)->whereYear('date', $year)
            ->where('status', 'late')
            ->count();

        // Izin bulan ini (semua type izin)
        $izinCount = Leave::where('user_id', $user->id)
            ->whereMonth('date', $month)->whereYear('date', $year)
            ->where('type', 'izin')
            ->count();

        // Sakit dengan surat dokter
        $sakitDenganSurat = Leave::where('user_id', $user->id)
            ->whereMonth('date', $month)->whereYear('date', $year)
            ->where('type', 'sakit')
            ->whereNotNull('surat_dokter')
            ->count();

        // Sakit tanpa surat dokter
        $sakitTanpaSurat = Leave::where('user_id', $user->id)
            ->whereMonth('date', $month)->whereYear('date', $year)
            ->where('type', 'sakit')
            ->whereNull('surat_dokter')
            ->count();

        // Izin maksimal 3x dihitung penuh, lebih dari itu tidak dihitung
        $izinValid = min($izinCount, 3);

        // Hitung total poin kehadiran:
        // on_time           = 1.0 poin per hari
        // late              = 0.5 poin per hari
        // izin (maks 3)     = 1.0 poin per hari
        // sakit + surat     = 1.0 poin per hari
        // sakit tanpa surat = 0.5 poin per hari
        // alpha             = 0.0 poin
        $totalPoin = ($onTimeCount * 1.0)
                   + ($lateCount   * 0.5)
                   + ($izinValid   * 1.0)
                   + ($sakitDenganSurat * 1.0)
                   + ($sakitTanpaSurat  * 0.5);

        $attendanceScore = $workDays > 0
            ? round(min(100, ($totalPoin / $workDays) * 100), 2)
            : 0;

        // ── 2. Task Score ────────────────────────────────────────────────
        $existing = KpiScore::where('user_id', $user->id)
            ->where('month', $month)->where('year', $year)->first();

        $assigned  = AdditionalTask::where('user_id', $user->id)
            ->whereMonth('created_at', $month)->whereYear('created_at', $year)
            ->count();
        $completed = AdditionalTask::where('user_id', $user->id)
            ->whereMonth('created_at', $month)->whereYear('created_at', $year)
            ->where('status', 'completed')->count();

        if ($assigned > 0) {
            // Ada tugas → hitung otomatis dari rasio selesai
            $taskScore = round(($completed / $assigned) * 100, 2);
        } elseif ($existing && $existing->task_score > 0) {
            // Tidak ada tugas tapi admin sudah input manual → pertahankan
            $taskScore = $existing->task_score;
        } else {
            // Default 0 — admin yang tentukan
            $taskScore = 0;
        }

        // ── 3. Admin Rating dari PerformanceReview ───────────────────────
        // Rating skala 1-5 → konversi ke 0-100 (× 20)
        $review = PerformanceReview::where('user_id', $user->id)
            ->whereMonth('review_date', $month)
            ->whereYear('review_date', $year)
            ->orderByDesc('created_at')
            ->first();

        if ($review) {
            $adminRating = round(($review->rating / 5) * 100, 2);
        } elseif ($existing && $existing->admin_rating !== null) {
            $adminRating = $existing->admin_rating;
        } else {
            $adminRating = null; // belum ada penilaian
        }

        // ── 4. Final KPI ─────────────────────────────────────────────────
        $kpiTarget = $user->position->kpi_target ?? 100;

        if ($adminRating !== null) {
            // 3 komponen lengkap
            $finalKpi = round(($attendanceScore + $taskScore + $adminRating) / 3, 2);
        } else {
            // Rating belum ada → hitung dari 2 komponen
            $finalKpi = round(($attendanceScore + $taskScore) / 2, 2);
        }

        $achievement = KpiScore::calcAchievement($finalKpi, $kpiTarget);

        // ── 5. Salary adjustment ─────────────────────────────────────────
        $salary    = Salary::where('user_id', $user->id)->first();
        $base      = $salary->base_salary        ?? 0;
        $allowance = $salary->position_allowance ?? 0;

        return KpiScore::updateOrCreate(
            ['user_id' => $user->id, 'month' => $month, 'year' => $year],
            [
                'attendance_score' => $attendanceScore,
                'task_score'       => $taskScore,
                'admin_rating'     => $adminRating,
                'final_kpi'        => $finalKpi,
                'kpi_target'       => $kpiTarget,
                'achievement'      => $achievement,
                'kpi_grade'        => KpiScore::calcGrade($achievement),
                'kpi_adjustment'   => KpiScore::calcAdjustment($achievement, $base, $allowance),
            ]
        );
    }

    // ── Format response ──────────────────────────────────────────────────
    private function format(KpiScore $k): array
    {
        return [
            'id'               => $k->id,
            'user_id'          => $k->user_id,
            'name'             => $k->user->name           ?? '-',
            'position'         => $k->user->position->name ?? '-',
            'attendance_score' => $k->attendance_score,
            'task_score'       => $k->task_score,
            'admin_rating'     => $k->admin_rating,
            'final_kpi'        => $k->final_kpi,
            'kpi_target'       => $k->kpi_target,
            'achievement'      => $k->achievement,
            'kpi_grade'        => $k->kpi_grade,
            'kpi_adjustment'   => $k->kpi_adjustment,
            'notes'            => $k->notes,
        ];
    }

    // ── Hitung hari kerja Senin–Sabtu ────────────────────────────────────
    private function countWorkDays(int $month, int $year): int
    {
        $start = Carbon::create($year, $month, 1);
        $end   = $start->copy()->endOfMonth();
        $count = 0;
        while ($start->lte($end)) {
            if (!$start->isSunday()) $count++;
            $start->addDay();
        }
        return $count;
    }
}
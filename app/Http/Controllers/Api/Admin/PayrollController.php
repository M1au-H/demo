<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use App\Models\Salary;
use App\Models\User;
use App\Models\Attendance;
use App\Models\KpiScore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PayrollController extends Controller
{
    private function authUser(Request $request): ?User
    {
        $token = preg_replace('/^(Token|Bearer)\s+/i', '', $request->header('Authorization', ''));
        return $token ? User::where('api_token', $token)->first() : null;
    }

    private function countWorkdays(int $month, int $year): int
    {
        $start   = Carbon::create($year, $month, 1);
        $end     = $start->copy()->endOfMonth();
        $count   = 0;
        $current = $start->copy();
        while ($current->lte($end)) {
            if ($current->isWeekday()) $count++;
            $current->addDay();
        }
        return $count ?: 1;
    }

    // ── Core: hitung payroll 1 pegawai ───────────────────────────────────
    public static function calculatePayroll(int $userId, int $month, int $year): array
    {
        $salary = Salary::where('user_id', $userId)->first();
        if (!$salary) {
            $salary = new Salary([
                'base_salary' => 0, 'position_allowance' => 0,
                'overtime_rate' => 0, 'late_rate' => 0,
            ]);
        }

        // ── Hitung hari kerja ────────────────────────────────────────────
        $start    = Carbon::create($year, $month, 1);
        $end      = $start->copy()->endOfMonth();
        $workdays = 0;
        $cur      = $start->copy();
        while ($cur->lte($end)) {
            if ($cur->isWeekday()) $workdays++;
            $cur->addDay();
        }
        $workdays = max($workdays, 1);

        // ── Absensi ──────────────────────────────────────────────────────
        $attendances = Attendance::where('user_id', $userId)
            ->whereYear('date',  $year)
            ->whereMonth('date', $month)
            ->whereNotNull('check_in_time')
            ->whereNotNull('check_out_time')
            ->get();

        $weekdayAtt = $attendances->filter(fn($a) => Carbon::parse($a->date)->isWeekday());
        $weekendAtt = $attendances->filter(fn($a) => Carbon::parse($a->date)->isWeekend());

        $totalHadir           = $weekdayAtt->count();
        $totalWeekendHadir    = $weekendAtt->count();
        $totalLateMinutes     = (int) $weekdayAtt->sum('late_minutes');
        $totalEarlyLeave      = (int) $weekdayAtt->sum('early_leave_minutes');
        $totalOvertimeMinutes = (int) $attendances->sum('overtime_minutes');

        // ── Gaji proporsional ────────────────────────────────────────────
        $dailyRate         = (float) $salary->base_salary / $workdays;
        $dailyAllowance    = (float) $salary->position_allowance / $workdays;
        $proportionalBase  = round($dailyRate      * $totalHadir);
        $proportionalAllow = round($dailyAllowance * $totalHadir);

        // ── Lembur & potongan ────────────────────────────────────────────
        $overtimePay    = round($totalOvertimeMinutes * (float) $salary->overtime_rate);
        $lateDeduction  = round($totalLateMinutes     * (float) $salary->late_rate);
        $earlyDeduction = round($totalEarlyLeave      * (float) $salary->late_rate);
        $totalDeduction = $lateDeduction + $earlyDeduction;

        // ── Bonus & sanksi manual ────────────────────────────────────────
        $existing          = Payroll::where('user_id', $userId)
            ->where('month', $month)->where('year', $year)->first();
        $manualBonus       = (float) ($existing->bonus              ?? 0);
        $sanctionDeduction = (float) ($existing->sanction_deduction ?? 0);

        // ── KPI Adjustment ───────────────────────────────────────────────
        $kpi           = KpiScore::where('user_id', $userId)
            ->where('month', $month)
            ->where('year',  $year)
            ->first();

        $kpiAdjustment  = $kpi ? (float) $kpi->kpi_adjustment : 0;
        $kpiBonus       = $kpiAdjustment > 0 ? $kpiAdjustment  : 0;
        $kpiDeduction   = $kpiAdjustment < 0 ? abs($kpiAdjustment) : 0;
        $totalBonus     = $manualBonus + $kpiBonus;

        // ── Total gaji ───────────────────────────────────────────────────
        $totalSalary = $proportionalBase
                     + $proportionalAllow
                     + $overtimePay
                     + $totalBonus
                     - $totalDeduction
                     - $sanctionDeduction
                     - $kpiDeduction;

        // Minimum gaji
        if ($totalHadir > 0 || $totalWeekendHadir > 0) {
            $totalSalary = max($totalSalary, 1000000);
        }

        // Bulatkan ke ratusan
        $totalSalary = (int) round($totalSalary / 100) * 100;

        return [
            'base_salary'               => (int) $proportionalBase,
            'position_allowance'        => (int) $proportionalAllow,
            'overtime_pay'              => (int) $overtimePay,
            'late_deduction'            => (int) $totalDeduction,
            'bonus'                     => (int) $totalBonus,
            'sanction_deduction'        => (int) $sanctionDeduction,
            'total_salary'              => (int) $totalSalary,
            'total_hadir'               => $totalHadir,
            'total_weekend_hadir'       => $totalWeekendHadir,
            'total_late_minutes'        => $totalLateMinutes,
            'total_early_leave_minutes' => $totalEarlyLeave,
            'total_overtime_minutes'    => $totalOvertimeMinutes,
            'workdays_in_month'         => $workdays,
            'status'                    => $existing->status ?? 'draft',
            'note'                      => $existing->note   ?? null,
            // ── Simpan snapshot KPI ke payroll ──
            'kpi_score'                 => $kpi ? (float) $kpi->final_kpi   : null,
            'kpi_target'                => $kpi ? (float) $kpi->kpi_target  : null,
            'kpi_achievement'           => $kpi ? (float) $kpi->achievement : null,
            'kpi_adjustment'            => (int) $kpiAdjustment,
        ];
    }

    // GET /admin/payroll?month=&year=
    public function index(Request $request)
    {
        $month = (int) ($request->month ?? now()->month);
        $year  = (int) ($request->year  ?? now()->year);

        $payrolls = Payroll::with('user:id,name,job_title,avatar')
            ->where('month', $month)
            ->where('year',  $year)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($p) => $this->formatPayroll($p));

        $summary = [
            'total_pegawai' => $payrolls->count(),
            'total_gaji'    => $payrolls->sum('total_salary'),
            'total_draft'   => $payrolls->where('status', 'draft')->count(),
            'total_paid'    => $payrolls->where('status', 'paid')->count(),
        ];

        return response()->json(['data' => $payrolls, 'summary' => $summary]);
    }

    // POST /admin/payroll/generate
    public function generate(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year'  => 'required|integer|min:2020',
        ]);

        $month     = (int) $request->month;
        $year      = (int) $request->year;
        $employees = User::where('role', 'user')->get();

        if ($employees->isEmpty()) {
            return response()->json(['message' => 'Tidak ada pegawai ditemukan.'], 422);
        }

        DB::beginTransaction();
        try {
            foreach ($employees as $emp) {
                $existing = Payroll::where('user_id', $emp->id)
                    ->where('month', $month)->where('year', $year)->first();
                if ($existing && in_array($existing->status, ['approved', 'paid'])) continue;

                $calc = self::calculatePayroll($emp->id, $month, $year);

                Payroll::updateOrCreate(
                    ['user_id' => $emp->id, 'month' => $month, 'year' => $year],
                    $calc
                );
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Generate gagal: ' . $e->getMessage()], 500);
        }

        return response()->json([
            'message' => 'Payroll berhasil digenerate untuk ' . $employees->count() . ' pegawai.',
        ]);
    }

    // GET /admin/payroll/{id}
    public function show($id)
    {
        $p        = Payroll::with('user:id,name,job_title,avatar')->findOrFail($id);
        $data     = $this->formatPayroll($p);
        $workdays = $this->countWorkdays($p->month, $p->year);

        // ── Ambil KPI untuk detail slip ──────────────────────────────────
        $kpi    = KpiScore::where('user_id', $p->user_id)
            ->where('month', $p->month)
            ->where('year',  $p->year)
            ->first();
        $kpiAdj = $kpi ? (float) $kpi->kpi_adjustment : 0;

        $details = [];

        // Earnings
        if ($p->base_salary > 0)
            $details[] = [
                'type'        => 'earning',
                'description' => 'Gaji Pokok (' . ($p->total_hadir ?? 0) . '/' . $workdays . ' hari)',
                'amount'      => (float) $p->base_salary,
            ];
        if ($p->position_allowance > 0)
            $details[] = [
                'type'        => 'earning',
                'description' => 'Tunjangan Jabatan',
                'amount'      => (float) $p->position_allowance,
            ];
        if ($p->overtime_pay > 0)
            $details[] = [
                'type'        => 'earning',
                'description' => 'Lembur (' . ($p->total_overtime_minutes ?? 0) . ' mnt)',
                'amount'      => (float) $p->overtime_pay,
            ];

        // Bonus manual (dikurangi KPI bonus agar tidak double)
        $manualBonus = max(0, (float) $p->bonus - ($kpiAdj > 0 ? $kpiAdj : 0));
        if ($manualBonus > 0)
            $details[] = [
                'type'        => 'earning',
                'description' => 'Bonus Manual',
                'amount'      => $manualBonus,
            ];

        // KPI adjustment di slip (tanpa grade)
        if ($kpi && $kpiAdj > 0)
            $details[] = [
                'type'        => 'earning',
                'description' => 'Bonus KPI (Achievement ' . $kpi->achievement . '%)',
                'amount'      => $kpiAdj,
            ];
        elseif ($kpi && $kpiAdj < 0)
            $details[] = [
                'type'        => 'deduction',
                'description' => 'Potongan KPI (Achievement ' . $kpi->achievement . '%)',
                'amount'      => abs($kpiAdj),
            ];
        elseif ($kpi && $kpiAdj == 0)
            $details[] = [
                'type'        => 'info',
                'description' => 'KPI Normal (Achievement ' . $kpi->achievement . '%)',
                'amount'      => 0,
            ];

        // Deductions
        if ($p->late_deduction > 0)
            $details[] = [
                'type'        => 'deduction',
                'description' => 'Potongan Telat & Pulang Cepat',
                'amount'      => (float) $p->late_deduction,
            ];
        if ($p->sanction_deduction > 0)
            $details[] = [
                'type'        => 'deduction',
                'description' => 'Potongan Sanksi',
                'amount'      => (float) $p->sanction_deduction,
            ];

        $data['details']  = $details;
        $data['workdays'] = $workdays;
        $data['kpi']      = $kpi ? [
            'final_kpi'   => $kpi->final_kpi,
            'kpi_target'  => $kpi->kpi_target,
            'achievement' => $kpi->achievement,
            'adjustment'  => (int) $kpi->kpi_adjustment,
        ] : null;

        return response()->json(['data' => $data]);
    }

    // PUT /admin/payroll/{id}
    public function update(Request $request, $id)
    {
        $p = Payroll::findOrFail($id);
        if ($p->status !== 'draft') {
            return response()->json(['message' => 'Hanya payroll draft yang bisa diedit.'], 422);
        }

        $request->validate([
            'bonus'              => 'nullable|numeric|min:0',
            'sanction_deduction' => 'nullable|numeric|min:0',
            'note'               => 'nullable|string|max:500',
        ]);

        $p->update([
            'bonus'              => $request->bonus              ?? $p->bonus,
            'sanction_deduction' => $request->sanction_deduction ?? $p->sanction_deduction,
            'note'               => $request->note               ?? $p->note,
        ]);

        $calc = self::calculatePayroll($p->user_id, $p->month, $p->year);
        $p->update(['total_salary' => $calc['total_salary']]);

        return response()->json([
            'message' => 'Payroll diperbarui.',
            'data'    => $this->formatPayroll($p->fresh()),
        ]);
    }

    // POST /admin/payroll/{id}/approve
    public function approve($id)
    {
        $p = Payroll::findOrFail($id);
        if ($p->status !== 'draft') {
            return response()->json(['message' => 'Hanya payroll draft yang bisa diapprove.'], 422);
        }
        $p->update(['status' => 'approved']);
        return response()->json(['message' => 'Payroll diapprove.']);
    }

    // POST /admin/payroll/{id}/mark-paid
    public function markPaid($id)
    {
        $p = Payroll::findOrFail($id);
        if ($p->status !== 'approved') {
            return response()->json(['message' => 'Payroll harus diapprove dulu.'], 422);
        }
        $p->update(['status' => 'paid', 'paid_at' => now()]);
        return response()->json(['message' => 'Payroll ditandai lunas.']);
    }

    // DELETE /admin/payroll/{id}
    public function destroy($id)
    {
        $p = Payroll::findOrFail($id);
        if ($p->status === 'paid') {
            return response()->json(['message' => 'Payroll yang sudah lunas tidak bisa dihapus.'], 422);
        }
        $p->delete();
        return response()->json(['message' => 'Payroll dihapus.']);
    }

    // GET /admin/salary-settings
    public function salaryIndex()
    {
        $employees = User::where('role', 'user')->orderBy('name')->get()->map(function ($emp) {
            $salary = Salary::firstOrCreate(
                ['user_id' => $emp->id],
                ['base_salary' => 0, 'position_allowance' => 0, 'overtime_rate' => 0, 'late_rate' => 0]
            );
            return [
                'id'       => $emp->id,
                'name'     => $emp->name,
                'position' => $emp->job_title,
                'avatar'   => $emp->avatar,
                'salary'   => $salary,
            ];
        });

        return response()->json(['data' => $employees]);
    }

    // PUT /admin/salary-settings/{userId}
    public function salaryUpdate(Request $request, $userId)
    {
        $request->validate([
            'base_salary'        => 'required|numeric|min:0',
            'position_allowance' => 'required|numeric|min:0',
            'overtime_rate'      => 'required|numeric|min:0',
            'late_rate'          => 'required|numeric|min:0',
        ]);

        Salary::updateOrCreate(
            ['user_id' => $userId],
            $request->only(['base_salary', 'position_allowance', 'overtime_rate', 'late_rate'])
        );

        return response()->json(['message' => 'Pengaturan gaji disimpan.']);
    }

    // GET /user/payroll
    public function myPayroll(Request $request)
    {
        $user = $this->authUser($request);
        if (!$user) return response()->json(['message' => 'Unauthorized'], 401);

        $payrolls = Payroll::where('user_id', $user->id)
            ->orderByDesc('year')->orderByDesc('month')
            ->get()->map(fn($p) => $this->formatPayroll($p));

        return response()->json(['data' => $payrolls]);
    }

    // ── Format output ────────────────────────────────────────────────────
    private function formatPayroll(Payroll $p): array
    {
        return [
            'id'                        => $p->id,
            'user_id'                   => $p->user_id,
            'user_name'                 => $p->user?->name,
            'user_position'             => $p->user?->job_title,
            'user_avatar'               => $p->user?->avatar,
            'month'                     => $p->month,
            'year'                      => $p->year,
            'base_salary'               => (int) $p->base_salary,
            'position_allowance'        => (int) $p->position_allowance,
            'overtime_pay'              => (int) $p->overtime_pay,
            'late_deduction'            => (int) $p->late_deduction,
            'bonus'                     => (int) $p->bonus,
            'sanction_deduction'        => (int) $p->sanction_deduction,
            'total_salary'              => (int) $p->total_salary,
            'total_hadir'               => (int) $p->total_hadir,
            'total_late_minutes'        => (int) $p->total_late_minutes,
            'total_early_leave_minutes' => (int) $p->total_early_leave_minutes,
            'total_overtime_minutes'    => (int) $p->total_overtime_minutes,
            'workdays_in_month'         => (int) ($p->workdays_in_month ?? 0),
            'note'                      => $p->note,
            'status'                    => $p->status,
            // ── KPI snapshot dari payroll ──
            'kpi_score'                 => $p->kpi_score      ? (float) $p->kpi_score      : null,
            'kpi_target'                => $p->kpi_target     ? (float) $p->kpi_target     : null,
            'kpi_achievement'           => $p->kpi_achievement? (float) $p->kpi_achievement: null,
            'kpi_adjustment'            => (int) ($p->kpi_adjustment ?? 0),
        ];
    }
};
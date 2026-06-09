<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Payroll;
use App\Models\Salary;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollController extends Controller
{
    // ── GET /api/admin/payroll ────────────────────────────────────────────
    public function index(Request $request)
    {
        $month = (int) ($request->query('month', now()->month));
        $year  = (int) ($request->query('year',  now()->year));

        $payrolls = Payroll::with(['user:id,name,avatar,position_id', 'user.position:id,name'])
            ->where('month', $month)
            ->where('year',  $year)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($p) => [
                'id'                        => $p->id,
                'user_id'                   => $p->user_id,
                'user_name'                 => $p->user->name ?? '—',
                'user_avatar'               => $p->user->avatar ? url('storage/' . $p->user->avatar) : null,
                'position'                  => $p->user->position?->name ?? '—',
                'month'                     => $p->month,
                'year'                      => $p->year,
                'base_salary'               => (float) $p->base_salary,
                'position_allowance'        => (float) $p->position_allowance,
                'overtime_pay'              => (float) $p->overtime_pay,
                'late_deduction'            => (float) $p->late_deduction,
                'early_leave_deduction'     => (float) $p->early_leave_deduction,
                'total_salary'              => (float) $p->total_salary,
                'total_work_days'           => (int)   $p->total_work_days,
                'total_late_minutes'        => (int)   $p->total_late_minutes,
                'total_overtime_minutes'    => (int)   $p->total_overtime_minutes,
                'total_early_leave_minutes' => (int)   $p->total_early_leave_minutes,
                'status'                    => $p->status ?? 'draft',
                'note'                      => $p->note,
                'created_at'                => $p->created_at,
            ]);

        return response()->json(['data' => $payrolls]);
    }

    // ── POST /api/admin/payroll/generate ─────────────────────────────────
    public function generate(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year'  => 'required|integer|min:2020|max:2099',
        ]);

        $month = (int) $request->month;
        $year  = (int) $request->year;
        $users = User::where('role', 'user')->get();

        if ($users->isEmpty()) {
            return response()->json(['message' => 'Tidak ada pegawai yang terdaftar.'], 422);
        }

        $generated = 0;
        $skipped   = 0;

        DB::transaction(function () use ($users, $month, $year, &$generated, &$skipped) {
            foreach ($users as $user) {
                $existing = Payroll::where('user_id', $user->id)
                    ->where('month', $month)
                    ->where('year',  $year)
                    ->first();

                if ($existing && in_array($existing->status, ['approved', 'paid'])) {
                    $skipped++;
                    continue;
                }

                Salary::firstOrCreate(
                    ['user_id' => $user->id],
                    ['base_salary' => 0, 'position_allowance' => 0, 'overtime_rate' => 0, 'late_rate' => 0]
                );

                $calc = self::calculatePayroll($user->id, $month, $year);

                Payroll::updateOrCreate(
                    ['user_id' => $user->id, 'month' => $month, 'year' => $year],
                    array_merge($calc, ['status' => $existing?->status ?? 'draft'])
                );

                $generated++;
            }
        });

        return response()->json([
            'message'   => "Payroll berhasil di-generate untuk {$generated} pegawai." . ($skipped > 0 ? " {$skipped} pegawai dilewati (sudah approved/paid)." : ''),
            'generated' => $generated,
            'skipped'   => $skipped,
        ]);
    }

    // ── GET /api/admin/payroll/{id} ───────────────────────────────────────
    public function show($id)
    {
        $p = Payroll::with(['user:id,name,avatar,position_id', 'user.position:id,name'])->findOrFail($id);

        return response()->json(['data' => [
            'id'                        => $p->id,
            'user_id'                   => $p->user_id,
            'user_name'                 => $p->user->name ?? '—',
            'user_avatar'               => $p->user->avatar ? url('storage/' . $p->user->avatar) : null,
            'position'                  => $p->user->position?->name ?? '—',
            'month'                     => $p->month,
            'year'                      => $p->year,
            'base_salary'               => (float) $p->base_salary,
            'position_allowance'        => (float) $p->position_allowance,
            'overtime_pay'              => (float) $p->overtime_pay,
            'late_deduction'            => (float) $p->late_deduction,
            'early_leave_deduction'     => (float) $p->early_leave_deduction,
            'total_salary'              => (float) $p->total_salary,
            'total_work_days'           => (int)   $p->total_work_days,
            'total_late_minutes'        => (int)   $p->total_late_minutes,
            'total_overtime_minutes'    => (int)   $p->total_overtime_minutes,
            'total_early_leave_minutes' => (int)   $p->total_early_leave_minutes,
            'status'                    => $p->status ?? 'draft',
            'note'                      => $p->note,
            'created_at'                => $p->created_at,
        ]]);
    }

    // ── PUT /api/admin/payroll/{id} ───────────────────────────────────────
    public function update(Request $request, $id)
    {
        $payroll = Payroll::findOrFail($id);

        if (in_array($payroll->status, ['approved', 'paid'])) {
            return response()->json(['message' => 'Tidak bisa edit payroll yang sudah approved/paid.'], 422);
        }

        $payroll->update($request->only([
            'base_salary', 'position_allowance', 'overtime_pay',
            'late_deduction', 'early_leave_deduction', 'note',
        ]));

        $payroll->total_salary = max(0,
            $payroll->base_salary + $payroll->position_allowance
            + $payroll->overtime_pay - $payroll->late_deduction
            - $payroll->early_leave_deduction
        );
        $payroll->save();

        return response()->json(['message' => 'Payroll berhasil diupdate.', 'data' => $payroll]);
    }

    // ── POST /api/admin/payroll/{id}/approve ─────────────────────────────
    public function approve($id)
    {
        $payroll = Payroll::findOrFail($id);

        if ($payroll->status === 'paid') {
            return response()->json(['message' => 'Payroll sudah berstatus paid.'], 422);
        }

        $payroll->update(['status' => 'approved']);
        return response()->json(['message' => 'Payroll berhasil diapprove.', 'data' => $payroll]);
    }

    // ── POST /api/admin/payroll/{id}/mark-paid ───────────────────────────
    public function markPaid($id)
    {
        $payroll = Payroll::findOrFail($id);

        if ($payroll->status !== 'approved') {
            return response()->json(['message' => 'Payroll harus diapprove terlebih dahulu.'], 422);
        }

        $payroll->update(['status' => 'paid']);
        return response()->json(['message' => 'Payroll berhasil ditandai lunas.', 'data' => $payroll]);
    }

    // ── DELETE /api/admin/payroll/{id} ────────────────────────────────────
    public function destroy($id)
    {
        $payroll = Payroll::findOrFail($id);

        if (in_array($payroll->status, ['approved', 'paid'])) {
            return response()->json(['message' => 'Tidak bisa hapus payroll yang sudah approved/paid.'], 422);
        }

        $payroll->delete();
        return response()->json(['message' => 'Payroll berhasil dihapus.']);
    }

    // ── GET /api/admin/salary-settings ───────────────────────────────────
    public function salaryIndex(Request $request)
    {
        $search = $request->query('search', '');

        // Auto-create salary record untuk semua user yang belum punya
        $userIds = User::where('role', 'user')->pluck('id');
        foreach ($userIds as $uid) {
            Salary::firstOrCreate(
                ['user_id' => $uid],
                ['base_salary' => 0, 'position_allowance' => 0, 'overtime_rate' => 0, 'late_rate' => 0]
            );
        }

        $salaries = Salary::with(['user:id,name,avatar,position_id', 'user.position:id,name'])
            ->whereHas('user', function ($q) use ($search) {
                $q->where('role', 'user');
                if ($search) $q->where('name', 'like', "%{$search}%");
            })
            ->get()
            ->map(fn($s) => [
                'id'                 => $s->id,
                'user_id'            => $s->user_id,
                'user_name'          => $s->user->name ?? '—',
                'user_avatar'        => $s->user->avatar ? url('storage/' . $s->user->avatar) : null,
                'position'           => $s->user->position?->name ?? '—',
                'base_salary'        => (float) $s->base_salary,
                'position_allowance' => (float) $s->position_allowance,
                'overtime_rate'      => (float) $s->overtime_rate,
                'late_rate'          => (float) $s->late_rate,
            ]);

        return response()->json(['data' => $salaries]);
    }

    // ── PUT /api/admin/salary-settings/{userId} ──────────────────────────
    public function salaryUpdate(Request $request, $userId)
    {
        $request->validate([
            'base_salary'        => 'required|numeric|min:0',
            'position_allowance' => 'nullable|numeric|min:0',
            'overtime_rate'      => 'nullable|numeric|min:0',
            'late_rate'          => 'nullable|numeric|min:0',
        ]);

        $user   = User::findOrFail($userId);
        $salary = Salary::updateOrCreate(
            ['user_id' => $user->id],
            [
                'base_salary'        => $request->base_salary,
                'position_allowance' => $request->position_allowance ?? 0,
                'overtime_rate'      => $request->overtime_rate      ?? 0,
                'late_rate'          => $request->late_rate           ?? 0,
            ]
        );

        return response()->json(['message' => 'Pengaturan gaji berhasil disimpan.', 'data' => $salary]);
    }

    // ── GET /api/user/payroll ─────────────────────────────────────────────
    public function myPayroll(Request $request)
    {
        $header = $request->header('Authorization', '');
        $token  = preg_replace('/^(Token|Bearer)\s+/i', '', trim($header));
        $user   = User::where('api_token', $token)->first();

        if (!$user) return response()->json(['message' => 'Unauthorized'], 401);

        $payrolls = Payroll::where('user_id', $user->id)
            ->orderBy('year',  'desc')
            ->orderBy('month', 'desc')
            ->get()
            ->map(fn($p) => [
                'id'                        => $p->id,
                'month'                     => $p->month,
                'year'                      => $p->year,
                'base_salary'               => (float) $p->base_salary,
                'position_allowance'        => (float) $p->position_allowance,
                'overtime_pay'              => (float) $p->overtime_pay,
                'late_deduction'            => (float) $p->late_deduction,
                'early_leave_deduction'     => (float) $p->early_leave_deduction,
                'total_salary'              => (float) $p->total_salary,
                'total_work_days'           => (int)   $p->total_work_days,
                'total_late_minutes'        => (int)   $p->total_late_minutes,
                'total_overtime_minutes'    => (int)   $p->total_overtime_minutes,
                'total_early_leave_minutes' => (int)   $p->total_early_leave_minutes,
                'status'                    => $p->status ?? 'draft',
                'note'                      => $p->note,
            ]);

        return response()->json(['data' => $payrolls]);
    }

    // ── STATIC: Hitung payroll ────────────────────────────────────────────
    public static function calculatePayroll(int $userId, int $month, int $year): array
    {
        $salary = Salary::where('user_id', $userId)->first();

        $baseSalary        = $salary ? (float) $salary->base_salary        : 0;
        $positionAllowance = $salary ? (float) $salary->position_allowance : 0;
        $overtimeRate      = $salary ? (float) $salary->overtime_rate      : 0;
        $lateRate          = $salary ? (float) $salary->late_rate          : 0;

        $attendances = Attendance::where('user_id', $userId)
            ->whereMonth('date', $month)
            ->whereYear('date',  $year)
            ->whereNotNull('check_in_time')
            ->get();

        $totalWorkDays          = $attendances->count();
        $totalLateMinutes       = (int) $attendances->sum('late_minutes');
        $totalEarlyLeaveMinutes = (int) $attendances->sum('early_leave_minutes');
        $totalOvertimeMinutes   = (int) $attendances->sum('overtime_minutes');

        $lateDeduction       = round($totalLateMinutes       * $lateRate);
        $earlyLeaveDeduction = round($totalEarlyLeaveMinutes * $lateRate);
        $overtimePay         = round($totalOvertimeMinutes   * $overtimeRate);

        $totalSalary = max(0,
            $baseSalary + $positionAllowance + $overtimePay
            - $lateDeduction - $earlyLeaveDeduction
        );

        return [
            'user_id'                   => $userId,
            'month'                     => $month,
            'year'                      => $year,
            'base_salary'               => $baseSalary,
            'position_allowance'        => $positionAllowance,
            'overtime_pay'              => $overtimePay,
            'late_deduction'            => $lateDeduction,
            'early_leave_deduction'     => $earlyLeaveDeduction,
            'total_salary'              => $totalSalary,
            'total_work_days'           => $totalWorkDays,
            'total_late_minutes'        => $totalLateMinutes,
            'total_overtime_minutes'    => $totalOvertimeMinutes,
            'total_early_leave_minutes' => $totalEarlyLeaveMinutes,
        ];
    }
}
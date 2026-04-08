<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use Illuminate\Http\Request;

class UserPayrollController extends Controller
{
    // ── GET /api/payroll/my?month=&year= ────────────────────────────────────
    public function index(Request $request)
    {
        $user  = $request->user;
        $month = $request->month ?? now()->month;
        $year  = $request->year  ?? now()->year;

        $payrolls = Payroll::where('user_id', $user->id)
            ->whereIn('status', ['approved', 'paid'])
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get()
            ->map(fn($p) => $this->format($p));

        // Payroll bulan ini
        $current = Payroll::where('user_id', $user->id)
            ->where('month', $month)
            ->where('year',  $year)
            ->whereIn('status', ['approved', 'paid'])
            ->with('details')
            ->first();

        return response()->json([
            'current' => $current ? $this->format($current, true) : null,
            'history' => $payrolls,
        ]);
    }

    // ── GET /api/payroll/my/{id} ─────────────────────────────────────────────
    public function show(Request $request, $id)
    {
        $payroll = Payroll::where('user_id', $request->user->id)
            ->whereIn('status', ['approved', 'paid'])
            ->with('details')
            ->findOrFail($id);

        return response()->json(['data' => $this->format($payroll, true)]);
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function format(Payroll $p, bool $withDetails = false): array
    {
        $data = [
            'id'                        => $p->id,
            'month'                     => $p->month,
            'year'                      => $p->year,
            'base_salary'               => (float) $p->base_salary,
            'position_allowance'        => (float) $p->position_allowance,
            'overtime_pay'              => (float) $p->overtime_pay,
            'late_deduction'            => (float) $p->late_deduction,
            'bonus'                     => (float) $p->bonus,
            'sanction_deduction'        => (float) $p->sanction_deduction,
            'total_salary'              => (float) $p->total_salary,
            'total_hadir'               => (int)   $p->total_hadir,
            'total_late_minutes'        => (int)   $p->total_late_minutes,
            'total_early_leave_minutes' => (int)   $p->total_early_leave_minutes,
            'total_overtime_minutes'    => (int)   $p->total_overtime_minutes,
            'status'                    => $p->status,
            'note'                      => $p->note,
        ];

        if ($withDetails) {
            $data['details'] = $p->details->map(fn($d) => [
                'type'        => $d->type,
                'description' => $d->description,
                'amount'      => (float) $d->amount,
            ]);
        }

        return $data;
    }
}
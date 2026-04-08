<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Salary;
use App\Models\User;
use Illuminate\Http\Request;

class SalaryController extends Controller
{
    // ── GET /api/admin/salary-settings ──────────────────────────────────────
    // Daftar semua pegawai + setting gajinya
    public function index()
    {
        $users = User::where('role', 'user')
            ->with(['salary', 'position'])
            ->get()
            ->map(fn($u) => $this->format($u));

        return response()->json(['data' => $users]);
    }

    // ── GET /api/admin/salary-settings/{userId} ──────────────────────────────
    public function show($userId)
    {
        $user = User::with(['salary', 'position'])->findOrFail($userId);
        return response()->json(['data' => $this->format($user)]);
    }

    // ── PUT /api/admin/salary-settings/{userId} ──────────────────────────────
    public function update(Request $request, $userId)
    {
        $request->validate([
            'base_salary'        => 'required|numeric|min:0',
            'position_allowance' => 'required|numeric|min:0',
            'overtime_rate'      => 'required|numeric|min:0',
            'late_rate'          => 'required|numeric|min:0',
        ]);

        $user = User::findOrFail($userId);

        Salary::updateOrCreate(
            ['user_id' => $user->id],
            [
                'base_salary'        => $request->base_salary,
                'position_allowance' => $request->position_allowance,
                'overtime_rate'      => $request->overtime_rate,
                'late_rate'          => $request->late_rate,
            ]
        );

        $user->load('salary');
        return response()->json([
            'message' => 'Salary settings berhasil diperbarui.',
            'data'    => $this->format($user),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function format(User $u): array
    {
        return [
            'id'           => $u->id,
            'name'         => $u->name,
            'email'        => $u->email,
            'job_title'    => $u->job_title,
            'position'     => $u->position?->name,
            'salary' => [
                'base_salary'        => (float) ($u->salary?->base_salary        ?? 0),
                'position_allowance' => (float) ($u->salary?->position_allowance ?? 0),
                'overtime_rate'      => (float) ($u->salary?->overtime_rate      ?? 0),
                'late_rate'          => (float) ($u->salary?->late_rate          ?? 0),
            ],
        ];
    }
}
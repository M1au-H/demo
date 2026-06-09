<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Leave;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today()->toDateString();
        $month = now()->month;
        $year  = now()->year;

        // ── 1. Absensi hari ini — cache 3 menit ──────────────────────────────
        $todayAttendance = Cache::remember('dashboard_att_today', 180, function () use ($today) {
            return Attendance::with('user:id,name,job_title,avatar')
                ->whereDate('date', $today)
                ->select('id','user_id','date','check_in_time','check_out_time','status','late_minutes')
                ->get();
        });

        // ── 2. Leaves terbaru — cache 5 menit ───────────────────────────────
        $leaves = Cache::remember('dashboard_leaves', 300, function () {
            return Leave::with('user:id,name')
                ->orderBy('created_at', 'desc')
                ->select('id','user_id','date','type','status')
                ->limit(10)
                ->get();
        });

        // ── 3. Monthly attendance top — cache 10 menit ───────────────────────
        $monthlyTop = Cache::remember("dashboard_monthly_{$month}_{$year}", 600, function () use ($month, $year) {
            return Attendance::with('user:id,name')
                ->whereMonth('date', $month)
                ->whereYear('date',  $year)
                ->whereNotNull('check_in_time')
                ->select('user_id', DB::raw('COUNT(*) as total_days'))
                ->groupBy('user_id')
                ->orderByDesc('total_days')
                ->limit(5)
                ->get();
        });

        return response()->json([
            'data' => [
                'today_attendance' => $todayAttendance,
                'leaves'           => $leaves,
                'monthly_top'      => $monthlyTop,
            ]
        ]);
    }

    // Panggil ini setelah ada check-in/checkout untuk refresh cache
    public static function bustCache(): void
    {
        Cache::forget('dashboard_att_today');
        Cache::forget('dashboard_leaves');
    }
}
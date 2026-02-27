<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    private function getUser(Request $request): ?User
    {
        $token = $request->bearerToken();
        if (!$token) return null;
        return User::where('api_token', $token)->first();
    }

    public function index(Request $request)
    {
        $user = $this->getUser($request);
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $this->checkAttendanceNotifications($user);

        $notifications = Notification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(30)
            ->get();

        $unread = $notifications->where('is_read', false)->count();

        return response()->json([
            'data'   => $notifications,
            'unread' => $unread,
        ]);
    }

    public function read(Request $request, $id)
    {
        $user = $this->getUser($request);
        if (!$user) return response()->json(['message' => 'Unauthorized'], 401);

        $notif = Notification::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $notif->update(['is_read' => true]);

        return response()->json(['message' => 'OK']);
    }

    public function readAll(Request $request)
    {
        $user = $this->getUser($request);
        if (!$user) return response()->json(['message' => 'Unauthorized'], 401);

        Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['message' => 'Semua notifikasi ditandai dibaca.']);
    }

    private function checkAttendanceNotifications(User $user): void
    {
        $today     = Carbon::today();
        $todayStr  = $today->toDateString();
        $now       = Carbon::now();
        $dayOfWeek = $today->dayOfWeek;

        if ($dayOfWeek === 0) return;

        $endHour = ($dayOfWeek === 5) ? 15 : 16;

        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $todayStr)
            ->first();

        if ($now->hour >= 7 && (!$attendance || !$attendance->check_in_time)) {
            $exists = Notification::where('user_id', $user->id)
                ->where('category', 'attendance')
                ->where('title', 'Belum Absen Masuk')
                ->whereDate('created_at', $todayStr)
                ->exists();

            if (!$exists) {
                Notification::create([
                    'user_id'  => $user->id,
                    'type'     => 'warning',
                    'category' => 'attendance',
                    'title'    => 'Belum Absen Masuk',
                    'message'  => 'Kamu belum melakukan absen masuk hari ini. Segera absen sekarang!',
                    'is_read'  => false,
                ]);
            }
        }

        if ($now->hour >= $endHour && $attendance && $attendance->check_in_time && !$attendance->check_out_time) {
            $exists = Notification::where('user_id', $user->id)
                ->where('category', 'attendance')
                ->where('title', 'Belum Absen Pulang')
                ->whereDate('created_at', $todayStr)
                ->exists();

            if (!$exists) {
                Notification::create([
                    'user_id'  => $user->id,
                    'type'     => 'info',
                    'category' => 'attendance',
                    'title'    => 'Belum Absen Pulang',
                    'message'  => 'Shift kerja sudah selesai. Jangan lupa absen pulang!',
                    'is_read'  => false,
                ]);
            }
        }
    }
}
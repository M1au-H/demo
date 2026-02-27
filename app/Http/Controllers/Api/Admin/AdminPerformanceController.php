<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\PerformanceReview;
use App\Models\Sanction;
use App\Models\AdditionalTask;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Helpers\NotificationHelper;

class AdminPerformanceController extends Controller
{
    private function getAdmin(Request $request)
    {
        $header = $request->header('Authorization', '');
        $token  = preg_replace('/^(Token|Bearer)\s+/i', '', trim($header));
        return User::where('api_token', $token)->first();
    }

    // POST /api/admin/performance/{userId}
    public function store(Request $request, $userId)
    {
        $admin = $this->getAdmin($request);

        $request->validate([
            'rating'  => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        User::findOrFail($userId);

        $review = PerformanceReview::create([
            'user_id'     => $userId,
            'reviewed_by' => $admin->id,
            'review_date' => Carbon::today()->toDateString(),
            'rating'      => $request->rating,
            'comment'     => $request->comment,
        ]);

        // Kirim notifikasi ke pegawai
        NotificationHelper::performanceReview((int) $userId, $request->rating);

        return response()->json([
            'message' => 'Penilaian berhasil disimpan',
            'data'    => $review,
        ]);
    }

    // GET /api/admin/performance/{userId}
    public function userHistory($userId)
    {
        User::findOrFail($userId);

        $reviews = PerformanceReview::where('user_id', $userId)
            ->with('reviewer:id,name')
            ->orderBy('review_date', 'desc')
            ->paginate(20);

        return response()->json($reviews);
    }

    // POST /api/admin/sanction/{userId}
    public function giveSanction(Request $request, $userId)
    {
        $admin = $this->getAdmin($request);

        $request->validate([
            'type'   => 'required|in:warning,mandatory_overtime,disciplinary_note',
            'reason' => 'required|string|max:1000',
        ]);

        User::findOrFail($userId);

        $sanction = Sanction::create([
            'user_id'       => $userId,
            'given_by'      => $admin->id,
            'type'          => $request->type,
            'reason'        => $request->reason,
            'sanction_date' => Carbon::today()->toDateString(),
        ]);

        // Kirim notifikasi ke pegawai
        NotificationHelper::sanction((int) $userId, $request->type, $request->reason);

        return response()->json([
            'message' => 'Sanksi berhasil diberikan',
            'data'    => $sanction,
        ]);
    }

    // POST /api/admin/task/{userId}
    public function giveTask(Request $request, $userId)
    {
        $admin = $this->getAdmin($request);

        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date'    => 'nullable|date',
        ]);

        User::findOrFail($userId);

        $task = AdditionalTask::create([
            'user_id'     => $userId,
            'assigned_by' => $admin->id,
            'title'       => $request->title,
            'description' => $request->description,
            'due_date'    => $request->due_date,
            'status'      => 'pending',
        ]);

        // Kirim notifikasi ke pegawai
        NotificationHelper::additionalTask((int) $userId, $request->title, $request->due_date);

        return response()->json([
            'message' => 'Tugas berhasil diberikan',
            'data'    => $task,
        ]);
    }

    // PUT /api/admin/task/{taskId}/done
    public function markTaskDone($taskId)
    {
        $task = AdditionalTask::findOrFail($taskId);
        $task->update(['status' => 'done']);

        return response()->json([
            'message' => 'Tugas ditandai selesai',
            'data'    => $task,
        ]);
    }

    // GET /api/admin/employees
    public function employees()
    {
        $employees = User::where('role', 'user')
            ->with('position:id,name')
            ->select('id', 'name', 'email', 'avatar', 'position_id')
            ->get();

        return response()->json(['data' => $employees]);
    }
}
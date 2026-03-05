<?php
// VERSI TERBARU - sudah include NotificationHelper::sanctionCompleted & taskCompleted

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PerformanceReview;
use App\Models\Sanction;
use App\Models\AdditionalTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PerformanceController extends Controller
{
    private function getUserFromToken(Request $request)
    {
        $header = $request->header('Authorization', '');
        $token  = preg_replace('/^(Token|Bearer)\s+/i', '', trim($header));
        return \App\Models\User::where('api_token', $token)->first();
    }

    // GET /api/performance/my
    public function myPerformance(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['message' => 'Unauthorized'], 401);

        $reviews = PerformanceReview::where('user_id', $user->id)
            ->with('reviewer:id,name')
            ->orderBy('review_date', 'desc')
            ->paginate(20);

        return response()->json($reviews);
    }

    // DELETE /api/performance/{id}
    public function deleteReview(Request $request, $id)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['message' => 'Unauthorized'], 401);

        $review = PerformanceReview::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$review) {
            return response()->json(['message' => 'Penilaian tidak ditemukan'], 404);
        }

        $review->delete();

        return response()->json(['message' => 'Penilaian berhasil dihapus']);
    }

    // GET /api/sanctions/my
    public function mySanctions(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['message' => 'Unauthorized'], 401);

        $sanctions = Sanction::where('user_id', $user->id)
            ->with('giver:id,name')
            ->orderBy('sanction_date', 'desc')
            ->get()
            ->map(function ($s) {
                $s->proof_photo_url = $s->proof_photo
                    ? url('storage/' . $s->proof_photo)
                    : null;
                return $s;
            });

        return response()->json(['data' => $sanctions]);
    }

    // POST /api/sanctions/{id}/complete
    public function completeSanction(Request $request, $id)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['message' => 'Unauthorized'], 401);

        $sanction = Sanction::where('id', $id)
            ->where('user_id', $user->id)
            ->whereNull('completed_at')
            ->first();

        if (!$sanction) {
            return response()->json(['message' => 'Sanksi tidak ditemukan atau sudah diselesaikan'], 404);
        }

        // Upload foto bukti jika ada
        if ($request->hasFile('proof_photo')) {
            $request->validate(['proof_photo' => 'image|max:5120']); // max 5MB
            $path = $request->file('proof_photo')->store('proofs/sanctions', 'public');
            $sanction->proof_photo = $path;
        }

        $sanction->completed_at = now();
        $sanction->save();

        $sanction->proof_photo_url = $sanction->proof_photo
            ? url('storage/' . $sanction->proof_photo)
            : null;

        return response()->json([
            'message' => 'Sanksi berhasil diselesaikan',
            'data'    => $sanction,
        ]);
    }

    // GET /api/tasks/my
    public function myTasks(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['message' => 'Unauthorized'], 401);

        $tasks = AdditionalTask::where('user_id', $user->id)
            ->with('assigner:id,name')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($t) {
                $t->proof_photo_url = $t->proof_photo
                    ? url('storage/' . $t->proof_photo)
                    : null;
                return $t;
            });

        return response()->json(['data' => $tasks]);
    }

    // POST /api/tasks/{id}/complete
    public function completeTask(Request $request, $id)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['message' => 'Unauthorized'], 401);

        $task = AdditionalTask::where('id', $id)
            ->where('user_id', $user->id)
            ->where('status', '!=', 'done')
            ->first();

        if (!$task) {
            return response()->json(['message' => 'Tugas tidak ditemukan atau sudah selesai'], 404);
        }

        // Upload foto bukti jika ada
        if ($request->hasFile('proof_photo')) {
            $request->validate(['proof_photo' => 'image|max:5120']); // max 5MB
            $path = $request->file('proof_photo')->store('proofs/tasks', 'public');
            $task->proof_photo = $path;
        }

        $task->status       = 'done';
        $task->completed_at = now();
        $task->save();

        $task->proof_photo_url = $task->proof_photo
            ? url('storage/' . $task->proof_photo)
            : null;

        return response()->json([
            'message' => 'Tugas berhasil diselesaikan',
            'data'    => $task,
        ]);
    }
}
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FaceProfile;
use App\Models\User;
use Illuminate\Http\Request;

class FaceController extends Controller
{
    private function getUserFromToken(Request $request): ?User
    {
        $header = $request->header('Authorization', '');
        $token  = preg_replace('/^(Token|Bearer)\s+/i', '', trim($header));
        return User::where('api_token', $token)->first();
    }

    // GET /api/face/profiles — ambil semua descriptor untuk matching di frontend
    // Dipanggil saat halaman login dimuat (tanpa token)
    public function allProfiles()
    {
        $profiles = FaceProfile::with('user:id,name,avatar')
            ->get()
            ->map(fn($p) => [
                'user_id'    => $p->user_id,
                'name'       => $p->user->name,
                'avatar'     => $p->user->avatar ? url('storage/' . $p->user->avatar) : null,
                'descriptor' => json_decode($p->descriptor), // array float[128]
            ]);

        return response()->json(['data' => $profiles]);
    }

    // POST /api/admin/face/enroll/{userId} — admin daftarkan wajah pegawai
    public function enroll(Request $request, $userId)
    {
        $request->validate([
            'descriptor' => 'required|array|min:128|max:128',
            'descriptor.*' => 'required|numeric',
        ], [
            'descriptor.required' => 'Data wajah wajib ada.',
            'descriptor.array'    => 'Format descriptor tidak valid.',
            'descriptor.min'      => 'Descriptor harus berisi 128 nilai.',
            'descriptor.max'      => 'Descriptor harus berisi 128 nilai.',
        ]);

        $user = User::findOrFail($userId);

        FaceProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'descriptor' => json_encode($request->descriptor),
                'label'      => $user->name,
            ]
        );

        return response()->json([
            'message' => "Wajah {$user->name} berhasil didaftarkan.",
        ]);
    }

    // DELETE /api/admin/face/delete/{userId} — admin hapus wajah
    public function delete($userId)
    {
        FaceProfile::where('user_id', $userId)->delete();
        return response()->json(['message' => 'Data wajah berhasil dihapus.']);
    }

    // GET /api/admin/face/status — siapa saja yang sudah/belum daftar wajah
    public function status()
    {
        $users = User::where('role', 'user')
            ->with(['faceProfile', 'position:id,name'])
            ->select('id', 'name', 'avatar', 'position_id')
            ->get()
            ->map(fn($u) => [
                'id'           => $u->id,
                'name'         => $u->name,
                'avatar'       => $u->avatar ? url('storage/' . $u->avatar) : null,
                'position'     => $u->position?->name,
                'has_face'     => $u->faceProfile !== null,
                'enrolled_at'  => $u->faceProfile?->updated_at?->format('d M Y H:i'),
            ]);

        return response()->json(['data' => $users]);
    }
}
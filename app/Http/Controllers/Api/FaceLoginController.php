<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FaceLoginController extends Controller
{
    // POST /api/face/login
    // Dipanggil oleh frontend setelah face recognition sukses
    // Frontend mengirim user_id yang sudah dicocokkan secara lokal
    public function login(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $user = User::find($request->user_id);

        // Hanya pegawai biasa yang boleh login via face
        if ($user->role !== 'user') {
            return response()->json([
                'message' => 'Akun admin tidak bisa login via face recognition.'
            ], 403);
        }

        // Cek apakah user punya profil wajah terdaftar
        if (!$user->faceProfile) {
            return response()->json([
                'message' => 'Wajah belum terdaftar. Hubungi admin.'
            ], 403);
        }

        // Generate token baru
        $user->api_token = Str::random(60);
        $user->save();
        $user->refresh();

        return response()->json($user);
    }
}
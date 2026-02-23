<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['errors' => ['login' => 'Email atau password salah']], 401);
        }

        $user->api_token = Str::random(60);
        $user->save();

        return response()->json($user);
    }

    public function adminLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['errors' => ['login' => 'Email atau password salah']], 401);
        }

        if ($user->role !== 'admin') {
            return response()->json(['errors' => ['login' => 'Akun ini bukan admin']], 403);
        }

        $user->api_token = Str::random(60);
        $user->save();

        return response()->json($user);
    }

    public function verifyToken(Request $request)
    {
        $request->validate(['api_token' => 'required']);

        $user = User::where('api_token', $request->api_token)->first();

        if (!$user) {
            return response()->json(['errors' => ['token' => 'Token tidak valid']], 401);
        }

        return response()->json($user);
    }

    public function logout(Request $request)
    {
        $header = $request->header('Authorization');
        if ($header) {
            $token = str_replace('Token ', '', $header);
            $user = User::where('api_token', $token)->first();
            if ($user) {
                $user->api_token = null;
                $user->save();
            }
        }
        return response()->json(['message' => 'Logout berhasil']);
    }

    public function register(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email',
            'password'   => 'required|min:8|confirmed',
        ]);

        $user = User::create([
            'name'      => $request->first_name . ' ' . $request->last_name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'api_token' => Str::random(60),
            'role'      => 'user',
        ]);

        return response()->json($user);
    }

    public function updateProfile(Request $request)
    {
        $header = $request->header('Authorization');
        if (!$header) {
            return response()->json(['errors' => ['auth' => 'Unauthorized']], 401);
        }

        $token = str_replace('Token ', '', $header);
        $user = User::where('api_token', $token)->first();

        if (!$user) {
            return response()->json(['errors' => ['auth' => 'Unauthorized']], 401);
        }

        $request->validate([
            'name'      => 'sometimes|string|max:255',
            'phone'     => 'sometimes|string|max:20',
            'bio'       => 'sometimes|string|max:500',
            'job_title' => 'sometimes|string|max:100',
            'company'   => 'sometimes|string|max:100',
        ]);

        $user->update($request->only(['name', 'phone', 'bio', 'job_title', 'company']));

        return response()->json($user);
    }

    public function uploadAvatar(Request $request)
    {
        $header = $request->header('Authorization');
        if (!$header) {
            return response()->json(['errors' => ['auth' => 'Unauthorized']], 401);
        }

        $token = str_replace('Token ', '', $header);
        $user = User::where('api_token', $token)->first();

        if (!$user) {
            return response()->json(['errors' => ['auth' => 'Unauthorized']], 401);
        }

        $request->validate([
            'avatar' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        // Hapus avatar lama jika ada
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        // Simpan avatar baru
        $path = $request->file('avatar')->store('avatars', 'public');
        $user->avatar = $path;
        $user->save();

        return response()->json([
            'message' => 'Avatar berhasil diupload',
            'avatar'  => $user->avatar,
            'avatar_url' => asset('storage/' . $user->avatar),
            'user'    => $user,
        ]);
    }
}
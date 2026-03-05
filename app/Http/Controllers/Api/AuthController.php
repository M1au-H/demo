<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate(['email' => 'required|email', 'password' => 'required']);
        $user = User::where('email', $request->email)->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['errors' => ['login' => 'Email atau password salah']], 401);
        }
        $user->api_token = Str::random(60);
        $user->save();
        $user->refresh();
        return response()->json($user);
    }

    public function adminLogin(Request $request)
    {
        $request->validate(['email' => 'required|email', 'password' => 'required']);
        $user = User::where('email', $request->email)->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['errors' => ['login' => 'Email atau password salah']], 401);
        }
        if ($user->role !== 'admin') {
            return response()->json(['errors' => ['login' => 'Akun ini bukan admin']], 403);
        }
        $user->api_token = Str::random(60);
        $user->save();
        $user->refresh();
        return response()->json($user);
    }

    public function verifyToken(Request $request)
    {
        $request->validate(['api_token' => 'required']);
        $user = User::where('api_token', $request->api_token)->first();
        if (!$user) {
            return response()->json(['errors' => ['token' => 'Token tidak valid']], 401);
        }
        $user->refresh();
        return response()->json($user);
    }

    public function logout(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if ($user) {
            $user->api_token = null;
            $user->save();
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
        ], [
            'first_name.required' => 'Nama depan wajib diisi.',
            'last_name.required'  => 'Nama belakang wajib diisi.',
            'email.required'      => 'Email wajib diisi.',
            'email.email'         => 'Format email tidak valid.',
            'email.unique'        => 'Email sudah terdaftar.',
            'password.required'   => 'Password wajib diisi.',
            'password.min'        => 'Password minimal 8 karakter.',
            'password.confirmed'  => 'Konfirmasi password tidak cocok.',
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
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['errors' => ['auth' => 'Unauthorized']], 401);

        $request->validate([
            'name'      => 'sometimes|nullable|string|max:255',
            'phone'     => 'sometimes|nullable|string|max:20',
            'bio'       => 'sometimes|nullable|string|max:500',
            'job_title' => 'sometimes|nullable|string|max:100',
            'company'   => 'sometimes|nullable|string|max:100',
        ], [
            'name.max'      => 'Nama maksimal 255 karakter.',
            'phone.max'     => 'Nomor telepon maksimal 20 karakter.',
            'bio.max'       => 'Bio maksimal 500 karakter.',
            'job_title.max' => 'Jabatan maksimal 100 karakter.',
            'company.max'   => 'Nama perusahaan maksimal 100 karakter.',
        ]);

        $updateData = array_filter(
            $request->only(['name', 'phone', 'bio', 'job_title', 'company']),
            fn($v) => !is_null($v) && $v !== ''
        );

        if (!empty($updateData)) {
            $user->update($updateData);
        }

        $user->refresh();
        return response()->json($user);
    }

    public function changePassword(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['errors' => ['auth' => 'Unauthorized']], 401);

        $request->validate([
            'current_password' => 'required',
            'new_password'     => 'required|min:8|confirmed',
        ], [
            'current_password.required' => 'Password lama wajib diisi.',
            'new_password.required'     => 'Password baru wajib diisi.',
            'new_password.min'          => 'Password baru minimal 8 karakter.',
            'new_password.confirmed'    => 'Konfirmasi password baru tidak cocok.',
        ]);

        $currentHashedPassword = DB::table('users')->where('id', $user->id)->value('password');

        if (!Hash::check($request->current_password, $currentHashedPassword)) {
            return response()->json([
                'errors' => ['current_password' => ['Password lama tidak sesuai.']]
            ], 422);
        }

        if (Hash::check($request->new_password, $currentHashedPassword)) {
            return response()->json([
                'errors' => ['new_password' => ['Password baru tidak boleh sama dengan password lama.']]
            ], 422);
        }

        DB::table('users')->where('id', $user->id)->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json(['message' => 'Password berhasil diubah']);
    }

    public function uploadAvatar(Request $request)
    {
        $user = $this->getUserFromToken($request);
        if (!$user) return response()->json(['errors' => ['auth' => 'Unauthorized']], 401);

        $request->validate([
            'avatar' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ], [
            'avatar.required' => 'Foto profil wajib dipilih.',
            'avatar.image'    => 'File harus berupa gambar.',
            'avatar.mimes'    => 'Format foto harus jpg, jpeg, png, atau webp.',
            'avatar.max'      => 'Ukuran foto maksimal 2MB.',
        ]);

        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $user->avatar = $path;
        $user->save();
        $user->refresh();

        return response()->json([
            'message'    => 'Foto profil berhasil diupload',
            'avatar'     => $user->avatar,
            'avatar_url' => asset('storage/' . $user->avatar),
            'user'       => $user,
        ]);
    }

    private function getUserFromToken(Request $request): ?User
    {
        $header = $request->header('Authorization', '');
        if (!$header) return null;

        $token = preg_replace('/^(Token|Bearer)\s+/i', '', trim($header));

        if (!$token) return null;

        return User::where('api_token', $token)->first();
    }
}
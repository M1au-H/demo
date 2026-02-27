<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;

class CheckRole
{
    public function handle(Request $request, Closure $next, string $role): mixed
    {
        // Baca token dari header Authorization: Bearer xxx
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['message' => 'Token tidak ditemukan.'], 401);
        }

        // Cari user berdasarkan api_token
        $user = User::where('api_token', $token)->first();

        if (!$user) {
            return response()->json(['message' => 'Token tidak valid.'], 401);
        }

        if ($user->role !== $role) {
            return response()->json(['message' => 'Forbidden. Akses ditolak.'], 403);
        }

        // Inject user ke request agar bisa dipakai di controller
        $request->setUserResolver(fn() => $user);

        return $next($request);
    }
}
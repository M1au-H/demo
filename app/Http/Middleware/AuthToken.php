<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;

class AuthToken
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken() 
               ?? $request->header('X-Api-Token')
               ?? $request->input('api_token');

        if (!$token) {
            return response()->json(['message' => 'Token tidak ditemukan.'], 401);
        }

        $user = User::where('api_token', $token)->first();

        if (!$user) {
            return response()->json(['message' => 'Token tidak valid.'], 401);
        }

        auth()->setUser($user);

        return $next($request);
    }
}
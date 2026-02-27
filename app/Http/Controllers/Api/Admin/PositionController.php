<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Position;
use App\Models\User;
use Illuminate\Http\Request;

class PositionController extends Controller
{
    public function index()
    {
        $positions = Position::withCount('users')->get();
        return response()->json(['data' => $positions]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:positions,name|max:100',
            'description' => 'nullable|string|max:255',
        ]);
        $position = Position::create($request->only('name', 'description'));
        $position->users_count = 0;
        return response()->json(['data' => $position], 201);
    }

    public function update(Request $request, $id)
    {
        $position = Position::findOrFail($id);
        $request->validate([
            'name' => 'required|string|unique:positions,name,'.$id.'|max:100',
            'description' => 'nullable|string|max:255',
        ]);
        $position->update($request->only('name', 'description'));
        return response()->json(['data' => $position]);
    }

    public function destroy($id)
    {
        $position = Position::withCount('users')->findOrFail($id);
        if ($position->users_count > 0) {
            return response()->json([
                'message' => 'Jabatan tidak bisa dihapus karena masih memiliki '.$position->users_count.' pegawai.'
            ], 422);
        }
        $position->delete();
        return response()->json(['message' => 'Jabatan berhasil dihapus.']);
    }

    public function assign(Request $request, $id)
    {
        $position = Position::findOrFail($id);
        $request->validate(['user_id' => 'required|exists:users,id']);
        $user = User::findOrFail($request->user_id);
        $user->position_id = $position->id;
        $user->save();
        return response()->json([
            'message' => "Jabatan {$position->name} berhasil diberikan ke {$user->name}.",
            'data'    => $user->load('position'),
        ]);
    }

    public function revoke(Request $request, $id)
    {
        $position = Position::findOrFail($id);
        $request->validate(['user_id' => 'required|exists:users,id']);
        $user = User::findOrFail($request->user_id);
        if ($user->position_id !== (int) $id) {
            return response()->json([
                'message' => 'Pegawai ini tidak memiliki jabatan tersebut.'
            ], 422);
        }
        $user->position_id = null;
        $user->save();
        return response()->json([
            'message' => "Jabatan {$position->name} berhasil dicabut dari {$user->name}.",
        ]);
    }
}
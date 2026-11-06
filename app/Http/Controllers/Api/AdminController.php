<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Classes;
use App\Models\Room;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    // ================= USER CRUD =================
    public function listUsers(Request $request)
    {
        $query = User::with(['role', 'schoolClass']);
        if ($request->filled('role_id')) {
            $query->where('role_id', $request->role_id);
        } elseif ($request->filled('role')) {
            $roleVal = strtolower(trim($request->role));
            $targetRoleId = match ($roleVal) {
                'admin', 'waka' => 1,
                'guru', 'guru_mapel' => 2,
                'siswa' => 3,
                'ortu', 'orangtua', 'orang_tua' => 4,
                'bk', 'bimbingan_konseling', 'konseling' => 5,
                'walas', 'walikelas', 'wali_kelas', 'wali' => 6,
                default => null,
            };
            if ($targetRoleId) {
                $query->where('role_id', $targetRoleId);
            }
        }
        $users = $query->orderByRaw('CASE WHEN role_id = 1 THEN 0 ELSE 1 END')
            ->orderBy('fullname')
            ->paginate(20);

        return response()->json(['success' => true, 'data' => $users], 200);
    }

    public function createUser(Request $request)
    {
        $validated = $request->validate([
            'role_id' => 'required|exists:roles,id',
            'username' => 'required|string|max:50|unique:users,username',
            'password' => 'required|string|min:6',
            'fullname' => 'required|string|max:100',
            'class_id' => 'nullable|exists:classes,id',
            'parent_id' => 'nullable|exists:users,id',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $user = User::create($validated);

        return response()->json(['success' => true, 'message' => 'User berhasil ditambahkan', 'data' => $user], 201);
    }

    public function showUser($id)
    {
        $user = User::with(['role', 'schoolClass', 'parent', 'children'])->findOrFail($id);
        return response()->json(['success' => true, 'data' => $user], 200);
    }

    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $validated = $request->validate([
            'role_id' => 'required|exists:roles,id',
            'username' => 'required|string|max:50|unique:users,username,' . $id,
            'fullname' => 'required|string|max:100',
            'password' => 'nullable|string|min:6',
            'class_id' => 'nullable|exists:classes,id',
            'parent_id' => 'nullable|exists:users,id',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);
        return response()->json(['success' => true, 'message' => 'User berhasil diperbarui', 'data' => $user], 200);
    }

    public function deleteUser($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return response()->json(['success' => true, 'message' => 'User berhasil dihapus'], 200);
    }

    // ================= CLASSES CRUD =================
    public function listClasses()
    {
        $classes = Classes::withCount('students')->orderBy('class_name')->get();
        return response()->json(['success' => true, 'data' => $classes], 200);
    }

    public function createClass(Request $request)
    {
        $validated = $request->validate([
            'class_name' => 'required|string|max:50|unique:classes,class_name',
        ]);
        $class = Classes::create($validated);
        return response()->json(['success' => true, 'message' => 'Kelas berhasil ditambahkan', 'data' => $class], 201);
    }

    public function updateClass(Request $request, $id)
    {
        $class = Classes::findOrFail($id);
        $validated = $request->validate([
            'class_name' => 'required|string|max:50|unique:classes,class_name,' . $id,
        ]);
        $class->update($validated);
        return response()->json(['success' => true, 'message' => 'Kelas berhasil diperbarui', 'data' => $class], 200);
    }

    public function deleteClass($id)
    {
        $class = Classes::findOrFail($id);
        $class->delete();
        return response()->json(['success' => true, 'message' => 'Kelas berhasil dihapus'], 200);
    }

    // ================= ROOMS CRUD =================
    public function listRooms()
    {
        $rooms = Room::orderBy('room_name')->get();
        return response()->json(['success' => true, 'data' => $rooms], 200);
    }

    public function createRoom(Request $request)
    {
        $validated = $request->validate([
            'room_name' => 'required|string|max:50|unique:rooms,room_name',
        ]);
        $room = Room::create($validated);
        return response()->json(['success' => true, 'message' => 'Ruangan berhasil ditambahkan', 'data' => $room], 201);
    }

    public function updateRoom(Request $request, $id)
    {
        $room = Room::findOrFail($id);
        $validated = $request->validate([
            'room_name' => 'required|string|max:50|unique:rooms,room_name,' . $id,
        ]);
        $room->update($validated);
        return response()->json(['success' => true, 'message' => 'Ruangan berhasil diperbarui', 'data' => $room], 200);
    }

    public function deleteRoom($id)
    {
        $room = Room::findOrFail($id);
        $room->delete();
        return response()->json(['success' => true, 'message' => 'Ruangan berhasil dihapus'], 200);
    }
}

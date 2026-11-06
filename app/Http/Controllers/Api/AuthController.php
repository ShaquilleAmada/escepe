<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // 1. Validasi input
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string'
        ]);

        $loginInput = trim($request->username);

        // 2. Cari user berdasarkan username ATAU nip_nisn (NIS / NIP)
        $user = User::where('username', $loginInput)
            ->orWhere('nip_nisn', $loginInput)
            ->first();

        // 3. Jika login diawali 'P' atau 'p' (format akun Orang Tua: P + NIS Siswa) dan akun belum ada
        if (!$user && (str_starts_with($loginInput, 'P') || str_starts_with($loginInput, 'p')) && strlen($loginInput) > 1) {
            $studentNis = substr($loginInput, 1);
            $student = User::where('role_id', 3)
                ->where(function ($q) use ($studentNis) {
                    $q->where('nip_nisn', $studentNis)
                      ->orWhere('username', $studentNis);
                })->first();

            if ($student) {
                $actualNis = $student->nip_nisn ?: $student->username;
                $user = User::firstOrCreate(
                    ['username' => 'P' . $actualNis],
                    [
                        'role_id'   => 4, // OrangTua
                        'fullname'  => 'Orang Tua ' . $student->fullname,
                        'parent_id' => $student->id,
                        'class_id'  => $student->class_id,
                        'password'  => Hash::make('password123'),
                    ]
                );

                if (!$student->parent_id) {
                    $student->update(['parent_id' => $user->id]);
                }
            }
        }

        // 4. Cek apakah user ada dan password cocok
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Username atau password salah.'
            ], 401);
        }

        // Cek status keaktifan siswa / akun orang tua (nonaktif jika siswa telah lulus)
        if ($user->student_status === 'lulus') {
            return response()->json([
                'success' => false,
                'message' => 'Akun tidak aktif karena siswa telah dinyatakan lulus.'
            ], 403);
        }

        if ($user->role_id == 4) {
            $child = $user->children()->first();
            if (!$child && $user->parent_id) {
                $child = User::find($user->parent_id);
            }
            if ($child && $child->student_status === 'lulus') {
                return response()->json([
                    'success' => false,
                    'message' => 'Akun orang tua tidak aktif karena siswa telah dinyatakan lulus.'
                ], 403);
            }
        }

        // 5. Buat Token Sanctum
        $token = $user->createToken('scp-app-token')->plainTextToken;
        $user->load(['role', 'schoolClass']);

        // Jika Orang Tua, load juga data anak/siswa yang terhubung
        if ($user->role_id == 4) {
            $user->load(['children.schoolClass']);
        }

        $mustChangePassword = Hash::check('password123', $user->password);

        // 6. Kembalikan Response
        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'token' => $token,
            'must_change_password' => $mustChangePassword,
            'data' => $user,
            'user' => $user
        ], 200);
    }

    public function logout(Request $request)
    {
        // Hapus token yang sedang digunakan
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil'
        ], 200);
    }
}
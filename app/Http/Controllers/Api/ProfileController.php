<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * ProfileController (API)
 *
 * Endpoint untuk pengguna mengelola profil mereka sendiri:
 *   GET    /api/profile              → Lihat profil sendiri
 *   PUT    /api/profile              → Update data profil (fullname, email, phone, nip_nisn)
 *   POST   /api/profile/change-password → Ganti password dengan verifikasi password lama
 */
class ProfileController extends Controller
{
    /**
     * Ambil profil user yang sedang login beserta relasi penting.
     */
    public function show(Request $request)
    {
        $user = $request->user()->load(['role', 'schoolClass', 'parent', 'children']);

        return response()->json([
            'success' => true,
            'data'    => $user,
        ], 200);
    }

    /**
     * Update data profil sendiri.
     * Tidak memperbolehkan perubahan username, role, class — hanya data personal.
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'fullname' => 'required|string|max:100',
            'email'    => 'nullable|email|max:100|unique:users,email,' . $user->id,
            'phone'    => 'nullable|string|max:20',
            'nip_nisn' => 'nullable|string|max:30|unique:users,nip_nisn,' . $user->id,
        ], [
            'fullname.required' => 'Nama lengkap wajib diisi.',
            'email.email'       => 'Format email tidak valid.',
            'email.unique'      => 'Email sudah digunakan oleh akun lain.',
            'nip_nisn.unique'   => 'NIP/NISN sudah digunakan oleh akun lain.',
        ]);

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui.',
            'data'    => $user->fresh()->load(['role', 'schoolClass']),
        ], 200);
    }

    /**
     * Ganti password user sendiri.
     * Wajib memasukkan password lama sebagai verifikasi keamanan.
     */
    public function changePassword(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'current_password'          => 'required|string',
            'password'                  => ['required', Password::min(8), 'confirmed'],
        ], [
            'current_password.required' => 'Password saat ini wajib diisi.',
            'password.required'         => 'Password baru wajib diisi.',
            'password.confirmed'        => 'Konfirmasi password tidak cocok.',
        ]);

        // Verifikasi password lama
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password saat ini tidak sesuai.',
                'errors'  => ['current_password' => ['Password saat ini tidak sesuai.']],
            ], 422);
        }

        // Pastikan password baru tidak sama dengan password lama
        if (Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password baru tidak boleh sama dengan password saat ini.',
                'errors'  => ['password' => ['Password baru tidak boleh sama dengan password saat ini.']],
            ], 422);
        }

        // Simpan password baru
        $user->update(['password' => Hash::make($request->password)]);

        // Hapus semua token lain kecuali yang sedang digunakan (paksa logout device lain)
        $user->tokens()->where('id', '!=', $request->user()->currentAccessToken()->id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diubah. Sesi di perangkat lain telah diakhiri.',
        ], 200);
    }
}

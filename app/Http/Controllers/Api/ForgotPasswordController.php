<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PasswordOtp;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * ForgotPasswordController (API)
 *
 * Alur reset password 3 langkah untuk aplikasi mobile:
 *   1. POST /api/forgot-password/send-otp   → masukkan email, kirim OTP
 *   2. POST /api/forgot-password/verify-otp → verifikasi OTP, dapat reset_token
 *   3. POST /api/forgot-password/reset-password → pakai reset_token untuk ganti password
 */
class ForgotPasswordController extends Controller
{
    public function __construct(private readonly OtpService $otpService) {}

    // -------------------------------------------------------------------------
    // LANGKAH 1: Kirim OTP ke email pengguna
    // -------------------------------------------------------------------------

    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        // Cari user dengan email ini
        $user = User::where('email', $request->email)->first();

        // Selalu kembalikan response sukses meski email tidak ada
        // (mencegah user enumerasi/guessing email yang terdaftar)
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Alamat email tidak terdaftar di database.',
            ], 404);
        }

        try {
            $otp = $this->otpService->generate($request->email);
            // Untuk API, dispatch hanya kirim email (mode 'email')
            // Jika mode 'display', otp akan tersedia di response untuk development
            $mode = config('otp.mode', 'display');
            if ($mode === 'email') {
                $this->otpService->dispatch($otp, $request->email, $request);
                return response()->json([
                    'success' => true,
                    'message' => 'Kode OTP telah dikirim ke email ' . $request->email . '. Kode berlaku selama 10 menit.',
                ], 200);
            } else {
                // Mode development: tampilkan OTP langsung di response
                return response()->json([
                    'success'  => true,
                    'message'  => '[DEV MODE] Kode OTP berhasil dibuat.',
                    'otp_code' => $otp, // HANYA untuk development, hapus di production
                ], 200);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('API OTP error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim OTP. Pastikan pengaturan email server sudah dikonfigurasi.',
            ], 500);
        }
    }

    // -------------------------------------------------------------------------
    // LANGKAH 2: Verifikasi OTP → kembalikan reset_token (satu kali pakai)
    // -------------------------------------------------------------------------

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp'   => 'required|string|digits:6',
        ]);

        $maxAttempts = config('otp.max_attempts', 5);
        $otpRecord   = PasswordOtp::where('email', $request->email)->latest()->first();

        if (!$otpRecord) {
            return response()->json([
                'success' => false,
                'message' => 'Kode OTP tidak ditemukan atau sudah kedaluwarsa. Minta kode baru.',
            ], 422);
        }

        if ($otpRecord->isLocked($maxAttempts)) {
            $otpRecord->delete();
            return response()->json([
                'success' => false,
                'message' => 'Terlalu banyak percobaan verifikasi. Minta kode OTP baru.',
            ], 422);
        }

        if ($otpRecord->isExpired()) {
            $otpRecord->delete();
            return response()->json([
                'success' => false,
                'message' => 'Kode OTP sudah kedaluwarsa. Minta kode OTP baru.',
            ], 422);
        }

        if (!Hash::check($request->otp, $otpRecord->otp_hash)) {
            $otpRecord->increment('attempts');
            $sisaPercobaan = $maxAttempts - $otpRecord->fresh()->attempts;

            if ($sisaPercobaan <= 0) {
                $otpRecord->delete();
                return response()->json([
                    'success' => false,
                    'message' => 'Terlalu banyak percobaan. Minta kode OTP baru.',
                ], 422);
            }

            return response()->json([
                'success'           => false,
                'message'           => "Kode OTP salah. Sisa percobaan: {$sisaPercobaan}.",
                'remaining_attempts'=> $sisaPercobaan,
            ], 422);
        }

        // OTP benar → buat reset_token (simpan di otp_record sementara sebagai marker)
        // Gunakan kombinasi hash email + waktu sebagai token satu kali pakai
        $resetToken = hash('sha256', $request->email . now()->timestamp . $request->otp);

        // Simpan reset token ke record OTP (tandai bahwa OTP sudah diverifikasi)
        $otpRecord->update(['otp_hash' => Hash::make($resetToken)]);

        return response()->json([
            'success'     => true,
            'message'     => 'Kode OTP valid. Gunakan reset_token di bawah untuk mengubah password.',
            'reset_token' => $resetToken,
        ], 200);
    }

    // -------------------------------------------------------------------------
    // LANGKAH 3: Reset password menggunakan reset_token dari langkah 2
    // -------------------------------------------------------------------------

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email'                 => 'required|email',
            'reset_token'           => 'required|string',
            'password'              => ['required', Password::min(8), 'confirmed'],
        ]);

        $otpRecord = PasswordOtp::where('email', $request->email)->latest()->first();

        if (!$otpRecord) {
            return response()->json([
                'success' => false,
                'message' => 'Sesi reset password tidak valid atau sudah kedaluwarsa. Mulai ulang proses.',
            ], 422);
        }

        // Verifikasi reset_token
        if (!Hash::check($request->reset_token, $otpRecord->otp_hash)) {
            return response()->json([
                'success' => false,
                'message' => 'Reset token tidak valid. Mulai ulang proses lupa password.',
            ], 422);
        }

        // Cek token kedaluwarsa (berlaku 10 menit dari OTP awal dibuat)
        if ($otpRecord->isExpired()) {
            $otpRecord->delete();
            return response()->json([
                'success' => false,
                'message' => 'Sesi reset password telah kedaluwarsa. Minta kode OTP baru.',
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            $otpRecord->delete();
            return response()->json([
                'success' => false,
                'message' => 'Akun tidak ditemukan.',
            ], 404);
        }

        // Pastikan password baru tidak sama dengan yang lama
        if (Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password baru tidak boleh sama dengan password lama.',
            ], 422);
        }

        // Simpan password baru
        $user->update(['password' => Hash::make($request->password)]);

        // Hapus OTP & batalkan semua token aktif (paksa login ulang di semua device)
        $otpRecord->delete();
        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diubah. Silakan login dengan password baru Anda.',
        ], 200);
    }
}

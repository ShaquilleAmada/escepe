<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PasswordOtp;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ForgotPasswordController extends Controller
{
    public function __construct(private readonly OtpService $otpService) {}

    // -------------------------------------------------------------------------
    // LANGKAH 1: Tampilkan form input email
    // -------------------------------------------------------------------------

    public function showEmailForm()
    {
        return view('auth.forgot-password');
    }

    // -------------------------------------------------------------------------
    // LANGKAH 2: Proses email → buat & kirim OTP
    // -------------------------------------------------------------------------

    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email'    => 'Format email tidak valid.',
        ]);

        // Cari user yang terdaftar dengan email ini (semua role user)
        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return back()
                ->withInput()
                ->withErrors(['email' => 'Email tidak terdaftar dalam sistem.']);
        }

        try {
            // Buat OTP baru (menghapus OTP lama otomatis di dalam service)
            $otp = $this->otpService->generate($request->email);

            // Kirim OTP sesuai mode (email: kirim Mailable ke email user; display: flash ke session)
            $this->otpService->dispatch($otp, $request->email, $request);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Gagal mengirim email OTP: ' . $e->getMessage());

            return back()
                ->withInput()
                ->withErrors(['email' => 'Gagal mengirim email verifikasi ke ' . $request->email . '. Pastikan pengaturan server email (SMTP) pada file .env sudah valid.']);
        }

        // Tandai di session bahwa user boleh akses halaman reset (tanpa expose email di URL)
        $request->session()->put('otp_email', $request->email);

        $statusMsg = config('otp.mode') === 'email'
            ? 'Kode OTP verifikasi telah dikirim ke email ' . $request->email . '. Silakan periksa kotak masuk atau spam.'
            : 'Kode verifikasi berhasil dibuat.';

        return redirect()->route('password.reset.form')
            ->with('status', $statusMsg);
    }

    // -------------------------------------------------------------------------
    // LANGKAH 3: Tampilkan form reset password
    // -------------------------------------------------------------------------

    public function showResetForm(Request $request)
    {
        // Blokir akses langsung tanpa melewati langkah email
        if (! $request->session()->has('otp_email')) {
            return redirect()->route('password.forgot')
                ->withErrors(['email' => 'Silakan masukkan email Anda terlebih dahulu.']);
        }

        return view('auth.reset-password');
    }

    // -------------------------------------------------------------------------
    // LANGKAH 4: Proses verifikasi OTP + simpan password baru
    // -------------------------------------------------------------------------

    public function resetPassword(Request $request)
    {
        // Blokir akses tanpa session email
        if (! $request->session()->has('otp_email')) {
            return redirect()->route('password.forgot');
        }

        $email = $request->session()->get('otp_email');

        $request->validate([
            'otp'                   => ['required', 'string', 'digits:6'],
            'password'              => ['required', Password::min(8), 'confirmed'],
        ], [
            'otp.required'          => 'Kode OTP wajib diisi.',
            'otp.digits'            => 'Kode OTP harus 6 digit angka.',
            'password.required'     => 'Password baru wajib diisi.',
            'password.min'          => 'Password minimal 8 karakter.',
            'password.confirmed'    => 'Password tidak cocok.',
        ]);

        $maxAttempts = config('otp.max_attempts', 5);

        // Cari OTP aktif untuk email ini
        $otpRecord = PasswordOtp::where('email', $email)->latest()->first();

        if (! $otpRecord) {
            return back()->withErrors(['otp' => 'Kode kedaluwarsa. Minta kode baru.']);
        }

        // Cek apakah sudah terlalu banyak percobaan salah
        if ($otpRecord->isLocked($maxAttempts)) {
            $otpRecord->delete();
            $request->session()->forget('otp_email');
            return back()->withErrors(['otp' => 'Terlalu banyak percobaan. Minta kode baru.']);
        }

        // Cek apakah OTP sudah kedaluwarsa
        if ($otpRecord->isExpired()) {
            $otpRecord->delete();
            $request->session()->forget('otp_email');
            return back()->withErrors(['otp' => 'Kode kedaluwarsa. Minta kode baru.']);
        }

        // Verifikasi OTP (hash check)
        if (! Hash::check($request->otp, $otpRecord->otp_hash)) {
            $otpRecord->increment('attempts');
            $sisaPercobaan = $maxAttempts - $otpRecord->fresh()->attempts;

            if ($sisaPercobaan <= 0) {
                $otpRecord->delete();
                $request->session()->forget('otp_email');
                return back()->withErrors(['otp' => 'Terlalu banyak percobaan. Minta kode baru.']);
            }

            return back()->withErrors([
                'otp' => "Kode salah. Sisa percobaan: {$sisaPercobaan}.",
            ]);
        }

        // OTP benar → ambil user (semua role)
        $user = User::where('email', $email)->first();

        if (! $user) {
            $otpRecord->delete();
            $request->session()->forget('otp_email');
            return redirect()->route('password.forgot')
                ->withErrors(['email' => 'Akun tidak ditemukan.']);
        }

        // Pastikan password baru tidak sama dengan password lama
        if (Hash::check($request->password, $user->password)) {
            return back()->withErrors([
                'password' => 'Password baru tidak boleh sama dengan password lama.',
            ]);
        }

        // Simpan password baru dengan hash — password lama tidak pernah di-log
        $user->update(['password' => Hash::make($request->password)]);

        // Hapus OTP (sekali pakai) & bersihkan session
        $otpRecord->delete();
        $request->session()->forget('otp_email');

        return redirect()->route('login')
            ->with('success', 'Password berhasil diubah. Silakan login.');
    }
}

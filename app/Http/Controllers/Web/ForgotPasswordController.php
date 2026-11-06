<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PasswordOtp;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rules\Password;

class ForgotPasswordController extends Controller
{
    public function __construct(private readonly OtpService $otpService) {}

    // -------------------------------------------------------------------------
    // LANGKAH 1: Form Input Email
    // -------------------------------------------------------------------------

    public function showEmailForm()
    {
        return view('auth.forgot-password');
    }

    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email'    => 'Format email tidak valid.',
        ]);

        $email = strtolower(trim($request->email));

        // Cari user yang terdaftar dengan email ini
        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if (! $user) {
            return back()
                ->withInput()
                ->withErrors(['email' => 'Alamat email tidak terdaftar dalam sistem.']);
        }

        try {
            // Buat OTP baru (menghapus OTP lama otomatis)
            $otp = $this->otpService->generate($email);

            // Kirim OTP via email atau session display (sesuai config)
            $this->otpService->dispatch($otp, $email, $request);
        } catch (\Throwable $e) {
            Log::error('Gagal mengirim email OTP: ' . $e->getMessage());

            return back()
                ->withInput()
                ->withErrors(['email' => 'Gagal mengirim email verifikasi ke ' . $email . '. Pastikan konfigurasi SMTP email pada server sudah aktif.']);
        }

        // Simpan email di session untuk langkah verifikasi OTP
        $request->session()->put('otp_email', $email);
        $request->session()->put('otp_verified', false);
        $request->session()->save();

        $statusMsg = config('otp.mode') === 'email'
            ? 'Kode OTP 6 digit telah dikirim ke email ' . $email . '. Silakan periksa kotak masuk atau spam.'
            : 'Kode verifikasi OTP berhasil dibuat.';

        return redirect()->route('password.verify.form')
            ->with('status', $statusMsg);
    }

    // -------------------------------------------------------------------------
    // LANGKAH 2: Form & Proses Verifikasi Kode OTP
    // -------------------------------------------------------------------------

    public function showVerifyOtpForm(Request $request)
    {
        if (! $request->session()->has('otp_email')) {
            return redirect()->route('password.forgot')
                ->withErrors(['email' => 'Silakan masukkan email Anda terlebih dahulu.']);
        }

        // Jika sudah lolos verifikasi OTP, langsung arahkan ke form reset password
        if ($request->session()->get('otp_verified') === true) {
            return redirect()->route('password.reset.form');
        }

        return view('auth.verify-otp', [
            'email' => $request->session()->get('otp_email'),
        ]);
    }

    public function verifyOtp(Request $request)
    {
        if (! $request->session()->has('otp_email')) {
            return redirect()->route('password.forgot')
                ->withErrors(['email' => 'Sesi verifikasi telah kedaluwarsa. Silakan masukkan email Anda kembali.']);
        }

        // Bersihkan input OTP: hilangkan spasi, strip, atau karakter non-angka
        $rawOtp = (string) $request->input('otp');
        $cleanOtp = preg_replace('/\D/', '', $rawOtp);
        $request->merge(['otp' => $cleanOtp]);

        $request->validate([
            'otp' => ['required', 'string', 'size:6', 'regex:/^[0-9]{6}$/'],
        ], [
            'otp.required' => 'Kode OTP wajib diisi.',
            'otp.size'     => 'Kode OTP harus berupa 6 digit angka.',
            'otp.regex'    => 'Kode OTP hanya boleh berisi angka.',
        ]);

        $email       = $request->session()->get('otp_email');
        $maxAttempts = (int) config('otp.max_attempts', 5);

        // Cari OTP aktif untuk email ini (urutkan id terbaru)
        $otpRecord = PasswordOtp::where('email', $email)->latest('id')->first();

        if (! $otpRecord) {
            return back()->withErrors(['otp' => 'Kode OTP tidak ditemukan atau sudah kedaluwarsa. Silakan kirim ulang kode baru.']);
        }

        // Cek limit percobaan
        if ($otpRecord->isLocked($maxAttempts)) {
            $otpRecord->delete();
            return back()->withErrors(['otp' => 'Terlalu banyak percobaan salah. Silakan kirim ulang kode OTP baru.']);
        }

        // Cek kedaluwarsa (10 menit)
        if ($otpRecord->isExpired()) {
            $otpRecord->delete();
            return back()->withErrors(['otp' => 'Kode OTP telah kedaluwarsa (berlaku 10 menit). Silakan minta kode baru.']);
        }

        // Verifikasi hash OTP
        if (! Hash::check($cleanOtp, $otpRecord->otp_hash)) {
            $otpRecord->increment('attempts');
            $sisaPercobaan = $maxAttempts - $otpRecord->fresh()->attempts;

            if ($sisaPercobaan <= 0) {
                $otpRecord->delete();
                return back()->withErrors(['otp' => 'Batas percobaan verifikasi habis. Silakan kirim ulang kode OTP baru.']);
            }

            return back()->withErrors([
                'otp' => "Kode OTP yang Anda masukkan salah. Sisa kesempatan: {$sisaPercobaan} kali.",
            ]);
        }

        // OTP Valid! Tandai di session bahwa user telah terverifikasi
        $request->session()->put('otp_verified', true);
        $request->session()->save();

        return redirect()->route('password.reset.form')
            ->with('status', 'Kode OTP berhasil diverifikasi! Silakan buat kata sandi baru Anda.');
    }

    public function resendOtp(Request $request)
    {
        if (! $request->session()->has('otp_email')) {
            return redirect()->route('password.forgot');
        }

        $email = $request->session()->get('otp_email');

        try {
            $otp = $this->otpService->generate($email);
            $this->otpService->dispatch($otp, $email, $request);

            $request->session()->put('otp_verified', false);
            $request->session()->save();

            return redirect()->route('password.verify.form')
                ->with('status', 'Kode OTP baru telah berhasil dikirim ke ' . $email . '. Silakan periksa email Anda.');
        } catch (\Throwable $e) {
            Log::error('Gagal kirim ulang OTP: ' . $e->getMessage());

            return back()->withErrors(['otp' => 'Gagal mengirim ulang email OTP. Silakan coba lagi beberapa saat lagi.']);
        }
    }

    // -------------------------------------------------------------------------
    // LANGKAH 3: Form & Proses Buat Password Baru (Hanya jika OTP sudah lolos)
    // -------------------------------------------------------------------------

    public function showResetForm(Request $request)
    {
        // Tolak jika belum isi email atau belum lulus verifikasi OTP
        if (! $request->session()->has('otp_email') || ! $request->session()->get('otp_verified')) {
            return redirect()->route('password.forgot')
                ->withErrors(['email' => 'Silakan lakukan verifikasi kode OTP terlebih dahulu.']);
        }

        return view('auth.reset-password', [
            'email' => $request->session()->get('otp_email'),
        ]);
    }

    public function resetPassword(Request $request)
    {
        if (! $request->session()->has('otp_email') || ! $request->session()->get('otp_verified')) {
            return redirect()->route('password.forgot')
                ->withErrors(['email' => 'Sesi reset password telah berakhir. Silakan ulangi langkah verifikasi.']);
        }

        $email = $request->session()->get('otp_email');

        $request->validate([
            'password' => ['required', Password::min(8), 'confirmed'],
        ], [
            'password.required'  => 'Password baru wajib diisi.',
            'password.min'       => 'Password baru minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ]);

        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if (! $user) {
            $request->session()->forget(['otp_email', 'otp_verified']);
            return redirect()->route('password.forgot')
                ->withErrors(['email' => 'Akun tidak ditemukan. Silakan hubungi admin.']);
        }

        // Pastikan password baru tidak sama dengan password lama
        if (Hash::check($request->password, $user->password)) {
            return back()->withErrors([
                'password' => 'Password baru tidak boleh sama dengan kata sandi lama Anda.',
            ]);
        }

        // Update password baru dengan bcrypt hash
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // Jika ada flag must_change_password (lulusan/reset), matikan
        if (Schema::hasColumn('users', 'must_change_password')) {
            $user->update(['must_change_password' => false]);
        }

        // Hapus OTP dari database & bersihkan session
        PasswordOtp::where('email', $email)->delete();
        $request->session()->forget(['otp_email', 'otp_verified']);
        $request->session()->save();

        return redirect()->route('login')
            ->with('success', 'Kata sandi akun Anda berhasil diperbarui! Silakan masuk dengan kata sandi baru.');
    }
}

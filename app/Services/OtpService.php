<?php

namespace App\Services;

use App\Models\PasswordOtp;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * OtpService — abstraksi pembuatan dan pengiriman OTP reset password.
 *
 * Mode dikontrol lewat config('otp.mode') / env RESET_OTP_MODE:
 *   - 'display'  : OTP dikirim ke session flash, ditampilkan satu kali di layar (default dev).
 *   - 'email'    : OTP dikirim via Mailable ke email pengguna (implementasikan di masa depan
 *                  dengan menambahkan branch 'email' di dispatch() tanpa mengubah kontrak ini).
 */
class OtpService
{
    /** Durasi OTP valid dalam menit */
    private const TTL_MINUTES = 10;

    /** Panjang OTP (digit) */
    private const OTP_LENGTH = 6;

    /**
     * Buat OTP baru untuk email tertentu.
     * Jika sudah ada OTP lama untuk email yang sama, hapus dulu (satu OTP aktif per email).
     *
     * @return string  OTP plaintext (untuk dikirim ke pengguna, TIDAK disimpan di DB)
     */
    public function generate(string $email): string
    {
        $normalizedEmail = strtolower(trim($email));

        // Hapus OTP lama untuk email ini agar tidak ada lebih dari satu aktif
        PasswordOtp::where('email', $normalizedEmail)->delete();

        // Buat OTP 6 digit, boleh diawali nol
        $otp = str_pad((string) random_int(0, 999999), self::OTP_LENGTH, '0', STR_PAD_LEFT);

        PasswordOtp::create([
            'email'      => $normalizedEmail,
            'otp_hash'   => Hash::make($otp),  // simpan hash, bukan plaintext
            'attempts'   => 0,
            'expires_at' => now()->addMinutes((int) config('otp.ttl_minutes', self::TTL_MINUTES)),
        ]);

        return $otp;
    }

    /**
     * Kirim OTP ke pengguna sesuai mode yang dikonfigurasi.
     *
     * @param  string  $otp    OTP plaintext
     * @param  string  $email  Email tujuan
     * @param  \Illuminate\Http\Request  $request  Untuk menyimpan ke session flash
     */
    public function dispatch(string $otp, string $email, \Illuminate\Http\Request $request): void
    {
        $mode = config('otp.mode', 'display');

        match ($mode) {
            'display' => $this->dispatchDisplay($otp, $request),
            'email'   => $this->dispatchEmail($otp, $email),
            default   => $this->dispatchDisplay($otp, $request),
        };
    }

    /**
     * Mode DISPLAY: simpan OTP plaintext ke session flash.
     * OTP hanya tersedia SATU KALI pada request berikutnya (session flash).
     */
    private function dispatchDisplay(string $otp, \Illuminate\Http\Request $request): void
    {
        $request->session()->flash('otp_code', $otp);
    }

    /**
     * Mode EMAIL: kirim OTP via Mailable ke email pengguna.
     */
    private function dispatchEmail(string $otp, string $email): void
    {
        $user = User::where('email', $email)->first();
        $userName = $user?->fullname ?? 'Pengguna';
        $ttl = (int) config('otp.ttl_minutes', self::TTL_MINUTES);

        \Illuminate\Support\Facades\Mail::to($email)->send(new \App\Mail\OtpMail($otp, $userName, $ttl));
    }
}

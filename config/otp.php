<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Mode Pengiriman OTP Reset Password
    |--------------------------------------------------------------------------
    |
    | 'display' : OTP ditampilkan langsung di layar (session flash).
    |             Cocok untuk environment lokal/dev atau sistem internal tertutup.
    |             RISIKO: Siapa pun yang mengakses layar/sesi dapat melihat OTP.
    |
    | 'email'   : OTP dikirim ke email pengguna melalui Mailable.
    |             Memerlukan App\Mail\OtpMail dan konfigurasi SMTP.
    |             Implementasikan di OtpService::dispatchEmail().
    |
    */
    'mode' => env('RESET_OTP_MODE', 'display'),

    /*
    | Durasi validitas OTP dalam menit.
    */
    'ttl_minutes' => env('RESET_OTP_TTL', 10),

    /*
    | Maksimal percobaan OTP salah sebelum hangus.
    */
    'max_attempts' => env('RESET_OTP_MAX_ATTEMPTS', 5),
];

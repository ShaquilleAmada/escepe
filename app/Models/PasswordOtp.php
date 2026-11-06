<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordOtp extends Model
{
    protected $fillable = [
        'email',
        'otp_hash',
        'attempts',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'attempts'   => 'integer',
    ];

    /**
     * Periksa apakah OTP sudah kedaluwarsa.
     */
    public function isExpired(): bool
    {
        return now()->isAfter($this->expires_at);
    }

    /**
     * Periksa apakah percobaan sudah melebihi batas maksimal.
     */
    public function isLocked(int $maxAttempts = 5): bool
    {
        return $this->attempts >= $maxAttempts;
    }
}

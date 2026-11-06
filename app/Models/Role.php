<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    // Kunci Konstanta Role ID Sistem SCP
    public const ADMIN = 1;
    public const GURU = 2;
    public const SISWA = 3;
    public const ORANG_TUA = 4;
    public const BK = 5;
    public const WALI_KELAS = 6;

    protected $fillable = [
        'role_name',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}

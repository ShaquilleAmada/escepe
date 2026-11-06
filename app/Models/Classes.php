<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Classes extends Model
{
    use HasFactory;

    protected $table = 'classes';

    public const GRADE_LEVELS = ['X', 'XI', 'XII'];
    public const MAJORS = ['RPL', 'TKJ', 'DKV', 'BR', 'BD', 'LPS', 'AKL', 'MP'];
    public const SECTIONS = ['1', '2', '3', '4'];

    protected $fillable = [
        'class_name',
        'grade_level',
        'major',
        'section',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'class_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'class_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(User::class, 'class_id')
            ->where('role_id', 3)
            ->where(function ($q) {
                $q->where('student_status', 'aktif')
                  ->orWhereNull('student_status');
            });
    }

    public function homeroomTeacher()
    {
        return $this->hasOne(User::class, 'class_id')
            ->where('role_id', 6);
    }

    public function walas()
    {
        return $this->homeroomTeacher();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'role_id',
        'parent_id',
        'class_id',
        'student_status',
        'graduation_year',
        'last_class_name',
        'username',
        'password',
        'fullname',
        'nip_nisn',
        'email',
        'phone',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(User::class, 'parent_id');
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'teacher_id');
    }

    public function leaveRequestsAsStudent(): HasMany
    {
        return $this->hasMany(LeaveRequest::class, 'student_id');
    }

    public function leaveRequestsAsApplicant(): HasMany
    {
        return $this->hasMany(LeaveRequest::class, 'applicant_id');
    }

    public function leaveRequestsAsVerifier(): HasMany
    {
        return $this->hasMany(LeaveRequest::class, 'verified_by');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'student_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CounselingLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'counselor_id',
        'date',
        'consecutive_alpa_days',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'consecutive_alpa_days' => 'integer',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function counselor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counselor_id');
    }
}


<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'applicant_id',
        'leave_type',
        'verified_by',
        'is_bypass',
        'decided_at',
        'start_date',
        'end_date',
        'reason',
        'rejection_reason',
        'document_path',
        'status_approval',
    ];

    protected $casts = [
        'is_bypass' => 'boolean',
        'decided_at' => 'datetime',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    protected $appends = [
        'document_url',
    ];

    public function getDocumentUrlAttribute(): ?string
    {
        return $this->document_path ? route('api.leave-requests.document', ['id' => $this->id]) : null;
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applicant_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}

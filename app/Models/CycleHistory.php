<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CycleHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'cycle_name',
        'start_date',
        'end_date',
        'class_mapping_summary',
        'changed_by',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}

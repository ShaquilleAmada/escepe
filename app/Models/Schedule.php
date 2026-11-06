<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Schedule extends Model
{
    use HasFactory;

    /**
     * Alokasi Waktu Jam Pelajaran per Hari SMKN 1 Bantul
     */
    public const DAILY_PERIODS = [
        'Senin' => [
            1  => ['start' => '08:00', 'end' => '08:35', 'label' => 'Jam Ke-1 (08:00 - 08:35)'],
            2  => ['start' => '08:35', 'end' => '09:10', 'label' => 'Jam Ke-2 (08:35 - 09:10)'],
            3  => ['start' => '09:10', 'end' => '09:45', 'label' => 'Jam Ke-3 (09:10 - 09:45)'],
            4  => ['start' => '10:00', 'end' => '10:35', 'label' => 'Jam Ke-4 (10:00 - 10:35)'],
            5  => ['start' => '10:35', 'end' => '11:10', 'label' => 'Jam Ke-5 (10:35 - 11:10)'],
            6  => ['start' => '11:10', 'end' => '11:45', 'label' => 'Jam Ke-6 (11:10 - 11:45)'],
            7  => ['start' => '12:45', 'end' => '13:25', 'label' => 'Jam Ke-7 (12:45 - 13:25)'],
            8  => ['start' => '13:25', 'end' => '14:00', 'label' => 'Jam Ke-8 (13:25 - 14:00)'],
            9  => ['start' => '14:00', 'end' => '14:35', 'label' => 'Jam Ke-9 (14:00 - 14:35)'],
            10 => ['start' => '14:35', 'end' => '15:10', 'label' => 'Jam Ke-10 (14:35 - 15:10)'],
        ],
        'Selasa' => [
            1  => ['start' => '07:30', 'end' => '08:10', 'label' => 'Jam Ke-1 (07:30 - 08:10)'],
            2  => ['start' => '08:10', 'end' => '08:50', 'label' => 'Jam Ke-2 (08:10 - 08:50)'],
            3  => ['start' => '08:50', 'end' => '09:30', 'label' => 'Jam Ke-3 (08:50 - 09:30)'],
            4  => ['start' => '09:45', 'end' => '10:25', 'label' => 'Jam Ke-4 (09:45 - 10:25)'],
            5  => ['start' => '10:25', 'end' => '11:05', 'label' => 'Jam Ke-5 (10:25 - 11:05)'],
            6  => ['start' => '11:05', 'end' => '11:45', 'label' => 'Jam Ke-6 (11:05 - 11:45)'],
            7  => ['start' => '12:45', 'end' => '13:25', 'label' => 'Jam Ke-7 (12:45 - 13:25)'],
            8  => ['start' => '13:25', 'end' => '14:00', 'label' => 'Jam Ke-8 (13:25 - 14:00)'],
            9  => ['start' => '14:00', 'end' => '14:35', 'label' => 'Jam Ke-9 (14:00 - 14:35)'],
            10 => ['start' => '14:35', 'end' => '15:10', 'label' => 'Jam Ke-10 (14:35 - 15:10)'],
        ],
        'Rabu' => [
            1  => ['start' => '07:15', 'end' => '07:55', 'label' => 'Jam Ke-1 (07:15 - 07:55)'],
            2  => ['start' => '07:55', 'end' => '08:35', 'label' => 'Jam Ke-2 (07:55 - 08:35)'],
            3  => ['start' => '08:35', 'end' => '09:15', 'label' => 'Jam Ke-3 (08:35 - 09:15)'],
            4  => ['start' => '09:30', 'end' => '10:10', 'label' => 'Jam Ke-4 (09:30 - 10:10)'],
            5  => ['start' => '10:10', 'end' => '10:50', 'label' => 'Jam Ke-5 (10:10 - 10:50)'],
            6  => ['start' => '10:50', 'end' => '11:30', 'label' => 'Jam Ke-6 (10:50 - 11:30)'],
            7  => ['start' => '12:30', 'end' => '13:10', 'label' => 'Jam Ke-7 (12:30 - 13:10)'],
            8  => ['start' => '13:10', 'end' => '13:50', 'label' => 'Jam Ke-8 (13:10 - 13:50)'],
            9  => ['start' => '13:50', 'end' => '14:30', 'label' => 'Jam Ke-9 (13:50 - 14:30)'],
            10 => ['start' => '14:30', 'end' => '15:10', 'label' => 'Jam Ke-10 (14:30 - 15:10)'],
        ],
        'Kamis' => [
            1  => ['start' => '07:15', 'end' => '07:55', 'label' => 'Jam Ke-1 (07:15 - 07:55)'],
            2  => ['start' => '07:55', 'end' => '08:35', 'label' => 'Jam Ke-2 (07:55 - 08:35)'],
            3  => ['start' => '08:35', 'end' => '09:15', 'label' => 'Jam Ke-3 (08:35 - 09:15)'],
            4  => ['start' => '09:30', 'end' => '10:10', 'label' => 'Jam Ke-4 (09:30 - 10:10)'],
            5  => ['start' => '10:10', 'end' => '10:50', 'label' => 'Jam Ke-5 (10:10 - 10:50)'],
            6  => ['start' => '10:50', 'end' => '11:30', 'label' => 'Jam Ke-6 (10:50 - 11:30)'],
            7  => ['start' => '12:30', 'end' => '13:10', 'label' => 'Jam Ke-7 (12:30 - 13:10)'],
            8  => ['start' => '13:10', 'end' => '13:50', 'label' => 'Jam Ke-8 (13:10 - 13:50)'],
            9  => ['start' => '13:50', 'end' => '14:30', 'label' => 'Jam Ke-9 (13:50 - 14:30)'],
            10 => ['start' => '14:30', 'end' => '15:10', 'label' => 'Jam Ke-10 (14:30 - 15:10)'],
        ],
        'Jumat' => [
            1  => ['start' => '07:15', 'end' => '07:55', 'label' => 'Jam Ke-1 (07:15 - 07:55)'],
            2  => ['start' => '07:55', 'end' => '08:35', 'label' => 'Jam Ke-2 (07:55 - 08:35)'],
            3  => ['start' => '08:35', 'end' => '09:15', 'label' => 'Jam Ke-3 (08:35 - 09:15)'],
            4  => ['start' => '09:30', 'end' => '10:10', 'label' => 'Jam Ke-4 (09:30 - 10:10)'],
            5  => ['start' => '10:10', 'end' => '10:50', 'label' => 'Jam Ke-5 (10:10 - 10:50)'],
            6  => ['start' => '10:50', 'end' => '11:25', 'label' => 'Jam Ke-6 (10:50 - 11:25)'],
            7  => ['start' => '12:40', 'end' => '13:15', 'label' => 'Jam Ke-7 (12:40 - 13:15)'],
            8  => ['start' => '13:15', 'end' => '13:50', 'label' => 'Jam Ke-8 (13:15 - 13:50)'],
        ],
    ];

    public const START_TIMES = [
        '07:15',
        '07:30',
        '07:55',
        '08:00',
        '08:10',
        '08:35',
        '08:50',
        '09:10',
        '09:30',
        '09:45',
        '10:00',
        '10:10',
        '10:25',
        '10:35',
        '10:50',
        '11:05',
        '11:10',
        '12:30',
        '12:40',
        '12:45',
        '13:10',
        '13:15',
        '13:25',
        '13:50',
        '14:00',
        '14:30',
        '14:35',
    ];

    public const END_TIMES = [
        '07:55',
        '08:10',
        '08:35',
        '08:50',
        '09:10',
        '09:15',
        '09:30',
        '09:45',
        '10:10',
        '10:25',
        '10:35',
        '10:50',
        '11:05',
        '11:10',
        '11:25',
        '11:30',
        '11:45',
        '13:10',
        '13:15',
        '13:25',
        '13:50',
        '14:00',
        '14:30',
        '14:35',
        '15:10',
        '15:30',
    ];

    protected $fillable = [
        'teacher_id',
        'class_id',
        'room_id',
        'subject',
        'day_of_week',
        'start_time',
        'end_time',
        'start_period',
        'end_period',
        'cycle_type',
    ];

    /**
     * Dapatkan teks keterangan Jam Ke (Contoh: 'Jam Ke 1 - 3' atau 'Jam Ke 4')
     */
    public function getPeriodTextAttribute(): ?string
    {
        if ($this->start_period && $this->end_period) {
            return $this->start_period == $this->end_period 
                ? "Jam Ke-{$this->start_period}" 
                : "Jam Ke-{$this->start_period} s/d {$this->end_period}";
        }

        $periods = self::DAILY_PERIODS[$this->day_of_week] ?? null;
        if (!$periods) return null;

        $start = substr($this->start_time, 0, 5);
        $end = substr($this->end_time, 0, 5);

        $sp = null;
        $ep = null;

        foreach ($periods as $p => $slot) {
            if ($slot['start'] === $start) $sp = $p;
            if ($slot['end'] === $end) $ep = $p;
        }

        if ($sp && $ep) {
            return $sp == $ep ? "Jam Ke-{$sp}" : "Jam Ke-{$sp} s/d {$ep}";
        }

        return null;
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function journals(): HasMany
    {
        return $this->hasMany(Journal::class);
    }
}

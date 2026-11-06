<?php

namespace App\Services;

use App\Models\Classes;
use App\Models\Journal;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\Schedule;
use App\Models\SystemConfiguration;
use App\Models\User;
use Carbon\Carbon;

class AttendanceRecapService
{
    /**
     * Menghitung total hari efektif (Senin s/d Jumat).
     */
    public static function getEffectiveDays(string $startDate, string $endDate): int
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        if ($start->gt($end)) {
            return 0;
        }

        $effectiveDays = 0;
        $current = $start->copy();
        while ($current->lte($end)) {
            if ($current->isWeekday()) {
                $effectiveDays++;
            }
            $current->addDay();
        }

        return $effectiveDays;
    }

    /**
     * Menentukan status rekap kelas pada tanggal tertentu: 'Belum Final' vs 'Final / Bersih'
     *
     * 'Belum Final' (kuning): Masih ada izin pending ATAU sesi KBM hari itu belum semua terisi jurnal/presensi.
     * 'Final / Bersih' (hijau): Semua izin hari itu sudah diputuskan DAN presensi semua sesi KBM hari itu sudah lengkap.
     */
    public static function getClassDayStatus(int $classId, string $date): array
    {
        // 1. Cek izin berstatus pending pada tanggal tersebut untuk kelas ini
        $hasPendingLeave = LeaveRequest::where('status_approval', 'pending')
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->whereHas('student', function ($q) use ($classId) {
                $q->where('class_id', $classId);
            })
            ->exists();

        if ($hasPendingLeave) {
            return [
                'status'       => 'belum_final',
                'label'        => 'Belum Final',
                'color'        => 'warning',
                'reason'       => 'Terdapat pengajuan izin yang masih berstatus pending.',
                'is_final'     => false,
            ];
        }

        // 2. Cek apakah hari non-efektif KBM (akhir pekan)
        $carbonDate = Carbon::parse($date);
        if ($carbonDate->isWeekend()) {
            return [
                'status'       => 'final',
                'label'        => 'Final / Bersih',
                'color'        => 'success',
                'reason'       => 'Hari non-efektif KBM (akhir pekan).',
                'is_final'     => true,
            ];
        }

        // 3. Cek jadwal KBM kelas ini pada hari & siklus aktif
        $dayName = match ($carbonDate->dayOfWeek) {
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            default => null,
        };

        if (!$dayName) {
            return [
                'status'   => 'final',
                'label'    => 'Final / Bersih',
                'color'    => 'success',
                'reason'   => 'Tidak ada KBM terjadwal.',
                'is_final' => true,
            ];
        }

        $activeCycle = SystemConfiguration::first()->active_cycle ?? 'Putaran 1';

        $scheduledCount = Schedule::where('class_id', $classId)
            ->where('day_of_week', $dayName)
            ->where(function ($q) use ($activeCycle) {
                $q->where('cycle_type', $activeCycle)
                  ->orWhere('cycle_type', 'Semua Siklus');
            })
            ->count();

        // Jika tidak ada jadwal KBM kelas ini di hari tersebut
        if ($scheduledCount === 0) {
            return [
                'status'   => 'final',
                'label'    => 'Final / Bersih',
                'color'    => 'success',
                'reason'   => 'Tidak ada jadwal KBM pada hari ini.',
                'is_final' => true,
            ];
        }

        // Cek jurnal mengajar yang sudah terisi
        $loggedJournalsCount = Journal::whereDate('date', $date)
            ->whereHas('schedule', function ($q) use ($classId, $dayName, $activeCycle) {
                $q->where('class_id', $classId)
                  ->where('day_of_week', $dayName)
                  ->where(function ($sub) use ($activeCycle) {
                      $sub->where('cycle_type', $activeCycle)
                          ->orWhere('cycle_type', 'Semua Siklus');
                  });
            })
            ->count();

        if ($loggedJournalsCount < $scheduledCount) {
            return [
                'status'       => 'belum_final',
                'label'        => 'Belum Final',
                'color'        => 'warning',
                'reason'       => "Presensi belum lengkap ({$loggedJournalsCount} dari {$scheduledCount} sesi KBM terisi).",
                'is_final'     => false,
            ];
        }

        return [
            'status'       => 'final',
            'label'        => 'Final / Bersih',
            'color'        => 'success',
            'reason'       => "Semua permohonan izin diputuskan & {$loggedJournalsCount} sesi KBM lengkap terisi.",
            'is_final'     => true,
        ];
    }
}

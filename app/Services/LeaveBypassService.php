<?php

namespace App\Services;

use App\Models\User;
use App\Models\LeaveRequest;
use App\Models\Schedule;
use App\Models\SystemConfiguration;
use App\Models\TeacherAbsence;
use App\Models\Role;
use Carbon\Carbon;

class LeaveBypassService
{
    /**
     * Memeriksa apakah seorang pengguna berwenang memverifikasi surat izin,
     * serta menentukan apakah persetujuan/penolakan ini masuk kategori Bypass BK.
     *
     * @return array ['allowed' => bool, 'is_bypass' => bool, 'message' => string]
     */
    public function evaluateVerificationAuthority(User $user, LeaveRequest $leave): array
    {
        // 1. Waka Kurikulum / Admin selalu berhak
        if ($user->role_id === Role::ADMIN) {
            return [
                'allowed'   => true,
                'is_bypass' => false,
                'message'   => 'Verifikasi diizinkan sebagai Administrator/Waka Kurikulum.',
            ];
        }

        $student = $leave->student;
        if (!$student) {
            return [
                'allowed'   => false,
                'is_bypass' => false,
                'message'   => 'Data siswa pemohon tidak ditemukan.',
            ];
        }

        $studentClassId = $student->class_id;

        // 2. Wali Kelas
        if ($user->role_id === Role::WALI_KELAS) {
            if (!$user->class_id || (int)$user->class_id !== (int)$studentClassId) {
                return [
                    'allowed'   => false,
                    'is_bypass' => false,
                    'message'   => 'Akses ditolak: Anda hanya berhak memverifikasi surat izin untuk siswa di kelas binaan Anda.',
                ];
            }

            return [
                'allowed'   => true,
                'is_bypass' => false,
                'message'   => 'Verifikasi diizinkan sebagai Wali Kelas resmi.',
            ];
        }

        // 3. Guru BK (Mode Bypass Dinamis)
        if ($user->role_id === Role::BK) {
            // Cari wali kelas resmi untuk kelas siswa ini
            $walas = User::where('role_id', Role::WALI_KELAS)
                ->where('class_id', $studentClassId)
                ->first();

            $now = Carbon::now('Asia/Jakarta');
            $todayDate = $now->toDateString();
            $startDate = Carbon::parse($leave->start_date)->toDateString();

            // Trigger (a): Walas ditandai "Berhalangan Hadir" pada tanggal tersebut oleh Waka
            if ($walas) {
                $isWalasAbsent = TeacherAbsence::where('teacher_id', $walas->id)
                    ->whereDate('date', $todayDate)
                    ->exists();

                if ($isWalasAbsent) {
                    return [
                        'allowed'   => true,
                        'is_bypass' => true,
                        'message'   => 'Bypass BK aktif seketika: Wali Kelas ditandai berhalangan hadir hari ini oleh Waka Kurikulum.',
                    ];
                }
            } else {
                // Jika kelas belum memiliki wali kelas, BK langsung berhak bypass
                return [
                    'allowed'   => true,
                    'is_bypass' => true,
                    'message'   => 'Bypass BK aktif: Kelas ini belum memiliki Wali Kelas definitif.',
                ];
            }

            // Trigger (b): Izin lintas hari / masa depan
            // Titik mulai bypass dihitung dari HARI PERTAMA izin berlaku, bukan dari tanggal pengajuan
            if ($todayDate < $startDate) {
                return [
                    'allowed'   => false,
                    'is_bypass' => false,
                    'message'   => "Bypass BK belum aktif: Surat izin berlaku mulai tanggal {$startDate}. Sebelum hari pertama berlaku, verifikasi merupakan hak eksklusif Wali Kelas.",
                ];
            }

            // Jika hari ini adalah hari pertama izin berlaku (atau hari berikutnya di rentang izin)
            if ($todayDate > $startDate) {
                return [
                    'allowed'   => true,
                    'is_bypass' => true,
                    'message'   => 'Bypass BK aktif: Hari pertama izin telah berlalu dan izin masih berstatus pending.',
                ];
            }

            // Jika hari ini = start_date izin:
            // Cek jadwal KBM pertama kelas siswa pada hari ini berdasarkan siklus aktif
            $dayMap = [
                1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis',
                5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu',
            ];
            $dayName = $dayMap[(int)$now->format('N')] ?? 'Senin';

            $config = SystemConfiguration::first();
            $activeCycle = $config->active_cycle ?? 'Putaran 1';

            $firstSchedule = Schedule::where('class_id', $studentClassId)
                ->where('day_of_week', $dayName)
                ->where(function ($q) use ($activeCycle) {
                    $q->where('cycle_type', $activeCycle)
                      ->orWhere('cycle_type', 'semua');
                })
                ->orderBy('start_time', 'asc')
                ->first();

            // Jika tidak ada KBM pada hari itu, hanya trigger (a) yang berlaku
            if (!$firstSchedule) {
                return [
                    'allowed'   => false,
                    'is_bypass' => false,
                    'message'   => "Bypass BK belum aktif: Tidak ada jadwal KBM kelas hari ini ({$dayName}). Pada hari tanpa KBM, bypass hanya dapat aktif jika Waka menandai Walas berhalangan.",
                ];
            }

            $firstStartTime = Carbon::parse($todayDate . ' ' . $firstSchedule->start_time, 'Asia/Jakarta');
            $bypassDeadline = $firstStartTime->copy()->addMinutes(30);

            if ($now->gte($bypassDeadline)) {
                return [
                    'allowed'   => true,
                    'is_bypass' => true,
                    'message'   => "Bypass BK aktif: Batas waktu toleransi Wali Kelas (30 menit setelah jam KBM pertama mulai pukul {$firstSchedule->start_time}) telah terlewati.",
                ];
            }

            $remainingMinutes = $now->diffInMinutes($bypassDeadline, false);
            return [
                'allowed'   => false,
                'is_bypass' => false,
                'message'   => "Bypass BK belum aktif: Masih dalam 30 menit hak eksklusif Wali Kelas (KBM mulai {$firstSchedule->start_time}, batas bypass {$bypassDeadline->format('H:i')}, sisa {$remainingMinutes} menit).",
            ];
        }

        return [
            'allowed'   => false,
            'is_bypass' => false,
            'message'   => 'Akses ditolak: Peran Anda tidak memiliki wewenang verifikasi perizinan siswa.',
        ];
    }
}


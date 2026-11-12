<?php

namespace App\Services;

use App\Models\LeaveRequest;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\SystemConfiguration;
use App\Models\TeacherAbsence;
use App\Models\User;
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
        $userRoleId = (int) $user->role_id;

        // 1. Waka Kurikulum / Admin selalu berhak
        if ($userRoleId === Role::ADMIN) {
            return [
                'allowed' => true,
                'is_bypass' => false,
                'message' => 'Verifikasi diizinkan sebagai Administrator/Waka Kurikulum.',
            ];
        }

        $student = $leave->student;
        if (! $student) {
            return [
                'allowed' => false,
                'is_bypass' => false,
                'message' => 'Data siswa pemohon tidak ditemukan.',
            ];
        }

        $studentClassId = (int) $student->class_id;

        // 2. Wali Kelas
        if ($userRoleId === Role::WALI_KELAS) {
            if (! $user->class_id || (int) $user->class_id !== $studentClassId) {
                return [
                    'allowed' => false,
                    'is_bypass' => false,
                    'message' => 'Akses ditolak: Anda hanya berhak memverifikasi surat izin untuk siswa di kelas binaan Anda.',
                ];
            }

            return [
                'allowed' => true,
                'is_bypass' => false,
                'message' => 'Verifikasi diizinkan sebagai Wali Kelas resmi.',
            ];
        }

        // 3. Guru BK (Mode Bypass Dinamis per Rombel Binaan)
        if ($userRoleId === Role::BK) {
            // Evaluasi hak wilayah rombel binaan BK (Pilihan A)
            $studentClass = $leave->student?->schoolClass ?? Classes::find($studentClassId);
            if ($studentClass && $studentClass->bk_id && (int) $studentClass->bk_id !== (int) $user->id) {
                $assignedBk = $studentClass->bkTeacher?->fullname ?? 'Guru BK penanggung jawab';
                return [
                    'allowed' => false,
                    'is_bypass' => false,
                    'message' => "Akses ditolak: Anda hanya berwenang memverifikasi perizinan siswa untuk rombel binaan Anda. Kelas {$studentClass->class_name} berada di bawah tanggung jawab {$assignedBk}.",
                ];
            }

            // Cari seluruh wali kelas resmi untuk kelas siswa ini
            $walasIds = User::where('role_id', Role::WALI_KELAS)
                ->where('class_id', $studentClassId)
                ->pluck('id');

            $now = Carbon::now('Asia/Jakarta');
            $todayDate = $now->toDateString();
            $startDate = Carbon::parse($leave->start_date)->toDateString();

            // Trigger (a): Walas ditandai "Berhalangan Hadir" pada tanggal tersebut oleh Waka
            if ($walasIds->isNotEmpty()) {
                $isWalasAbsent = TeacherAbsence::whereIn('teacher_id', $walasIds)
                    ->whereDate('date', $todayDate)
                    ->exists();

                if ($isWalasAbsent) {
                    return [
                        'allowed' => true,
                        'is_bypass' => true,
                        'message' => 'Bypass BK aktif seketika: Wali Kelas ditandai berhalangan hadir hari ini oleh Waka Kurikulum.',
                    ];
                }
            } else {
                // Jika kelas belum memiliki wali kelas, BK langsung berhak bypass
                return [
                    'allowed' => true,
                    'is_bypass' => true,
                    'message' => 'Bypass BK aktif: Kelas ini belum memiliki Wali Kelas definitif.',
                ];
            }

            // Trigger (b): Izin lintas hari / masa depan
            // Titik mulai bypass dihitung dari HARI PERTAMA izin berlaku, bukan dari tanggal pengajuan
            if ($todayDate < $startDate) {
                return [
                    'allowed' => false,
                    'is_bypass' => false,
                    'message' => "Bypass BK belum aktif: Surat izin berlaku mulai tanggal {$startDate}. Sebelum hari pertama berlaku, verifikasi merupakan hak eksklusif Wali Kelas.",
                ];
            }

            // Jika hari ini adalah hari pertama izin berlaku (atau hari berikutnya di rentang izin)
            if ($todayDate > $startDate) {
                return [
                    'allowed' => true,
                    'is_bypass' => true,
                    'message' => 'Bypass BK aktif: Hari pertama izin telah berlalu dan izin masih berstatus pending.',
                ];
            }

            // Jika hari ini = start_date izin:
            // Cek jadwal KBM pertama kelas siswa pada hari ini berdasarkan siklus aktif
            $dayMap = [
                1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis',
                5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu',
            ];
            $dayName = $dayMap[(int) $now->format('N')] ?? 'Senin';

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
            if (! $firstSchedule) {
                return [
                    'allowed' => false,
                    'is_bypass' => false,
                    'message' => "Bypass BK belum aktif: Tidak ada jadwal KBM kelas hari ini ({$dayName}). Pada hari tanpa KBM, bypass hanya dapat aktif jika Waka menandai Walas berhalangan.",
                ];
            }

            $firstStartTime = Carbon::parse($todayDate.' '.$firstSchedule->start_time, 'Asia/Jakarta');
            $bypassDeadline = $firstStartTime->copy()->addMinutes(30);

            if ($now->gte($bypassDeadline)) {
                return [
                    'allowed' => true,
                    'is_bypass' => true,
                    'message' => "Bypass BK aktif: Batas waktu toleransi Wali Kelas (30 menit setelah jam KBM pertama mulai pukul {$firstSchedule->start_time}) telah terlewati.",
                ];
            }

            $remainingMinutes = $now->diffInMinutes($bypassDeadline, false);

            return [
                'allowed' => false,
                'is_bypass' => false,
                'message' => "Bypass BK belum aktif: Masih dalam 30 menit hak eksklusif Wali Kelas (KBM mulai {$firstSchedule->start_time}, batas bypass {$bypassDeadline->format('H:i')}, sisa {$remainingMinutes} menit).",
            ];
        }

        return [
            'allowed' => false,
            'is_bypass' => false,
            'message' => 'Akses ditolak: Peran Anda tidak memiliki wewenang verifikasi perizinan siswa.',
        ];
    }
}

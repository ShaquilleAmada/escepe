<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations to delete all dummy test data.
     */
    public function up(): void
    {
        // 1. Daftar username akun dummy yang pernah di-seed
        $dummyUsernames = [
            '19003',         // Siswa Test
            'walas_a',       // Wali Kelas A
            'walas_dkv',     // Wali Kelas DKV
            'guru_bk_test',  // Guru BK Test
            'guru_budi',     // Budi Santoso, S.Kom
            'walas_siti',    // Siti Aminah, M.Pd
            'guru_dewi',     // Dewi Lestari, S.Pd
            'bk_ahmad',      // Ahmad Fauzi, S.Pd (BK)
            '10001', 'P10001', // Aditya Pratama & Ortu
            '10002', 'P10002', // Bella Safitri & Ortu
            '10003', 'P10003', // Candra Wijaya & Ortu
            '10004', 'P10004', // Dinda Kirana & Ortu
            '10005', 'P10005', // Eko Prasetyo & Ortu
            'guru1',
            'gurubk',
            'siswa01',
            'P5412',
            'wali_kelas',
        ];

        $dummyUsers = DB::table('users')->whereIn('username', $dummyUsernames)->get();
        $dummyUserIds = $dummyUsers->pluck('id')->toArray();

        if (!empty($dummyUserIds)) {
            // Hapus Leave Requests terkait dummy users
            if (Schema::hasTable('leave_requests')) {
                DB::table('leave_requests')
                    ->whereIn('student_id', $dummyUserIds)
                    ->orWhereIn('created_by', $dummyUserIds)
                    ->orWhereIn('approved_by', $dummyUserIds)
                    ->delete();
            }

            // Hapus Schedules terkait guru dummy
            if (Schema::hasTable('schedules')) {
                DB::table('schedules')
                    ->whereIn('teacher_id', $dummyUserIds)
                    ->delete();
            }

            // Hapus Token Personal Access terkait dummy users
            if (Schema::hasTable('personal_access_tokens')) {
                DB::table('personal_access_tokens')
                    ->where('tokenable_type', 'App\\Models\\User')
                    ->whereIn('tokenable_id', $dummyUserIds)
                    ->delete();
            }

            // Hapus Presensi terkait siswa dummy
            if (Schema::hasTable('attendances')) {
                DB::table('attendances')
                    ->whereIn('student_id', $dummyUserIds)
                    ->delete();
            }

            // Hapus Catatan BK terkait dummy users
            if (Schema::hasTable('counseling_logs')) {
                DB::table('counseling_logs')
                    ->whereIn('student_id', $dummyUserIds)
                    ->orWhereIn('bk_teacher_id', $dummyUserIds)
                    ->delete();
            }

            // Hapus User Dummy itu sendiri
            DB::table('users')->whereIn('id', $dummyUserIds)->delete();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Data dummy tidak perlu dikembalikan saat rollback
    }
};

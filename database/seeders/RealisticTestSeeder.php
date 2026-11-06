<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\User;
use App\Models\Classes;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\SchoolHoliday;
use App\Models\LeaveRequest;
use App\Models\SystemConfiguration;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class RealisticTestSeeder extends Seeder
{
    public function run(): void
    {
        // Pastikan Role ada
        $roles = [
            Role::ADMIN      => 'Admin',
            Role::GURU       => 'Guru',
            Role::SISWA      => 'Siswa',
            Role::ORANG_TUA  => 'OrangTua',
            Role::BK         => 'BK',
            Role::WALI_KELAS => 'WaliKelas',
        ];

        foreach ($roles as $id => $name) {
            Role::firstOrCreate(['id' => $id], ['role_name' => $name]);
        }

        $passwordHash = Hash::make('password123');

        // 1. Waka Kurikulum (Role 1)
        $waka = User::firstOrCreate(
            ['username' => 'waka_kurikulum'],
            [
                'role_id'  => Role::ADMIN,
                'fullname' => 'Waka Kurikulum SMK 1 Bantul',
                'email'    => 'waka@smkn1bantul.sch.id',
                'password' => $passwordHash,
            ]
        );

        // System Configuration
        SystemConfiguration::updateOrCreate(
            ['id' => 1],
            [
                'active_cycle'       => 'Putaran 1',
                'is_schedule_locked' => false,
                'academic_year'      => '2025/2026',
                'semester'           => 'Ganjil',
                'updated_by'         => $waka->id,
            ]
        );

        // 2. Ruangan & Kelas
        $room = Room::firstOrCreate(['room_name' => 'Ruang 12']);
        $class = Classes::firstOrCreate(
            ['class_name' => 'XII RPL 1'],
            [
                'grade_level' => 'XII',
                'major'       => 'RPL',
                'section'     => '1',
            ]
        );

        // 3. Guru & Walas & BK
        $guruBudi = User::firstOrCreate(
            ['username' => 'guru_budi'],
            [
                'role_id'  => Role::GURU,
                'fullname' => 'Budi Santoso, S.Kom',
                'nip_nisn' => '198501012010019999',
                'password' => $passwordHash,
            ]
        );

        $walasSiti = User::firstOrCreate(
            ['username' => 'walas_siti'],
            [
                'role_id'  => Role::WALI_KELAS,
                'class_id' => $class->id,
                'fullname' => 'Siti Aminah, M.Pd',
                'nip_nisn' => '198703152012012002',
                'password' => $passwordHash,
            ]
        );

        $guruDewi = User::firstOrCreate(
            ['username' => 'guru_dewi'],
            [
                'role_id'  => Role::GURU,
                'fullname' => 'Dewi Lestari, S.Pd',
                'nip_nisn' => '199005202015022003',
                'password' => $passwordHash,
            ]
        );

        $bkAhmad = User::firstOrCreate(
            ['username' => 'bk_ahmad'],
            [
                'role_id'  => Role::BK,
                'fullname' => 'Ahmad Fauzi, S.Pd (BK)',
                'nip_nisn' => '198207102008011004',
                'password' => $passwordHash,
            ]
        );

        // 4. 1 Kelas berisi 5 Siswa + 5 Akun Orang Tua
        $studentsData = [
            ['nis' => '10001', 'name' => 'Aditya Pratama'],
            ['nis' => '10002', 'name' => 'Bella Safitri'],
            ['nis' => '10003', 'name' => 'Candra Wijaya'],
            ['nis' => '10004', 'name' => 'Dinda Kirana'],
            ['nis' => '10005', 'name' => 'Eko Prasetyo'],
        ];

        $createdStudents = [];
        foreach ($studentsData as $item) {
            $student = User::firstOrCreate(
                ['username' => $item['nis']],
                [
                    'role_id'        => Role::SISWA,
                    'class_id'       => $class->id,
                    'student_status' => 'aktif',
                    'fullname'       => $item['name'],
                    'nip_nisn'       => $item['nis'],
                    'password'       => $passwordHash,
                ]
            );

            $parentUsername = 'P' . $item['nis'];
            $parent = User::firstOrCreate(
                ['username' => $parentUsername],
                [
                    'role_id'        => Role::ORANG_TUA,
                    'class_id'       => $class->id,
                    'student_status' => 'aktif',
                    'fullname'       => 'Orang Tua ' . $item['name'],
                    'parent_id'      => $student->id,
                    'password'       => $passwordHash,
                ]
            );

            if ($student->parent_id !== $parent->id) {
                $student->update(['parent_id' => $parent->id]);
            }

            $createdStudents[] = $student;
        }

        // 5. 1 Jadwal KBM (Senin Jam ke-1: 07:00 - 09:30)
        $schedule = Schedule::firstOrCreate(
            [
                'class_id'    => $class->id,
                'day_of_week' => 'Senin',
                'start_time'  => '07:00:00',
                'cycle_type'  => 'Putaran 1',
            ],
            [
                'teacher_id'  => $walasSiti->id,
                'room_id'     => $room->id,
                'subject'     => 'Pemodelan Perangkat Lunak',
                'end_time'    => '09:30:00',
            ]
        );

        // 6. 1 Hari Libur di School Holidays
        SchoolHoliday::firstOrCreate(
            ['holiday_date' => '2026-11-25'],
            [
                'description' => 'Hari Guru Nasional & HUT PGRI',
                'created_by'  => $waka->id,
            ]
        );

        // 7. Pengajuan Izin Beragam Status
        $today = Carbon::today()->toDateString();

        // (a) Izin 1: Pending (Siswa 1)
        LeaveRequest::firstOrCreate(
            [
                'student_id' => $createdStudents[0]->id,
                'start_date' => $today,
            ],
            [
                'applicant_id'    => $createdStudents[0]->id,
                'leave_type'      => 'izin',
                'end_date'        => $today,
                'reason'          => 'Izin menghadiri acara keluarga',
                'status_approval' => 'pending',
            ]
        );

        // (b) Izin 2: Disetujui Walas (Siswa 2)
        LeaveRequest::firstOrCreate(
            [
                'student_id' => $createdStudents[1]->id,
                'start_date' => $today,
            ],
            [
                'applicant_id'    => $createdStudents[1]->id,
                'leave_type'      => 'sakit',
                'end_date'        => $today,
                'reason'          => 'Demam dan flu istirahat di rumah',
                'status_approval' => 'approved',
                'verified_by'     => $walasSiti->id,
                'is_bypass'       => false,
                'decided_at'      => Carbon::now(),
            ]
        );

        // (c) Izin 3: Ditolak Walas (Siswa 3)
        LeaveRequest::firstOrCreate(
            [
                'student_id' => $createdStudents[2]->id,
                'start_date' => $today,
            ],
            [
                'applicant_id'     => $createdStudents[2]->id,
                'leave_type'       => 'izin',
                'end_date'         => $today,
                'reason'           => 'Keperluan pribadi tanpa surat keterangan',
                'status_approval'  => 'rejected',
                'verified_by'      => $walasSiti->id,
                'is_bypass'        => false,
                'decided_at'       => Carbon::now(),
                'rejection_reason' => 'Surat keterangan dokter/orang tua tidak disertakan.',
            ]
        );

        // (d) Izin 4: Disetujui BK via Bypass (Siswa 4)
        LeaveRequest::firstOrCreate(
            [
                'student_id' => $createdStudents[3]->id,
                'start_date' => $today,
            ],
            [
                'applicant_id'    => $createdStudents[3]->id,
                'leave_type'      => 'izin',
                'end_date'        => $today,
                'reason'          => 'Mengikuti lomba olimpiade komputer tingkat provinsi',
                'status_approval' => 'approved',
                'verified_by'     => $bkAhmad->id,
                'is_bypass'       => true,
                'decided_at'      => Carbon::now(),
            ]
        );
    }
}

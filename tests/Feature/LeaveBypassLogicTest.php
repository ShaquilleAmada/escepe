<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Classes;
use App\Models\LeaveRequest;
use App\Models\TeacherAbsence;
use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class LeaveBypassLogicTest extends TestCase
{
    use DatabaseTransactions;

    public function test_walas_can_only_verify_own_class_leave_requests()
    {
        $classA = Classes::firstOrCreate(
            ['class_name' => 'X RPL 1'],
            ['grade_level' => 'X', 'major' => 'RPL', 'section' => '1']
        );
        $classB = Classes::firstOrCreate(
            ['class_name' => 'XI TKJ 1'],
            ['grade_level' => 'XI', 'major' => 'TKJ', 'section' => '1']
        );

        $nisWalasA = 'walas_' . rand(10000, 99999);
        $walasA = User::create([
            'username' => $nisWalasA,
            'fullname' => 'Wali Kelas A',
            'password' => bcrypt('password123'),
            'role_id'  => Role::WALI_KELAS,
            'class_id' => $classA->id,
        ]);

        $nisB = (string)rand(80000, 89999);
        $studentB = User::create([
            'username' => $nisB,
            'nip_nisn' => $nisB,
            'fullname' => 'Siswa B',
            'password' => bcrypt('password123'),
            'role_id'  => Role::SISWA,
            'class_id' => $classB->id,
        ]);

        $leaveB = LeaveRequest::create([
            'student_id'      => $studentB->id,
            'applicant_id'    => $studentB->id,
            'leave_type'      => 'izin',
            'start_date'      => Carbon::today()->toDateString(),
            'end_date'        => Carbon::today()->toDateString(),
            'reason'          => 'Ada urusan keluarga',
            'status_approval' => 'pending',
        ]);

        // Walas A mencoba verifikasi siswa di kelas B -> HARUS DITOLAK (403)
        $token = $walasA->createToken('test')->plainTextToken;
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->patchJson("/api/leave-requests/{$leaveB->id}/verify", [
                'status_approval' => 'approved',
            ]);

        $response->assertStatus(403);
    }

    public function test_bk_can_bypass_when_walas_is_marked_absent_by_waka()
    {
        $class = Classes::firstOrCreate(
            ['class_name' => 'X DKV 1'],
            ['grade_level' => 'X', 'major' => 'DKV', 'section' => '1']
        );

        $nisWalas = 'walas_' . rand(10000, 99999);
        $walas = User::create([
            'username' => $nisWalas,
            'fullname' => 'Wali Kelas DKV',
            'password' => bcrypt('password123'),
            'role_id'  => Role::WALI_KELAS,
            'class_id' => $class->id,
        ]);

        $nisBk = 'bk_' . rand(10000, 99999);
        $bk = User::create([
            'username' => $nisBk,
            'fullname' => 'Guru BK Test',
            'password' => bcrypt('password123'),
            'role_id'  => Role::BK,
        ]);

        $nisStudent = (string)rand(80000, 89999);
        $student = User::create([
            'username' => $nisStudent,
            'nip_nisn' => $nisStudent,
            'fullname' => 'Siswa DKV',
            'password' => bcrypt('password123'),
            'role_id'  => Role::SISWA,
            'class_id' => $class->id,
        ]);

        $leave = LeaveRequest::create([
            'student_id'      => $student->id,
            'applicant_id'    => $student->id,
            'leave_type'      => 'sakit',
            'start_date'      => Carbon::today()->toDateString(),
            'end_date'        => Carbon::today()->toDateString(),
            'reason'          => 'Demam tinggi',
            'status_approval' => 'pending',
        ]);

        // Tandai walas absen hari ini
        TeacherAbsence::create([
            'teacher_id' => $walas->id,
            'date'       => Carbon::today()->toDateString(),
            'reason'     => 'Izin sakit',
        ]);

        $bkToken = $bk->createToken('test')->plainTextToken;
        $response = $this->withHeader('Authorization', 'Bearer ' . $bkToken)
            ->patchJson("/api/leave-requests/{$leave->id}/verify", [
                'status_approval' => 'approved',
            ]);

        $response->assertStatus(200);
        $leave->refresh();
        $this->assertEquals('approved', $leave->status_approval);
        $this->assertTrue((bool)$leave->is_bypass);
        $this->assertEquals($bk->id, $leave->verified_by);
    }

    public function test_schedules_cud_is_forbidden_for_students()
    {
        $nisStudent = (string)rand(80000, 89999);
        $student = User::create([
            'username' => $nisStudent,
            'fullname' => 'Siswa Test',
            'password' => bcrypt('password123'),
            'role_id'  => Role::SISWA,
        ]);

        $token = $student->createToken('test')->plainTextToken;
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/schedules', [
                'teacher_id'  => 1,
                'class_id'    => 1,
                'room_id'     => 1,
                'subject'     => 'Hacked Subject',
                'day_of_week' => 'Senin',
                'start_time'  => '07:00',
                'end_time'    => '08:30',
                'cycle_type'  => 'Putaran 1',
            ]);

        // Mahasiswa/Siswa HARUS ditolak (403 Forbidden)
        $response->assertStatus(403);
    }
}

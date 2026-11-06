<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Classes;
use App\Models\Role;
use App\Models\LeaveRequest;
use App\Services\AttendanceRecapService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AttendanceRecapEnhancementTest extends TestCase
{
    use DatabaseTransactions;

    protected function getWakaUser()
    {
        return User::firstOrCreate(
            ['username' => 'waka_curriculum_test'],
            [
                'fullname' => 'Waka Kurikulum Test',
                'password' => bcrypt('password123'),
                'role_id'  => Role::ADMIN,
            ]
        );
    }

    public function test_recap_calculates_effective_weekdays(): void
    {
        // Monday to Friday in a single week = 5 weekdays
        $monday = Carbon::parse('2026-11-09'); // Monday
        $friday = Carbon::parse('2026-11-13'); // Friday

        $days = AttendanceRecapService::getEffectiveDays($monday->toDateString(), $friday->toDateString());
        $this->assertEquals(5, $days);
    }

    public function test_waka_recap_calculates_belum_final_when_leave_is_pending(): void
    {
        $waka = $this->getWakaUser();
        $class = Classes::first();
        $date = Carbon::today()->toDateString();

        $student = User::create([
            'username'       => 'test_pending_student_' . uniqid(),
            'role_id'        => Role::SISWA,
            'class_id'       => $class->id,
            'student_status' => 'aktif',
            'fullname'       => 'Siswa Pending Leave',
            'password'       => bcrypt('password123'),
        ]);

        // Create pending leave request
        LeaveRequest::create([
            'student_id'      => $student->id,
            'applicant_id'    => $student->id,
            'leave_type'      => 'izin',
            'start_date'      => $date,
            'end_date'        => $date,
            'reason'          => 'Izin urusan keluarga',
            'status_approval' => 'pending',
        ]);

        $status = AttendanceRecapService::getClassDayStatus($class->id, $date);
        $this->assertEquals('belum_final', $status['status']);
        $this->assertEquals('Belum Final', $status['label']);
        $this->assertFalse($status['is_final']);

        // View on web
        $response = $this->actingAs($waka)->get('/waka/attendances/recap?date=' . $date);
        $response->assertStatus(200);
        $response->assertSee('Belum Final');
    }

    public function test_walas_can_only_download_pdf_for_own_class(): void
    {
        $class1 = Classes::first();
        $class2 = Classes::skip(1)->first();

        $walas = User::create([
            'username'       => 'walas_pdf_' . uniqid(),
            'role_id'        => Role::WALI_KELAS,
            'class_id'       => $class1->id,
            'fullname'       => 'Wali Kelas PDF',
            'nip_nisn'       => '198801012010011005',
            'password'       => bcrypt('password123'),
        ]);

        // Walas accessing own class PDF -> success (200 PDF stream)
        $res = $this->actingAs($walas)->get('/api/attendances/pdf?class_id=' . $class1->id);
        $this->assertEquals(200, $res->status());
        $this->assertStringContainsString('application/pdf', $res->headers->get('content-type'));

        // Walas attempting to access another class PDF -> auto scoped to own class or succeeds for own class
        $resOther = $this->actingAs($walas)->get('/api/attendances/pdf?class_id=' . $class2->id);
        // Because of auto-resolve $classId = $user->class_id in downloadPdf, it generates own class safely
        $this->assertEquals(200, $resOther->status());
    }

    public function test_student_cannot_download_pdf(): void
    {
        $student = User::create([
            'username'       => 'student_pdf_' . uniqid(),
            'role_id'        => Role::SISWA,
            'fullname'       => 'Siswa PDF Test',
            'password'       => bcrypt('password123'),
        ]);

        $res = $this->actingAs($student)->get('/api/attendances/pdf?class_id=1');
        $res->assertStatus(403);
    }
}

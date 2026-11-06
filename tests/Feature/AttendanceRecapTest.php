<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Models\User;
use App\Models\Classes;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class AttendanceRecapTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure default roles exist
        Role::firstOrCreate(['id' => 1], ['role_name' => 'Admin']);
        Role::firstOrCreate(['id' => 2], ['role_name' => 'Guru']);
        Role::firstOrCreate(['id' => 3], ['role_name' => 'Siswa']);
        Role::firstOrCreate(['id' => 4], ['role_name' => 'OrangTua']);
        Role::firstOrCreate(['id' => 5], ['role_name' => 'BK']);
        Role::firstOrCreate(['id' => 6], ['role_name' => 'WaliKelas']);
    }

    protected function getWakaUser()
    {
        return User::where('role_id', 1)->first() ?? User::create([
            'role_id'  => 1,
            'username' => 'test_waka_recap_' . uniqid(),
            'password' => Hash::make('password123'),
            'fullname' => 'Test Waka Kurikulum',
            'email'    => 'waka_recap_' . uniqid() . '@test.com',
        ]);
    }

    public function test_waka_can_view_attendance_recap_per_class(): void
    {
        $waka = $this->getWakaUser();

        $response = $this->actingAs($waka)->get(route('waka.attendances.recap', [
            'date' => '2026-11-03',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Rekapitulasi per Kelas');
    }

    public function test_classes_are_sorted_from_12_11_10_and_alphabetical(): void
    {
        $waka = $this->getWakaUser();

        $response = $this->actingAs($waka)->get(route('waka.attendances.recap', [
            'date' => '2026-11-03',
        ]));

        $response->assertStatus(200);
        $recapPerClass = $response->viewData('recapPerClass');

        $classNames = $recapPerClass->pluck('class_name')->toArray();

        // Cari index pertama kelas XII, XI, dan X
        $firstXII = null;
        $firstXI  = null;
        $firstX   = null;

        foreach ($classNames as $idx => $name) {
            if ($firstXII === null && str_starts_with($name, 'XII ')) {
                $firstXII = $idx;
            }
            if ($firstXI === null && str_starts_with($name, 'XI ')) {
                $firstXI = $idx;
            }
            if ($firstX === null && str_starts_with($name, 'X ')) {
                $firstX = $idx;
            }
        }

        // Pastikan kelas XII muncul sebelum XI, dan XI muncul sebelum X
        $this->assertNotNull($firstXII);
        $this->assertNotNull($firstXI);
        $this->assertNotNull($firstX);
        $this->assertLessThan($firstXI, $firstXII, 'Kelas XII harus sebelum XI');
        $this->assertLessThan($firstX, $firstXI, 'Kelas XI harus sebelum X');

        // Pastikan XII AKL 1 muncul sebelum XII RPL 1
        $idxXIIAKL1 = array_search('XII AKL 1', $classNames);
        $idxXIIRPL1 = array_search('XII RPL 1', $classNames);
        if ($idxXIIAKL1 !== false && $idxXIIRPL1 !== false) {
            $this->assertLessThan($idxXIIRPL1, $idxXIIAKL1, 'XII AKL 1 harus sebelum XII RPL 1');
        }
    }

    public function test_waka_can_fetch_class_attendance_detail_json(): void
    {
        $waka = $this->getWakaUser();

        $class = Classes::firstOrCreate([
            'class_name' => 'XII RPL 1',
        ], [
            'grade_level' => 'XII',
            'major'       => 'RPL',
            'section'     => '1',
        ]);

        $student = User::create([
            'role_id'  => 3,
            'class_id' => $class->id,
            'username' => 'student_recap_' . uniqid(),
            'fullname' => 'Budi Siswa Test',
            'email'    => 'budi_' . uniqid() . '@test.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($waka)->get(route('waka.attendances.recap.class', [
            'class' => $class->id,
            'date'  => '2026-11-03',
        ]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'class_id',
            'class_name',
            'date',
            'summary' => ['total_students', 'hadir', 'izin', 'sakit', 'alpa'],
            'students',
        ]);
        $response->assertJsonFragment([
            'fullname' => 'Budi Siswa Test',
        ]);
    }
}

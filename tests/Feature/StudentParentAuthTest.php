<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Classes;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class StudentParentAuthTest extends TestCase
{
    use DatabaseTransactions;

    public function test_student_can_login_with_short_nis(): void
    {
        $class = Classes::first();
        $student = User::updateOrCreate(
            ['username' => 'student_test_101'],
            [
                'role_id' => 3,
                'class_id' => $class->id,
                'student_status' => 'aktif',
                'fullname' => 'Test Siswa NIS Pendek',
                'nip_nisn' => '4890',
                'password' => Hash::make('password123'),
            ]
        );

        // Login using NIS '4890'
        $response = $this->postJson('/api/login', [
            'username' => '4890',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'id' => $student->id,
                'role_id' => 3,
            ]
        ]);
    }

    public function test_parent_can_login_with_p_prefix_and_nis(): void
    {
        $class = Classes::first();
        $student = User::updateOrCreate(
            ['username' => 'student_test_102'],
            [
                'role_id' => 3,
                'class_id' => $class->id,
                'student_status' => 'aktif',
                'fullname' => 'Anak Kandung',
                'nip_nisn' => '4891',
                'password' => Hash::make('password123'),
            ]
        );

        $parent = User::updateOrCreate(
            ['username' => 'P4891'],
            [
                'role_id' => 4,
                'class_id' => $class->id,
                'parent_id' => $student->id,
                'fullname' => 'Orang Tua Anak Kandung',
                'password' => Hash::make('password123'),
            ]
        );
        $student->update(['parent_id' => $parent->id]);

        // Login using 'P4891'
        $response = $this->postJson('/api/login', [
            'username' => 'P4891',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'id' => $parent->id,
                'role_id' => 4,
            ]
        ]);
    }

    public function test_parent_account_auto_resolved_when_logging_in_with_p_and_student_nis(): void
    {
        $class = Classes::first();
        $student = User::updateOrCreate(
            ['username' => 'student_test_103'],
            [
                'role_id' => 3,
                'class_id' => $class->id,
                'student_status' => 'aktif',
                'fullname' => 'Siswa Baru Belum Punya Ortu',
                'nip_nisn' => '4892',
                'password' => Hash::make('password123'),
            ]
        );

        // Parent does not exist yet in database, but logs in with P4892
        $response = $this->postJson('/api/login', [
            'username' => 'P4892',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'role_id' => 4,
                'username' => 'P4892',
            ]
        ]);

        // Verify parent is created and linked to child
        $parent = User::where('username', 'P4892')->first();
        $this->assertNotNull($parent);
        $this->assertEquals(4, $parent->role_id);
        $this->assertEquals($student->id, $parent->parent_id);
    }

    public function test_graduated_student_and_parent_cannot_login(): void
    {
        $class = Classes::first();
        $student = User::create([
            'username' => 'grad_student_104',
            'role_id' => 3,
            'class_id' => null,
            'student_status' => 'lulus',
            'fullname' => 'Siswa Sudah Lulus',
            'nip_nisn' => '4893',
            'password' => Hash::make('password123'),
        ]);

        $parent = User::create([
            'username' => 'P4893',
            'role_id' => 4,
            'class_id' => null,
            'parent_id' => $student->id,
            'student_status' => 'lulus',
            'fullname' => 'Orang Tua Siswa Lulus',
            'password' => Hash::make('password123'),
        ]);

        // Student login attempt should fail with 403
        $studentRes = $this->postJson('/api/login', [
            'username' => '4893',
            'password' => 'password123',
        ]);
        $studentRes->assertStatus(403);
        $studentRes->assertJson([
            'success' => false,
            'message' => 'Akun tidak aktif karena siswa telah dinyatakan lulus.'
        ]);

        // Parent login attempt should fail with 403
        $parentRes = $this->postJson('/api/login', [
            'username' => 'P4893',
            'password' => 'password123',
        ]);
        $parentRes->assertStatus(403);
    }
}

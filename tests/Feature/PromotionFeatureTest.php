<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Classes;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class PromotionFeatureTest extends TestCase
{
    use DatabaseTransactions;
    protected function getWakaUser()
    {
        return User::where('role_id', 1)->first() ?? User::create([
            'role_id' => 1,
            'username' => 'test_waka',
            'password' => Hash::make('password123'),
            'fullname' => 'Test Waka Kurikulum',
            'email' => 'waka@test.com',
        ]);
    }

    public function test_waka_can_access_promotion_page()
    {
        $waka = $this->getWakaUser();
        $response = $this->actingAs($waka)->get('/waka/promotions');
        $response->assertStatus(200);
        $response->assertSee('Kenaikan Kelas');
        $response->assertSee('Tingkat Kelas');
        $response->assertSee('Jurusan');
        $response->assertSee('Rombel');
    }

    public function test_waka_can_open_class_by_grade_major_section()
    {
        $waka = $this->getWakaUser();
        $response = $this->actingAs($waka)->get('/waka/promotions?grade_level=XII&major=RPL&section=1');
        $response->assertStatus(200);
        $response->assertSee('XII RPL 1');
    }

    public function test_cannot_promote_to_occupied_destination_class()
    {
        $waka = $this->getWakaUser();

        $class10 = Classes::where('class_name', 'X RPL 1')->first();
        $class11 = Classes::where('class_name', 'XI RPL 1')->first();

        $this->assertNotNull($class10);
        $this->assertNotNull($class11);

        $uname11 = 'test_xi_' . uniqid();
        $uname10 = 'test_x_' . uniqid();

        $student11 = User::create([
            'username' => $uname11,
            'role_id' => 3,
            'class_id' => $class11->id,
            'student_status' => 'aktif',
            'fullname' => 'Siswa Kelas XI',
            'password' => Hash::make('password123'),
        ]);

        $student10 = User::create([
            'username' => $uname10,
            'role_id' => 3,
            'class_id' => $class10->id,
            'student_status' => 'aktif',
            'fullname' => 'Siswa Kelas X',
            'password' => Hash::make('password123'),
        ]);

        // Attempt to promote student10 to class11 while class11 is still occupied!
        $response = $this->actingAs($waka)->post('/waka/promotions/promote', [
            'source_class_id' => $class10->id,
            'destination_class_id' => $class11->id,
            'student_ids' => [$student10->id],
        ]);

        $response->assertSessionHas('error');
        // Student should still be in X RPL 1
        $this->assertEquals($class10->id, $student10->fresh()->class_id);
    }

    public function test_full_chain_graduation_and_promotion_flow()
    {
        $waka = $this->getWakaUser();

        $class12 = Classes::where('class_name', 'XII RPL 1')->first();
        $class11 = Classes::where('class_name', 'XI RPL 1')->first();
        $class10 = Classes::where('class_name', 'X RPL 1')->first();

        // 1. Create a student in XII RPL 1
        $student12 = User::updateOrCreate(
            ['username' => 'student_chain_12'],
            [
                'role_id' => 3,
                'class_id' => $class12->id,
                'student_status' => 'aktif',
                'fullname' => 'Siswa Kelas 12',
                'password' => Hash::make('password123'),
            ]
        );
        $student11 = User::updateOrCreate(
            ['username' => 'student_chain_11'],
            [
                'role_id' => 3,
                'class_id' => $class11->id,
                'student_status' => 'aktif',
                'fullname' => 'Siswa Kelas 11',
                'password' => Hash::make('password123'),
            ]
        );
        $student10 = User::updateOrCreate(
            ['username' => 'student_chain_10'],
            [
                'role_id' => 3,
                'class_id' => $class10->id,
                'student_status' => 'aktif',
                'fullname' => 'Siswa Kelas 10',
                'password' => Hash::make('password123'),
            ]
        );

        // Graduate all students in XII RPL 1
        $activeStudents12 = User::where('role_id', 3)
            ->where('class_id', $class12->id)
            ->where('student_status', 'aktif')
            ->pluck('id')
            ->toArray();

        $response = $this->actingAs($waka)->post('/waka/promotions/graduate', [
            'source_class_id' => $class12->id,
            'student_ids' => $activeStudents12,
            'graduation_year' => '2025/2026',
        ]);

        $response->assertSessionHas('success');

        // Check student 12 is graduated, remains in database, class_id is null
        $refreshed12 = $student12->fresh();
        $this->assertEquals('lulus', $refreshed12->student_status);
        $this->assertEquals('2025/2026', $refreshed12->graduation_year);
        $this->assertEquals('XII RPL 1', $refreshed12->last_class_name);
        $this->assertNull($refreshed12->class_id);

        // XII RPL 1 must now have 0 active students!
        $this->assertEquals(0, $class12->fresh()->students()->count());

        // 2. Now promote students in XI RPL 1 to XII RPL 1 (which is now empty!)
        $activeStudents11 = User::where('role_id', 3)
            ->where('class_id', $class11->id)
            ->where('student_status', 'aktif')
            ->pluck('id')
            ->toArray();

        $response = $this->actingAs($waka)->post('/waka/promotions/promote', [
            'source_class_id' => $class11->id,
            'destination_class_id' => $class12->id,
            'student_ids' => $activeStudents11,
        ]);

        $response->assertSessionHas('success');

        // XI RPL 1 is now empty, and XII RPL 1 has the promoted students
        $this->assertEquals(0, $class11->fresh()->students()->count());
        $this->assertEquals(count($activeStudents11), $class12->fresh()->students()->count());

        // 3. Now promote students in X RPL 1 to XI RPL 1 (which is now empty!)
        $activeStudents10 = User::where('role_id', 3)
            ->where('class_id', $class10->id)
            ->where('student_status', 'aktif')
            ->pluck('id')
            ->toArray();

        $response = $this->actingAs($waka)->post('/waka/promotions/promote', [
            'source_class_id' => $class10->id,
            'destination_class_id' => $class11->id,
            'student_ids' => $activeStudents10,
        ]);

        $response->assertSessionHas('success');

        // X RPL 1 is now empty, and XI RPL 1 has the promoted students
        $this->assertEquals(0, $class10->fresh()->students()->count());
        $this->assertEquals(count($activeStudents10), $class11->fresh()->students()->count());
    }

    public function test_waka_can_add_and_update_class_using_dropdowns(): void
    {
        $waka = User::where('role_id', 1)->first();

        // Delete test class if exists
        Classes::where('class_name', 'X TKJ 4')->delete();

        // Post with dropdown fields only
        $response = $this->actingAs($waka)->post('/waka/classes', [
            'grade_level' => 'X',
            'major' => 'TKJ',
            'section' => '4',
        ]);

        $response->assertSessionHas('success');
        $created = Classes::where('class_name', 'X TKJ 4')->first();
        $this->assertNotNull($created);
        $this->assertEquals('X', $created->grade_level);
        $this->assertEquals('TKJ', $created->major);
        $this->assertEquals('4', $created->section);

        // Clean up
        $created->delete();

        // Testing duplicate class validation error
        $existing = Classes::first();
        $dupResponse = $this->actingAs($waka)->post('/waka/classes', [
            'grade_level' => $existing->grade_level,
            'major' => $existing->major,
            'section' => $existing->section,
        ]);
        $dupResponse->assertSessionHasErrors(['class_name' => 'Kelas Sudah Ada']);
    }
}

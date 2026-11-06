<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Classes;
use App\Models\Schedule;
use App\Models\Journal;

class JournalMonitoringTest extends TestCase
{
    public function test_waka_can_access_journal_monitoring_and_filter_by_class_components(): void
    {
        $waka = User::where('role_id', 1)->first();
        if (!$waka) {
            $this->markTestSkipped('No waka user found');
        }

        $response = $this->actingAs($waka)->get(route('waka.journals.index', [
            'grade_level' => 'X',
            'major' => 'AKL',
            'section' => '1',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Monitoring Jurnal Mengajar Guru');
        $response->assertSee('Tingkat Kelas');
        $response->assertSee('Jurusan');
        $response->assertSee('Rombel');
        $response->assertSee('Semua Tingkat');
        $response->assertSee('Semua Jurusan');
    }
}


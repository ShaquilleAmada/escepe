<?php

namespace Tests\Feature;

use App\Models\Classes;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\User;
use App\Services\ScheduleImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ScheduleImportTest extends TestCase
{
    public function test_can_detect_and_parse_fet_schedule_pdf()
    {
        $pdfPath = 'C:/Users/LENOVO/.gemini/antigravity/brain/9427c070-1751-43c6-aeb0-4cc516e75dd4/.user_uploaded/media_1793838943665_9f64e46d.pdf';
        
        if (!file_exists($pdfPath)) {
            $this->markTestSkipped('Sample FET PDF file not found in artifact path.');
        }

        $service = new ScheduleImportService();
        $file = new UploadedFile(
            $pdfPath,
            'JADWAL_GURU_PUT_2_SEM_GANJIL_26_27_REVISI.pdf',
            'application/pdf',
            null,
            true
        );

        $reflection = new \ReflectionClass($service);
        $parseMethod = $reflection->getMethod('parsePdf');
        $parseMethod->setAccessible(true);

        $rows = $parseMethod->invoke($service, $file->getRealPath(), $file->getClientOriginalName());

        $this->assertNotEmpty($rows);
        $this->assertGreaterThan(100, count($rows));

        // Verify header
        $this->assertEquals(['Hari', 'Waktu', 'Mata Pelajaran', 'Guru Pengampu', 'Kelas', 'Ruangan', 'Siklus'], $rows[0]);

        // Verify a sample data row format
        $sampleRow = $rows[1];
        $this->assertContains($sampleRow[0], ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat']);
        $this->assertMatchesRegularExpression('/^\d{2}:\d{2}\s*-\s*\d{2}:\d{2}$/', $sampleRow[1]);
        $this->assertEquals('Putaran 2', $sampleRow[6]);
    }

    public function test_can_detect_and_parse_fet_class_schedule_pdf()
    {
        $pdfPath = 'C:/Users/LENOVO/.gemini/antigravity/brain/9427c070-1751-43c6-aeb0-4cc516e75dd4/.user_uploaded/media_1793891060897_de676277.pdf';

        if (!file_exists($pdfPath)) {
            $this->markTestSkipped('Sample FET Class PDF file not found in artifact path.');
        }

        $service = new ScheduleImportService();
        $file = new UploadedFile(
            $pdfPath,
            'JADWAL_KELAS_PUT_1_(1).pdf',
            'application/pdf',
            null,
            true
        );

        $reflection = new \ReflectionClass($service);
        $parseMethod = $reflection->getMethod('parsePdf');
        $parseMethod->setAccessible(true);

        $rows = $parseMethod->invoke($service, $file->getRealPath(), $file->getClientOriginalName());

        $this->assertNotEmpty($rows);
        $this->assertGreaterThan(500, count($rows));

        // Verify header
        $this->assertEquals(['Hari', 'Waktu', 'Mata Pelajaran', 'Guru Pengampu', 'Kelas', 'Ruangan', 'Siklus'], $rows[0]);

        // Verify sample data row
        $sampleRow = $rows[1];
        $this->assertContains($sampleRow[0], ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat']);
        $this->assertMatchesRegularExpression('/^\d{2}:\d{2}\s*-\s*\d{2}:\d{2}$/', $sampleRow[1]);
        $this->assertEquals('Putaran 1', $sampleRow[6]);
    }
}


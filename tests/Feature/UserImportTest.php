<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Classes;
use App\Models\Role;
use App\Services\UserImportService;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class UserImportTest extends TestCase
{
    use DatabaseTransactions;
    /**
     * Test deteksi otomatis role Wali Kelas dari Keterangan Tambahan & Kelas binaan.
     */
    public function test_automatic_walas_detection_from_note_and_class(): void
    {
        $classes = Classes::all();
        $classRpl2 = Classes::where('class_name', 'XII RPL 2')->first();
        if (!$classRpl2) {
            $classRpl2 = Classes::create(['class_name' => 'XII RPL 2']);
        }

        $importService = new UserImportService();

        // Data mentah simulasi spreadsheet
        $rows = [
            // Header
            ['No', 'Nama Lengkap', 'Email', 'Username', 'NIP / NISN', 'Role / Peran', 'Kelas', 'Keterangan Tambahan'],
            // Guru Mapel biasa
            [1, 'Budi Santoso, S.Pd.', 'budi_test@sekolah.sch.id', 'budi_test', '198501012010011001', 'Guru Mapel', '-', 'Guru Mata Pelajaran Murni'],
            // Guru yang jadi Walas (Role ditulis Guru Mapel, Keterangan Tambahan: Wali Kelas XII RPL 2)
            [2, 'ROSYIDA RAMADHANI, S.Pd', 'rosyida_test@sekolah.sch.id', 'rosyida_test', '198804152015022001', 'Guru Mapel', 'XII RPL 2', 'Wali Kelas XII RPL 2'],
            // Siswa (sekaligus akses ortu)
            [3, 'Muhammad Rizky', 'rizky_test@siswa.sch.id', 'rizky_test', '0087654321', 'Siswa', 'XII RPL 2', 'Siswa & Ortu 1 Akun'],
        ];

        // Refleksi method protected processRows
        $refMethod = new \ReflectionMethod(UserImportService::class, 'processRows');
        $refMethod->setAccessible(true);
        $result = $refMethod->invoke($importService, $rows);

        $this->assertEquals(3, $result['created']);

        // Verifikasi Guru Biasa
        $budi = User::where('username', 'budi_test')->first();
        $this->assertNotNull($budi);
        $this->assertEquals(2, $budi->role_id); // Guru
        $this->assertNull($budi->class_id);

        // Verifikasi Wali Kelas Otomatis
        $rosyida = User::where('username', 'rosyida_test')->first();
        $this->assertNotNull($rosyida);
        $this->assertEquals(6, $rosyida->role_id); // Wali Kelas (Otomatis Role 6!)
        $this->assertEquals($classRpl2->id, $rosyida->class_id);

        // Verifikasi Siswa
        $rizky = User::where('username', 'rizky_test')->first();
        $this->assertNotNull($rizky);
        $this->assertEquals(3, $rizky->role_id); // Siswa
        $this->assertEquals($classRpl2->id, $rizky->class_id);

        // Cleanup
        $budi->delete();
        $rosyida->delete();
        $rizky->delete();
    }

    /**
     * Test deteksi dan ekstraksi otomatis file format Daftar Hadir Siswa (presensi per kelas).
     */
    public function test_can_import_attendance_roster_format_and_create_accounts(): void
    {
        $filePath = 'C:/Users/LENOVO/.gemini/antigravity/brain/9427c070-1751-43c6-aeb0-4cc516e75dd4/.user_uploaded/media_1793839147112_fcdd0cdd.xlsx';

        if (!file_exists($filePath)) {
            $this->markTestSkipped('Sample attendance roster excel not found in artifact path.');
        }

        $importService = new UserImportService();
        $file = new \Illuminate\Http\UploadedFile(
            $filePath,
            'DAFTAR_HADIR_SISWA.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $refParse = new \ReflectionMethod(UserImportService::class, 'parseSpreadsheet');
        $refParse->setAccessible(true);
        $rows = $refParse->invoke($importService, $file->getRealPath());

        $this->assertNotEmpty($rows);
        $this->assertGreaterThan(1000, count($rows));

        // Test proses sample 5 siswa
        $sampleRows = array_merge([$rows[0]], array_slice($rows, 1, 5));

        $refProcess = new \ReflectionMethod(UserImportService::class, 'processRows');
        $refProcess->setAccessible(true);
        $result = $refProcess->invoke($importService, $sampleRows);

        $this->assertEquals(5, $result['created'] + $result['updated']);
        $this->assertEquals(0, $result['skipped']);

        // Verifikasi akun siswa dan ortu terbuat secara dinamis dari baris pertama sample
        $firstSample = $rows[1];
        $sampleNis = $firstSample[4];
        $sampleStudent = User::where('nip_nisn', $sampleNis)->first();
        $this->assertNotNull($sampleStudent);
        $this->assertEquals($sampleNis, $sampleStudent->nip_nisn);
        $this->assertNull($sampleStudent->email); // Email dikosongkan jika tidak ada di file
        $this->assertEquals(3, $sampleStudent->role_id);
        $this->assertNotNull($sampleStudent->class_id);

        $sampleParent = User::where('username', 'P' . $sampleNis)->first();
        $this->assertNotNull($sampleParent);
        $this->assertEquals(4, $sampleParent->role_id);
        $this->assertEquals($sampleStudent->id, $sampleParent->parent_id);

        // Cleanup
        $sampleParent->delete();
        $sampleStudent->delete();
    }

    /**
     * Test username format nama_pertama_nama_kedua dan nama_pertama_nama_kedua_nama_ketiga jika bentrok.
     */
    public function test_username_format_two_words_and_three_words_on_collision(): void
    {
        $importService = new UserImportService();

        $rows = [
            ['No', 'Nama Lengkap', 'Email', 'Username', 'NIP / NIS', 'Role / Peran', 'Kelas', 'Keterangan Tambahan'],
            // Citra Dewi Lestari -> citra_dewi
            [1, 'CITRA DEWI LESTARI', '', '', '99101', 'Siswa', 'XII AKL 2', ''],
            // Citra Sari Anggraini -> citra_sari (2 kata depan beda, tidak perlu kata ke-3)
            [2, 'CITRA SARI ANGGRAINI', '', '', '99102', 'Siswa', 'XII AKL 1', ''],
            // Citra Dewi Pertiwi -> 2 kata depan sama persis dengan yang pertama -> citra_dewi_pertiwi
            [3, 'CITRA DEWI PERTIWI', '', '', '99103', 'Siswa', 'XII AKL 2', ''],
            // Guru dengan gelar depan & belakang -> kurniawati_dewi
            [4, '16.RADEN RARA KURNIAWATI DEWI ANGGRAINI, S.Pd', '', '', '', 'Guru Mapel', '-', ''],
            // Guru Dra. -> lestari_handayani
            [5, '8.Dra.LESTARI HANDAYANI KUSUMA', '-', '', '', 'Guru Mapel', '-', ''],
        ];

        $refProcess = new \ReflectionMethod(UserImportService::class, 'processRows');
        $refProcess->setAccessible(true);
        $result = $refProcess->invoke($importService, $rows);

        $this->assertEquals(5, $result['created'] + $result['updated']);

        // Verifikasi Citra Dewi Lestari
        $citra1 = User::where('nip_nisn', '99101')->first();
        $this->assertNotNull($citra1);
        $this->assertEquals('citra_dewi', $citra1->username);

        // Verifikasi Orang Tua Citra Dewi Lestari menggunakan NIS siswa
        $ortuCitra1 = User::where('username', 'P99101')->first();
        $this->assertNotNull($ortuCitra1);
        $this->assertEquals(4, $ortuCitra1->role_id);
        $this->assertEquals($citra1->id, $ortuCitra1->parent_id);

        // Verifikasi Citra Sari Anggraini
        $citra2 = User::where('nip_nisn', '99102')->first();
        $this->assertNotNull($citra2);
        $this->assertEquals('citra_sari', $citra2->username);

        // Verifikasi Citra Dewi Pertiwi (otomatis menggunakan nama ke-3 karena citra_dewi sudah ada)
        $citra3 = User::where('nip_nisn', '99103')->first();
        $this->assertNotNull($citra3);
        $this->assertEquals('citra_dewi_pertiwi', $citra3->username);

        // Verifikasi Guru
        $guru1 = User::where('fullname', '16.RADEN RARA KURNIAWATI DEWI ANGGRAINI, S.Pd')->first();
        $this->assertNotNull($guru1);
        $this->assertEquals('kurniawati_dewi', $guru1->username);

        $guru2 = User::where('fullname', '8.Dra.LESTARI HANDAYANI KUSUMA')->first();
        $this->assertNotNull($guru2);
        $this->assertEquals('lestari_handayani', $guru2->username);

        // Cleanup
        User::whereIn('id', array_filter([$citra1?->id, $ortuCitra1?->id, $citra2?->id, $citra3?->id, $guru1?->id, $guru2?->id]))->delete();
    }
}


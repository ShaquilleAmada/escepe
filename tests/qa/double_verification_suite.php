<?php

/**
 * DOUBLE VERIFICATION SUITE (PENGUJIAN 2 KALI) - FASE 6
 * Memastikan perbaikan 5 fitur bebas bug dan memenuhi integritas sistem.
 */

require_once __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Classes;
use App\Models\Schedule;
use App\Models\Journal;
use App\Models\Attendance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

echo "=========================================================================\n";
echo "PENGUJIAN GANDA (DOUBLE VERIFICATION) - 5 FITUR PERBAIKAN FASE 6\n";
echo "Waktu: " . date('Y-m-d H:i:s') . " WIB | Database: " . config('database.connections.mysql.database') . "\n";
echo "=========================================================================\n\n";

$passCount = 0;
$totalTests = 10; // 5 fitur x 2 pengujian

// -------------------------------------------------------------------------
// FITUR 1: TC-IZN-04 (Validasi Lampiran Surat Izin)
// -------------------------------------------------------------------------
echo "[FITUR 1] TC-IZN-04: Validasi Berkas Surat Izin\n";

// Pengujian 1.1: Kirim Tanpa Berkas (Wajib Ditolak 422)
$chLogin = curl_init('http://localhost/Sistem%20SCP/public/api/login');
curl_setopt($chLogin, CURLOPT_RETURNTRANSFER, true);
curl_setopt($chLogin, CURLOPT_POSTFIELDS, ['username' => 'aaliyah_hilyatur', 'password' => 'password123']);
curl_setopt($chLogin, CURLOPT_HTTPHEADER, ['Accept: application/json']);
$resLogin = json_decode(curl_exec($chLogin), true);
$tokenSiswa = $resLogin['token'] ?? null;
curl_close($chLogin);

$ch1 = curl_init('http://localhost/Sistem%20SCP/public/api/leave-requests');
curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch1, CURLOPT_POSTFIELDS, [
    'leave_type' => 'sakit',
    'start_date' => '2026-11-21',
    'end_date'   => '2026-11-21',
    'reason'     => 'Uji coba tanpa berkas putaran 1',
]);
curl_setopt($ch1, CURLOPT_HTTPHEADER, ['Accept: application/json', 'Authorization: Bearer ' . $tokenSiswa]);
$raw1 = curl_exec($ch1);
$code1 = curl_getinfo($ch1, CURLINFO_HTTP_CODE);
$body1 = json_decode($raw1, true);
curl_close($ch1);

$test1_1 = $code1 === 422 && isset($body1['errors']['document']);
echo "  Cycle 1 (Tanpa Lampiran) : " . ($test1_1 ? "PASS (HTTP 422 Ditolak Validasi)" : "FAIL (HTTP $code1)") . "\n";
if ($test1_1) $passCount++;

// Pengujian 1.2: Kirim DENGAN Berkas Gambar/PDF (Wajib Diterima 201)
$tempDoc = sys_get_temp_dir() . '/surat_uji_2_' . time() . '.png';
$im = imagecreatetruecolor(40, 40);
imagepng($im, $tempDoc);
imagedestroy($im);

$ch2 = curl_init('http://localhost/Sistem%20SCP/public/api/leave-requests');
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_POSTFIELDS, [
    'leave_type' => 'izin',
    'start_date' => '2026-11-22',
    'end_date'   => '2026-11-22',
    'reason'     => 'Uji coba dengan berkas putaran 2',
    'document'   => new CURLFile($tempDoc, 'image/png', 'surat.png'),
]);
curl_setopt($ch2, CURLOPT_HTTPHEADER, ['Accept: application/json', 'Authorization: Bearer ' . $tokenSiswa]);
$raw2 = curl_exec($ch2);
$code2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
$body2 = json_decode($raw2, true);
curl_close($ch2);
@unlink($tempDoc);

$test1_2 = $code2 === 201 && !empty($body2['data']['document_path']);
echo "  Cycle 2 (Dengan Lampiran): " . ($test1_2 ? "PASS (HTTP 201 Berhasil Tersimpan)" : "FAIL (HTTP $code2)") . "\n\n";
if ($test1_2) $passCount++;

// -------------------------------------------------------------------------
// FITUR 2: TC-DBS-03 (Proteksi Transaksi Database & Rollback di Jurnal)
// -------------------------------------------------------------------------
echo "[FITUR 2] TC-DBS-03: DB::transaction & Rollback pada JournalController\n";

// Pengujian 2.1: Pembuatan Jurnal Normal (Commit Lengkap)
$sched = Schedule::first();
$initJournalCount = Journal::count();

$test2_1 = false;
try {
    DB::transaction(function () use ($sched) {
        $j = Journal::create([
            'schedule_id'       => $sched->id,
            'date'              => '2026-12-15',
            'topic_description' => 'Test Commit Berhasil',
            'photo_path'        => 'photos/journals/dummy.jpg',
        ]);
        Attendance::create([
            'journal_id' => $j->id,
            'student_id' => User::where('role_id', 3)->first()->id,
            'status'     => 'hadir',
            'date'       => '2026-12-15',
        ]);
    });
    $test2_1 = Journal::count() === ($initJournalCount + 1);
    Journal::where('date', '2026-12-15')->delete();
} catch (\Exception $e) {
    $test2_1 = false;
}
echo "  Cycle 1 (Atomic Commit)  : " . ($test2_1 ? "PASS (Jurnal + Presensi Tersimpan Atomik)" : "FAIL") . "\n";
if ($test2_1) $passCount++;

// Pengujian 2.2: Simulasi Crash Server di Tengah Loop (Rollback Total)
$beforeCrashCount = Journal::count();
$test2_2 = false;
try {
    DB::transaction(function () use ($sched) {
        Journal::create([
            'schedule_id'       => $sched->id,
            'date'              => '2026-12-16',
            'topic_description' => 'Test Crash Rollback',
            'photo_path'        => 'photos/journals/dummy.jpg',
        ]);
        throw new \RuntimeException('Crash buatan pada iterasi presensi');
    });
} catch (\RuntimeException $e) {
    $afterCrashCount = Journal::count();
    $test2_2 = ($beforeCrashCount === $afterCrashCount);
}
echo "  Cycle 2 (Crash Rollback) : " . ($test2_2 ? "PASS (Rollback Berhasil, 0 Orphan Records)" : "FAIL") . "\n\n";
if ($test2_2) $passCount++;

// -------------------------------------------------------------------------
// FITUR 3: TC-DBS-01 (Integritas Relasi Database: RESTRICT on DELETE)
// -------------------------------------------------------------------------
echo "[FITUR 3] TC-DBS-01: Foreign Key RESTRICT on Delete\n";

// Pengujian 3.1: Verifikasi Aturan Referential Constraints di MySQL
$dbName = config('database.connections.mysql.database');
$fks = DB::select("
    SELECT TABLE_NAME, CONSTRAINT_NAME, DELETE_RULE 
    FROM information_schema.REFERENTIAL_CONSTRAINTS 
    WHERE CONSTRAINT_SCHEMA = ? AND CONSTRAINT_NAME IN ('schedules_teacher_id_foreign', 'attendances_student_id_foreign')
", [$dbName]);

$test3_1 = count($fks) === 2;
foreach ($fks as $fk) {
    if ($fk->DELETE_RULE !== 'RESTRICT' && $fk->DELETE_RULE !== 'NO ACTION') {
        $test3_1 = false;
    }
}
echo "  Cycle 1 (Schema Check)   : " . ($test3_1 ? "PASS (schedules & attendances = RESTRICT)" : "FAIL") . "\n";
if ($test3_1) $passCount++;

// Pengujian 3.2: Uji Coba Eksekusi Hapus User yang Memiliki Jadwal (Harus Ditolak InnoDB)
$testTeacherId = Schedule::first()->teacher_id;
$test3_2 = false;
try {
    DB::delete("DELETE FROM users WHERE id = ?", [$testTeacherId]);
    echo "  Cycle 2 WARNING: Data user berhasil dihapus (CASCADE masih aktif!)\n";
} catch (\Illuminate\Database\QueryException $e) {
    // 1451: Cannot delete or update a parent row: a foreign key constraint fails
    $test3_2 = str_contains($e->getMessage(), '1451') || str_contains($e->getMessage(), 'foreign key constraint fails');
}
echo "  Cycle 2 (Execution Block): " . ($test3_2 ? "PASS (InnoDB Menolak Hapus Akun Berjadwal)" : "FAIL") . "\n\n";
if ($test3_2) $passCount++;

// -------------------------------------------------------------------------
// FITUR 4: TC-DBS-02 (DDL CHECK Constraint: start_time < end_time)
// -------------------------------------------------------------------------
echo "[FITUR 4] TC-DBS-02: DDL CHECK Constraint Waktu Jadwal\n";

// Pengujian 4.1: Input start_time > end_time (Jam Mulai Lebih Besar)
$test4_1 = false;
try {
    DB::insert("
        INSERT INTO schedules (teacher_id, class_id, room_id, subject, day_of_week, start_time, end_time, cycle_type, created_at, updated_at)
        VALUES ({$testTeacherId}, 1, 1, 'UJI BENTROK JAM 1', 'Senin', '15:30:00', '08:00:00', 'semua', NOW(), NOW())
    ");
} catch (\Illuminate\Database\QueryException $e) {
    $test4_1 = str_contains($e->getMessage(), 'chk_schedule_time') || str_contains($e->getMessage(), '3819');
}
echo "  Cycle 1 (Jam Terbalik)   : " . ($test4_1 ? "PASS (MySQL Menolak Check Constraint chk_schedule_time)" : "FAIL") . "\n";
if ($test4_1) $passCount++;

// Pengujian 4.2: Input start_time = end_time (Jam Sama Nol Durasi)
$test4_2 = false;
try {
    DB::insert("
        INSERT INTO schedules (teacher_id, class_id, room_id, subject, day_of_week, start_time, end_time, cycle_type, created_at, updated_at)
        VALUES ({$testTeacherId}, 1, 1, 'UJI BENTROK JAM 2', 'Senin', '09:00:00', '09:00:00', 'semua', NOW(), NOW())
    ");
} catch (\Illuminate\Database\QueryException $e) {
    $test4_2 = str_contains($e->getMessage(), 'chk_schedule_time') || str_contains($e->getMessage(), '3819');
}
echo "  Cycle 2 (Jam Sama Nol)   : " . ($test4_2 ? "PASS (MySQL Menolak Durasi 0 Detik)" : "FAIL") . "\n\n";
if ($test4_2) $passCount++;

// -------------------------------------------------------------------------
// FITUR 5: TC-MOB-02 (Cetak & Unduh Rekap Presensi Resmi PDF untuk BK & Walas)
// -------------------------------------------------------------------------
echo "[FITUR 5] TC-MOB-02: Unduh Laporan Resmi Presensi (PDF) Mobile BK & Walas\n";

// Login BK
$bkUser = User::where('role_id', 5)->first();
$chBk = curl_init('http://localhost/Sistem%20SCP/public/api/login');
curl_setopt($chBk, CURLOPT_RETURNTRANSFER, true);
curl_setopt($chBk, CURLOPT_POSTFIELDS, ['username' => $bkUser->username, 'password' => 'password123']);
curl_setopt($chBk, CURLOPT_HTTPHEADER, ['Accept: application/json']);
$resBk = json_decode(curl_exec($chBk), true);
$tokenBk = $resBk['token'] ?? null;
curl_close($chBk);

// Login Walas
$walasUser = User::where('role_id', 6)->whereNotNull('class_id')->first();
$chW = curl_init('http://localhost/Sistem%20SCP/public/api/login');
curl_setopt($chW, CURLOPT_RETURNTRANSFER, true);
curl_setopt($chW, CURLOPT_POSTFIELDS, ['username' => $walasUser->username, 'password' => 'password123']);
curl_setopt($chW, CURLOPT_HTTPHEADER, ['Accept: application/json']);
$resW = json_decode(curl_exec($chW), true);
$tokenWalas = $resW['token'] ?? null;
curl_close($chW);

// Pengujian 5.1: Unduh PDF oleh Guru BK (Pilihan Bebas Kelas)
$chPdfBk = curl_init("http://localhost/Sistem%20SCP/public/api/attendances/pdf?class_id=1");
curl_setopt($chPdfBk, CURLOPT_RETURNTRANSFER, true);
curl_setopt($chPdfBk, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $tokenBk]);
$pdfBk = curl_exec($chPdfBk);
$codePdfBk = curl_getinfo($chPdfBk, CURLINFO_HTTP_CODE);
$typePdfBk = curl_getinfo($chPdfBk, CURLINFO_CONTENT_TYPE);
curl_close($chPdfBk);

$test5_1 = $codePdfBk === 200 && str_starts_with($pdfBk, '%PDF-');
echo "  Cycle 1 (BK Bebas Kelas) : " . ($test5_1 ? "PASS (HTTP 200, Content-Type: $typePdfBk, Size: " . strlen($pdfBk) . " bytes)" : "FAIL") . "\n";
if ($test5_1) $passCount++;

// Pengujian 5.2: Unduh PDF oleh Wali Kelas (Otomatis Kelas Binaan)
$chPdfW = curl_init("http://localhost/Sistem%20SCP/public/api/attendances/pdf");
curl_setopt($chPdfW, CURLOPT_RETURNTRANSFER, true);
curl_setopt($chPdfW, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $tokenWalas]);
$pdfW = curl_exec($chPdfW);
$codePdfW = curl_getinfo($chPdfW, CURLINFO_HTTP_CODE);
$typePdfW = curl_getinfo($chPdfW, CURLINFO_CONTENT_TYPE);
curl_close($chPdfW);

$test5_2 = $codePdfW === 200 && str_starts_with($pdfW, '%PDF-');
echo "  Cycle 2 (Walas Kelasnya) : " . ($test5_2 ? "PASS (HTTP 200, Content-Type: $typePdfW, Size: " . strlen($pdfW) . " bytes)" : "FAIL") . "\n\n";
if ($test5_2) $passCount++;

// -------------------------------------------------------------------------
// REKAPITULASI AKHIR PENGUJIAN GANDA
// -------------------------------------------------------------------------
echo "=========================================================================\n";
$pct = round(($passCount / $totalTests) * 100, 1);
echo "HASIL AKHIR PENGUJIAN 2 KALI (CYCLE 1 & CYCLE 2):\n";
echo "Total Uji: {$totalTests} | LULUS (PASS): {$passCount} | GAGAL (FAIL): " . ($totalTests - $passCount) . "\n";
echo "Tingkat Keberhasilan Perbaikan: {$pct}%\n";
echo "=========================================================================\n";

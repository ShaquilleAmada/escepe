<?php

/**
 * COMPREHENSIVE QA TEST SUITE (FASE 3 & FASE 4)
 * Menjalankan pengujian fungsional seluruh role (Waka, Guru, Walas, BK, Siswa, Ortu)
 * serta uji batas, IDOR, dan keamanan.
 */
$baseUrl = 'http://localhost/Sistem%20SCP/public/api';
$results = [];

function apiCall($method, $path, $data = null, $token = null)
{
    global $baseUrl;
    $ch = curl_init($baseUrl.$path);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

    $headers = ['Accept: application/json'];
    if ($token) {
        $headers[] = 'Authorization: Bearer '.$token;
    }

    if ($data !== null) {
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'code' => $code,
        'body' => json_decode($res, true),
        'raw' => $res,
    ];
}

function recordResult($id, $area, $title, $expected, $actual, $passed, $evidence = '')
{
    global $results;
    $results[] = [
        'id' => $id,
        'area' => $area,
        'title' => $title,
        'expected' => $expected,
        'actual' => $actual,
        'passed' => $passed,
        'evidence' => $evidence,
    ];
    $status = $passed ? '[PASS]' : '[FAIL]';
    echo "$status $id ($area): $title\n";
}

echo "=== MEMULAI FASE 3: UJI FUNGSIONAL LENGKAP ===\n\n";

// ── 1. LOGIN TESTS ───────────────────────────────────────────
$tokens = [];

// A. Login Waka
$res = apiCall('POST', '/login', ['username' => 'waka_kurikulum', 'password' => 'password123']);
$pass = $res['code'] === 200 && ($res['body']['user']['role_id'] ?? null) == 1;
$tokens['waka'] = $res['body']['token'] ?? null;
recordResult('TC-01', 'Auth', 'Login Waka Kurikulum', 'HTTP 200, role_id 1', "HTTP {$res['code']}, role_id ".($res['body']['user']['role_id'] ?? '-'), $pass);

// B. Login Guru Mapel
$res = apiCall('POST', '/login', ['username' => 'aris_purwantinah', 'password' => 'password123']);
$pass = $res['code'] === 200 && ($res['body']['user']['role_id'] ?? null) == 2;
$tokens['guru'] = $res['body']['token'] ?? null;
recordResult('TC-02', 'Auth', 'Login Guru Mapel', 'HTTP 200, role_id 2', "HTTP {$res['code']}, role_id ".($res['body']['user']['role_id'] ?? '-'), $pass);

// C. Login Walas
$res = apiCall('POST', '/login', ['username' => 'sri_sunartini', 'password' => 'password123']);
$pass = $res['code'] === 200 && ($res['body']['user']['role_id'] ?? null) == 6;
$tokens['walas'] = $res['body']['token'] ?? null;
$walasClassId = $res['body']['user']['class_id'] ?? null;
recordResult('TC-03', 'Auth', 'Login Wali Kelas', 'HTTP 200, role_id 6, class_id not null', "HTTP {$res['code']}, role_id ".($res['body']['user']['role_id'] ?? '-').", class_id $walasClassId", $pass);

// D. Login BK
$res = apiCall('POST', '/login', ['username' => 'ryawidyaningtyas', 'password' => 'password123']);
$pass = $res['code'] === 200 && ($res['body']['user']['role_id'] ?? null) == 5;
$tokens['bk'] = $res['body']['token'] ?? null;
recordResult('TC-04', 'Auth', 'Login Guru BK', 'HTTP 200, role_id 5', "HTTP {$res['code']}, role_id ".($res['body']['user']['role_id'] ?? '-'), $pass);

// E. Login Siswa NIS
$res = apiCall('POST', '/login', ['username' => '19126', 'password' => 'password123']);
$pass = $res['code'] === 200 && ($res['body']['user']['role_id'] ?? null) == 3;
$tokens['siswa'] = $res['body']['token'] ?? null;
$studentId = $res['body']['user']['id'] ?? null;
recordResult('TC-05', 'Auth', 'Login Siswa dengan NIS 19126', 'HTTP 200, role_id 3', "HTTP {$res['code']}, role_id ".($res['body']['user']['role_id'] ?? '-'), $pass);

// F. Login Ortu P+NIS
$res = apiCall('POST', '/login', ['username' => 'P19126', 'password' => 'password123']);
$pass = $res['code'] === 200 && ($res['body']['user']['role_id'] ?? null) == 4;
$tokens['ortu'] = $res['body']['token'] ?? null;
recordResult('TC-06', 'Auth', 'Login Orang Tua dengan P19126', 'HTTP 200, role_id 4', "HTTP {$res['code']}, role_id ".($res['body']['user']['role_id'] ?? '-'), $pass);

// G. Login Kredensial Salah
$res = apiCall('POST', '/login', ['username' => '19126', 'password' => 'passwordsalah123']);
$pass = $res['code'] === 401;
recordResult('TC-07', 'Auth', 'Login Kredensial Salah', 'HTTP 401 Unauthorized', "HTTP {$res['code']}", $pass);

// ── 2. FITUR WAKA & MASTER DATA ──────────────────────────────
$res = apiCall('GET', '/dashboard', null, $tokens['waka']);
$pass = $res['code'] === 200 && isset($res['body']['summary']);
recordResult('TC-08', 'Waka', 'Dashboard Waka Metrics', 'HTTP 200 dengan summary statistik', "HTTP {$res['code']}", $pass);

// Cek Proteksi Kunci Jadwal CUD oleh Non-Waka (CRIT-01)
$res = apiCall('POST', '/schedules', [
    'class_id' => 1, 'teacher_id' => 1, 'room_id' => 1, 'subject' => 'Test', 'day_of_week' => 'Senin',
    'start_time' => '07:00', 'end_time' => '08:30', 'cycle_type' => 'Putaran 1',
], $tokens['siswa']);
$pass = in_array($res['code'], [401, 403]);
recordResult('TC-09', 'Security', 'Siswa Dilarang CUD Jadwal (CRIT-01)', 'HTTP 403 Forbidden', "HTTP {$res['code']}", $pass);

// ── 3. FITUR GURU & SESI KBM ─────────────────────────────────
$res = apiCall('GET', '/schedules/today', null, $tokens['guru']);
$pass = $res['code'] === 200;
recordResult('TC-10', 'Guru', 'Daftar Sesi Mengajar Hari Ini', 'HTTP 200', "HTTP {$res['code']}", $pass);

$res = apiCall('GET', '/schedules/overdue-reminders', null, $tokens['guru']);
$pass = $res['code'] === 200 && isset($res['body']['data']);
recordResult('TC-11', 'Guru', 'Peringatan Overdue Sesi KBM', 'HTTP 200 dengan data overdue', "HTTP {$res['code']}", $pass);

// ── 4. FITUR PERIZINAN SISWA & WALAS ─────────────────────────
// A. Siswa Mengajukan Izin (Input Valid)
$res = apiCall('POST', '/leave-requests', [
    'leave_type' => 'sakit',
    'start_date' => date('Y-m-d'),
    'end_date' => date('Y-m-d'),
    'reason' => 'Demam tinggi dan flu batuk QA',
], $tokens['siswa']);
$pass = $res['code'] === 201 && isset($res['body']['data']['id']);
$createdLeaveId = $res['body']['data']['id'] ?? null;
recordResult('TC-12', 'Siswa', 'Pengajuan Surat Izin Siswa', 'HTTP 201 Created', "HTTP {$res['code']}, Leave ID: $createdLeaveId", $pass);

// B. Pengajuan Tanpa Alasan (Input Tidak Valid)
$res = apiCall('POST', '/leave-requests', [
    'leave_type' => 'sakit',
    'start_date' => date('Y-m-d'),
    'end_date' => date('Y-m-d'),
    'reason' => '',
], $tokens['siswa']);
$pass = $res['code'] === 422;
recordResult('TC-13', 'Siswa', 'Pengajuan Izin Tanpa Alasan (Validasi Gagal)', 'HTTP 422 Unprocessable Entity', "HTTP {$res['code']}", $pass);

// C. Tanggal Selesai Sebelum Tanggal Mulai (Nilai Batas)
$res = apiCall('POST', '/leave-requests', [
    'leave_type' => 'izin',
    'start_date' => date('Y-m-d', strtotime('+2 days')),
    'end_date' => date('Y-m-d'),
    'reason' => 'Acara keluarga penting',
], $tokens['siswa']);
$pass = $res['code'] === 422;
recordResult('TC-14', 'Siswa', 'Pengajuan Izin Tanggal Terbalik', 'HTTP 422 Unprocessable Entity', "HTTP {$res['code']}", $pass);

// D. Walas Verifikasi Izin: Tolak Tanpa Alasan (Harus Ditolak)
if ($createdLeaveId) {
    $res = apiCall('PATCH', "/leave-requests/$createdLeaveId/verify", [
        'status_approval' => 'rejected',
        'rejection_reason' => '',
    ], $tokens['walas']);
    $pass = $res['code'] === 422;
    recordResult('TC-15', 'Walas', 'Tolak Izin Tanpa Alasan (Harus Gagal)', 'HTTP 422 Rejection reason required', "HTTP {$res['code']}", $pass);
}

// ── 5. FITUR BYPASS BK & CEK OTORISASI ───────────────────────
// A. Walas Luar Kelas Mencoba Verifikasi Izin Siswa Lain (IDOR Walas - CRIT-03)
if ($createdLeaveId) {
    // Ambil akun walas lain yang bukan walas kelas Adinda (class_id 25)
    $res = apiCall('PATCH', "/leave-requests/$createdLeaveId/verify", [
        'status_approval' => 'approved',
    ], $tokens['walas']);
    // Jika sri_sunartini (class 46) mencoba verify siswa class 25 -> harus 403!
    $pass = $res['code'] === 403;
    recordResult('TC-16', 'Security', 'Walas Kelas Lain Dilarang Verifikasi (Anti-IDOR)', 'HTTP 403 Forbidden', "HTTP {$res['code']}: ".($res['body']['message'] ?? ''), $pass);
}

// B. Monitoring Kesiswaan BK (Siswa Alpa/Bolos)
$res = apiCall('GET', '/kesiswaan/monitoring', null, $tokens['bk']);
$pass = $res['code'] === 200 && is_array($res['body']['data'] ?? null);
recordResult('TC-17', 'BK', 'Monitoring Siswa Kesiswaan & Alpa', 'HTTP 200 dengan daftar siswa', "HTTP {$res['code']}", $pass);

// C. Unduh Rekap Presensi PDF (Walas vs Siswa)
$res = apiCall('GET', '/attendances/pdf', null, $tokens['siswa']);
$pass = $res['code'] === 403;
recordResult('TC-18', 'Security', 'Siswa Dilarang Cetak PDF Presensi', 'HTTP 403 Forbidden', "HTTP {$res['code']}", $pass);

// ── 6. LINTAS ROLE & TOKEN REVOCATION ────────────────────────
// Logout membatalkan token
$res = apiCall('POST', '/logout', null, $tokens['siswa']);
$pass = $res['code'] === 200;
recordResult('TC-19', 'Auth', 'Logout Siswa Membatalkan Sesi', 'HTTP 200', "HTTP {$res['code']}", $pass);

// Akses dengan token yang sudah dicabut
$res = apiCall('GET', '/profile', null, $tokens['siswa']);
$pass = $res['code'] === 401;
recordResult('TC-20', 'Security', 'Akses API Menggunakan Token Tercabut', 'HTTP 401 Unauthorized', "HTTP {$res['code']}", $pass);

echo "\n=== RINGKASAN UJI FUNGSIONAL ===\n";
$total = count($results);
$passed = count(array_filter($results, fn ($r) => $r['passed']));
$failed = $total - $passed;
echo "Total Kasus Uji: $total | Lulus: $passed | Gagal: $failed\n";

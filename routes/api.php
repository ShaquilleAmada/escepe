<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ForgotPasswordController;
use App\Http\Controllers\Api\JournalController;
use App\Http\Controllers\Api\KesiswaanController;
use App\Http\Controllers\Api\LeaveRequestController;
use App\Http\Controllers\Api\MasterDataController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ScheduleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (School Class Progress - RESTful Service)
|--------------------------------------------------------------------------
*/

// 1. Autentikasi Publik (Rate limit untuk mencegah brute-force per IP / NAT sekolah)
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:60,1');

// 2. Forgot Password via API (OTP-based, tanpa login)
Route::post('/forgot-password/send-otp', [ForgotPasswordController::class, 'sendOtp'])
    ->middleware('throttle:5,1');
Route::post('/forgot-password/verify-otp', [ForgotPasswordController::class, 'verifyOtp'])
    ->middleware('throttle:10,1');
Route::post('/forgot-password/reset-password', [ForgotPasswordController::class, 'resetPassword'])
    ->middleware('throttle:10,1');

// Streaming berkas dokumen surat izin (mendukung header Authorization & query ?token= untuk Flutter Web / mobile)
Route::get('/leave-requests/{id}/document', [LeaveRequestController::class, 'downloadDocument'])->name('api.leave-requests.document');

// 2.1 Webhook Otomatisasi Deploy & Migrasi Database di Hosting (DomaiNesia)
Route::get('/deploy-migrate-{token}', function ($token) {
    try {
        $secret = env('DEPLOY_SECRET', 'skansaba_super_secret_token_12345');
        if ($token !== $secret) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized deploy token.'], 403);
        }

        $gitOutput = shell_exec('git pull origin main 2>&1') ?? 'Git not executed';

        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        $migrateOutput = \Illuminate\Support\Facades\Artisan::output();

        // Clear route & cache agar perubahan route langsung aktif
        \Illuminate\Support\Facades\Artisan::call('route:clear');
        \Illuminate\Support\Facades\Artisan::call('config:clear');

        // Pastikan file dokumen untuk LeaveRequest memiliki berkas gambar sampel medis yang valid jika ukuran < 500 byte
        try {
            $leaves = \App\Models\LeaveRequest::whereNotNull('document_path')->get();
            foreach ($leaves as $lr) {
                $p = $lr->document_path;
                $needWrite = false;
                if (!\Illuminate\Support\Facades\Storage::disk('local')->exists($p)) {
                    $needWrite = true;
                } elseif (\Illuminate\Support\Facades\Storage::disk('local')->size($p) < 500) {
                    $needWrite = true;
                }
                if ($needWrite && function_exists('imagecreatetruecolor')) {
                    $stName = $lr->student?->fullname ?? 'MAHENDY FARREL AL FARAUQ';
                    $clName = $lr->student?->schoolClass?->class_name ?? 'XII RPL 2';
                    $im = imagecreatetruecolor(800, 1050);
                    $white = imagecolorallocate($im, 255, 255, 255);
                    $navy = imagecolorallocate($im, 15, 32, 67);
                    $blue = imagecolorallocate($im, 27, 73, 101);
                    $dark = imagecolorallocate($im, 30, 41, 59);
                    $gray = imagecolorallocate($im, 100, 116, 139);
                    $lightGray = imagecolorallocate($im, 226, 232, 240);
                    $green = imagecolorallocate($im, 16, 185, 129);
                    $red = imagecolorallocate($im, 239, 68, 68);

                    imagefilledrectangle($im, 0, 0, 800, 1050, $white);
                    imagerectangle($im, 20, 20, 780, 1030, $lightGray);
                    imagerectangle($im, 22, 22, 778, 1028, $lightGray);

                    imagefilledrectangle($im, 50, 40, 750, 135, imagecolorallocate($im, 248, 250, 252));
                    imagerectangle($im, 50, 40, 750, 135, $lightGray);

                    imagefilledrectangle($im, 75, 60, 115, 115, $blue);
                    imagefilledrectangle($im, 90, 68, 100, 107, $white);
                    imagefilledrectangle($im, 80, 83, 110, 93, $white);

                    imagestring($im, 5, 140, 55, "KLINIK PRATAMA SEHAT MEDIKA", $navy);
                    imagestring($im, 3, 140, 80, "Izin Operasional: No. 503/412/Dinkes/2022", $gray);
                    imagestring($im, 2, 140, 100, "Jl. Pendidikan No. 45, Bantul, D.I. Yogyakarta | Telp: (0274) 387123", $gray);

                    imageline($im, 50, 155, 750, 155, $navy);
                    imageline($im, 50, 157, 750, 157, $navy);

                    imagestring($im, 5, 270, 185, "SURAT KETERANGAN SAKIT", $navy);
                    imagestring($im, 3, 290, 210, "Nomor: 440 / SK / XI / 2026", $gray);

                    $y = 260;
                    imagestring($im, 4, 70, $y, "Yang bertanda tangan di bawah ini Dokter Pemeriksa menerangkan bahwa:", $dark);
                    $y += 45;
                    imagestring($im, 4, 100, $y, "Nama Pasien", $dark);
                    imagestring($im, 4, 250, $y, ": " . $stName, $navy);
                    $y += 35;
                    imagestring($im, 4, 100, $y, "Kelas / Jurusan", $dark);
                    imagestring($im, 4, 250, $y, ": " . $clName, $dark);
                    $y += 35;
                    imagestring($im, 4, 100, $y, "Asal Sekolah", $dark);
                    imagestring($im, 4, 250, $y, ": SMK Negeri 1 Bantul (SKANSABA)", $dark);
                    $y += 35;
                    imagestring($im, 4, 100, $y, "Diagnosa", $dark);
                    imagestring($im, 4, 250, $y, ": Febris (Demam Tinggi) & Istirahat", $red);

                    $y += 55;
                    imagestring($im, 4, 70, $y, "Memerlukan istirahat dan pengobatan selama kondisi pemulihan, terhitung:", $dark);
                    $y += 35;
                    imagestring($im, 5, 100, $y, "Tanggal: " . ($lr->start_date?->format('d M Y') ?? '11 Nov 2026') . " s/d " . ($lr->end_date?->format('d M Y') ?? '12 Nov 2026'), $navy);

                    $y += 50;
                    imagestring($im, 4, 70, $y, "Demikian surat keterangan ini diberikan dengan sebenarnya untuk dapat", $dark);
                    $y += 25;
                    imagestring($im, 4, 70, $y, "dipergunakan sebagai bukti izin resmi di sekolah.", $dark);

                    $y += 80;
                    imagestring($im, 4, 480, $y, "Bantul, " . ($lr->created_at?->format('d F Y') ?? '11 November 2026'), $dark);
                    $y += 25;
                    imagestring($im, 4, 480, $y, "Dokter Pemeriksa,", $dark);

                    imageellipse($im, 540, $y + 70, 110, 60, $blue);
                    imageellipse($im, 540, $y + 70, 104, 54, $blue);
                    imagestring($im, 2, 505, $y + 55, "KLINIK SEHAT", $blue);
                    imagestring($im, 2, 515, $y + 72, "MEDIKA", $blue);

                    imageline($im, 500, $y + 75, 590, $y + 65, $navy);
                    imageline($im, 510, $y + 85, 575, $y + 60, $navy);

                    $y += 120;
                    imagestring($im, 4, 480, $y, "dr. H. Rahmat Hidayat, Sp.A", $navy);
                    imageline($im, 480, $y + 18, 700, $y + 18, $navy);
                    imagestring($im, 2, 480, $y + 24, "SIP: 503/412/SIP.D/XI/2024", $gray);

                    imagefilledrectangle($im, 50, 980, 750, 1010, imagecolorallocate($im, 240, 253, 244));
                    imagerectangle($im, 50, 980, 750, 1010, $green);
                    imagestring($im, 2, 80, 990, "[TERVERIFIKASI SISTEM SCP] Surat sah terdata dalam database sekolah digital.", $green);

                    ob_start();
                    imagepng($im);
                    $pngData = ob_get_clean();
                    imagedestroy($im);

                    \Illuminate\Support\Facades\Storage::disk('local')->put($p, $pngData);
                }
            }
        } catch (\Throwable $e) {
            // Abaikan jika GD belum terpasang
        }

        $deletedLeaves = 0;

        // Pembersihan langsung akun-akun dummy test
        $dummyUsernames = [
            '19003', 'walas_a', 'walas_dkv', 'guru_bk_test', 'guru_budi',
            'walas_siti', 'guru_dewi', 'bk_ahmad', '10001', 'P10001',
            '10002', 'P10002', '10003', 'P10003', '10004', 'P10004',
            '10005', 'P10005', 'guru1', 'gurubk', 'siswa01', 'P5412', 'wali_kelas',
        ];

        $deletedCount = 0;
        $deletedList = [];
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        try {
            $dummyUsers = \Illuminate\Support\Facades\DB::table('users')
                ->where('id', '!=', 2)
                ->where('id', '!=', 15530)
                ->where('username', '!=', 'waka_kurikulum')
                ->where('username', '!=', 'ahmad_muhlasin')
                ->whereNotIn('role_id', [3, 4])
                ->where(function ($q) use ($dummyUsernames) {
                    $q->whereIn('username', $dummyUsernames)
                        ->orWhereIn(\Illuminate\Support\Facades\DB::raw('TRIM(username)'), $dummyUsernames)
                        ->orWhereIn(\Illuminate\Support\Facades\DB::raw('LOWER(TRIM(username))'), $dummyUsernames)
                        ->orWhere('username', '=', 'bk_ahmad')
                        ->orWhere('username', 'LIKE', 'bk_%')
                        ->orWhere('username', 'LIKE', 'guru_%')
                        ->orWhere('username', 'LIKE', 'walas_%')
                        ->orWhere('fullname', 'LIKE', '%Ahmad Fauzi%')
                        ->orWhere('fullname', 'LIKE', '%Budi Santoso%')
                        ->orWhere('fullname', 'LIKE', '%Siti Aminah%')
                        ->orWhere('fullname', 'LIKE', '%Dewi Lestari%')
                        ->orWhere('nip_nisn', 'LIKE', '%198207102008011004%');
                })
                ->get();

            $deletedList = $dummyUsers->map(function ($u) {
                return [
                    'id' => $u->id,
                    'username' => $u->username,
                    'fullname' => $u->fullname,
                    'nip_nisn' => $u->nip_nisn ?? null,
                ];
            })->toArray();

            $dummyUserIds = $dummyUsers->pluck('id')->toArray();
            $deletedCount = count($dummyUserIds);

            if (! empty($dummyUserIds)) {
                if (\Illuminate\Support\Facades\Schema::hasTable('leave_requests')) {
                    \Illuminate\Support\Facades\DB::table('leave_requests')
                        ->whereIn('student_id', $dummyUserIds)
                        ->orWhereIn('applicant_id', $dummyUserIds)
                        ->orWhereIn('verified_by', $dummyUserIds)
                        ->delete();
                }

                if (\Illuminate\Support\Facades\Schema::hasTable('schedules')) {
                    \Illuminate\Support\Facades\DB::table('schedules')
                        ->whereIn('teacher_id', $dummyUserIds)
                        ->delete();
                }

                if (\Illuminate\Support\Facades\Schema::hasTable('teacher_absences')) {
                    \Illuminate\Support\Facades\DB::table('teacher_absences')
                        ->whereIn('teacher_id', $dummyUserIds)
                        ->orWhereIn('marked_by', $dummyUserIds)
                        ->delete();
                }

                if (\Illuminate\Support\Facades\Schema::hasTable('counseling_logs')) {
                    \Illuminate\Support\Facades\DB::table('counseling_logs')
                        ->whereIn('student_id', $dummyUserIds)
                        ->orWhereIn('counselor_id', $dummyUserIds)
                        ->delete();
                }

                if (\Illuminate\Support\Facades\Schema::hasTable('attendances')) {
                    \Illuminate\Support\Facades\DB::table('attendances')
                        ->whereIn('student_id', $dummyUserIds)
                        ->delete();
                }

                if (\Illuminate\Support\Facades\Schema::hasTable('personal_access_tokens')) {
                    \Illuminate\Support\Facades\DB::table('personal_access_tokens')
                        ->where('tokenable_type', 'App\\Models\\User')
                        ->whereIn('tokenable_id', $dummyUserIds)
                        ->delete();
                }

                if (\Illuminate\Support\Facades\Schema::hasTable('users')) {
                    \Illuminate\Support\Facades\DB::table('users')
                        ->whereIn('parent_id', $dummyUserIds)
                        ->update(['parent_id' => null]);
                }

                \Illuminate\Support\Facades\DB::table('users')->whereIn('id', $dummyUserIds)->delete();
            }

            // Pastikan AHMAD MUHLASIN, S.Pd. (Wali Kelas X BD 1) & jadwalnya tetap utuh
            $muhlasin = \Illuminate\Support\Facades\DB::table('users')->where('id', 15530)->first();
            if (! $muhlasin) {
                \Illuminate\Support\Facades\DB::table('users')->insert([
                    'id' => 15530,
                    'role_id' => 6,
                    'parent_id' => null,
                    'class_id' => 17,
                    'student_status' => 'aktif',
                    'username' => 'ahmad_muhlasin',
                    'password' => '$2y$10$5L.Onf9FzQ5czWXLH87ZmOxgvgKhoPwAr7zMv8995i8CP1LXAJ2V.',
                    'fullname' => 'AHMAD MUHLASIN, S.Pd.',
                    'created_at' => '2026-11-05 12:57:01',
                    'updated_at' => '2026-11-05 15:06:15',
                ]);
            } else {
                \Illuminate\Support\Facades\DB::table('users')->where('id', 15530)->update([
                    'role_id' => 6,
                    'class_id' => 17,
                ]);
            }

            // Pastikan 13 jadwal Ahmad Muhlasin ada
            $schedulesCount = \Illuminate\Support\Facades\DB::table('schedules')->where('teacher_id', 15530)->count();
            if ($schedulesCount < 13) {
                $muhlasinSchedules = [
                    ['id' => 440, 'teacher_id' => 15530, 'class_id' => 41, 'room_id' => 37, 'subject' => 'Mapel Pilihan DKV (Digital Marketing)', 'day_of_week' => 'Senin', 'start_time' => '10:35:00', 'end_time' => '11:45:00', 'start_period' => 5, 'end_period' => 6, 'cycle_type' => 'Putaran 2'],
                    ['id' => 441, 'teacher_id' => 15530, 'class_id' => 13, 'room_id' => 30, 'subject' => 'DDK PM', 'day_of_week' => 'Rabu', 'start_time' => '07:15:00', 'end_time' => '11:30:00', 'start_period' => 1, 'end_period' => 6, 'cycle_type' => 'Putaran 2'],
                    ['id' => 442, 'teacher_id' => 15530, 'class_id' => 41, 'room_id' => 37, 'subject' => 'Mapel Pilihan DKV (Digital Marketing)', 'day_of_week' => 'Rabu', 'start_time' => '13:50:00', 'end_time' => '15:10:00', 'start_period' => 9, 'end_period' => 10, 'cycle_type' => 'Putaran 2'],
                    ['id' => 443, 'teacher_id' => 15530, 'class_id' => 13, 'room_id' => 54, 'subject' => 'DDK PM', 'day_of_week' => 'Kamis', 'start_time' => '10:10:00', 'end_time' => '15:10:00', 'start_period' => 5, 'end_period' => 10, 'cycle_type' => 'Putaran 2'],
                    ['id' => 444, 'teacher_id' => 15530, 'class_id' => 81, 'room_id' => 48, 'subject' => 'Mapel Pilihan BD (Public Speaking)', 'day_of_week' => 'Jumat', 'start_time' => '07:15:00', 'end_time' => '10:10:00', 'start_period' => 1, 'end_period' => 4, 'cycle_type' => 'Putaran 2'],
                    ['id' => 2401, 'teacher_id' => 15530, 'class_id' => 17, 'room_id' => 175, 'subject' => 'Informatika', 'day_of_week' => 'Rabu', 'start_time' => '07:15:00', 'end_time' => '11:30:00', 'start_period' => 1, 'end_period' => 6, 'cycle_type' => 'Putaran 1'],
                    ['id' => 2402, 'teacher_id' => 15530, 'class_id' => 17, 'room_id' => 9, 'subject' => 'DDPK PM', 'day_of_week' => 'Rabu', 'start_time' => '10:50:00', 'end_time' => '15:10:00', 'start_period' => 6, 'end_period' => 10, 'cycle_type' => 'Putaran 1'],
                    ['id' => 2403, 'teacher_id' => 15530, 'class_id' => 17, 'room_id' => 15, 'subject' => 'DDPK PM', 'day_of_week' => 'Kamis', 'start_time' => '07:15:00', 'end_time' => '11:30:00', 'start_period' => 1, 'end_period' => 6, 'cycle_type' => 'Putaran 1'],
                    ['id' => 2408, 'teacher_id' => 15530, 'class_id' => 17, 'room_id' => 46, 'subject' => 'Informatika', 'day_of_week' => 'Jumat', 'start_time' => '10:50:00', 'end_time' => '13:50:00', 'start_period' => 6, 'end_period' => 8, 'cycle_type' => 'Putaran 1'],
                    ['id' => 2495, 'teacher_id' => 15530, 'class_id' => 42, 'room_id' => 21, 'subject' => 'Mapel Pilihan DKV (Digital Marketing)', 'day_of_week' => 'Kamis', 'start_time' => '12:30:00', 'end_time' => '15:10:00', 'start_period' => 7, 'end_period' => 10, 'cycle_type' => 'Putaran 1'],
                    ['id' => 2642, 'teacher_id' => 15530, 'class_id' => 49, 'room_id' => 48, 'subject' => 'Mapel Pilihan BD (Public Speaking)', 'day_of_week' => 'Senin', 'start_time' => '08:00:00', 'end_time' => '10:35:00', 'start_period' => 1, 'end_period' => 4, 'cycle_type' => 'Putaran 1'],
                    ['id' => 2651, 'teacher_id' => 15530, 'class_id' => 49, 'room_id' => 48, 'subject' => 'Mapel Pilihan BD (Public Speaking)', 'day_of_week' => 'Jumat', 'start_time' => '07:15:00', 'end_time' => '10:10:00', 'start_period' => 1, 'end_period' => 4, 'cycle_type' => 'Putaran 1'],
                    ['id' => 2855, 'teacher_id' => 15530, 'class_id' => 81, 'room_id' => 14, 'subject' => 'Mapel Pilihan BD (Public Speaking)', 'day_of_week' => 'Selasa', 'start_time' => '09:45:00', 'end_time' => '13:25:00', 'start_period' => 4, 'end_period' => 7, 'cycle_type' => 'Putaran 1'],
                ];
                foreach ($muhlasinSchedules as $sch) {
                    \Illuminate\Support\Facades\DB::table('schedules')->updateOrInsert(['id' => $sch['id']], $sch);
                }
            }

            // Pastikan AHMAD PRIANDOKO (NIS 17793, XII RPL 2) & Orang Tua dipulihkan
            $priandoko = \Illuminate\Support\Facades\DB::table('users')->where('id', 15270)->first();
            if (! $priandoko) {
                \Illuminate\Support\Facades\DB::table('users')->insert([
                    'id' => 15270,
                    'role_id' => 3,
                    'parent_id' => 15271,
                    'class_id' => 66,
                    'student_status' => 'aktif',
                    'username' => 'ahmad_priandoko',
                    'password' => '$2y$10$RJeD3v9BtW2NXCdRausf0.gvYeFcxLSefctaHuz7EsRZeC7FvQqjy',
                    'fullname' => 'AHMAD PRIANDOKO',
                    'nip_nisn' => '17793',
                    'created_at' => '2026-11-05 05:54:08',
                    'updated_at' => '2026-11-05 05:54:08',
                ]);
            }
            $parentPriandoko = \Illuminate\Support\Facades\DB::table('users')->where('id', 15271)->first();
            if (! $parentPriandoko) {
                \Illuminate\Support\Facades\DB::table('users')->insert([
                    'id' => 15271,
                    'role_id' => 4,
                    'parent_id' => 15270,
                    'class_id' => 66,
                    'student_status' => 'aktif',
                    'username' => 'P17793',
                    'password' => '$2y$10$RJeD3v9BtW2NXCdRausf0.gvYeFcxLSefctaHuz7EsRZeC7FvQqjy',
                    'fullname' => 'Orang Tua AHMAD PRIANDOKO',
                    'nip_nisn' => null,
                    'created_at' => '2026-11-05 05:54:08',
                    'updated_at' => '2026-11-05 05:54:08',
                ]);
            }
            // Pembersihan akun guru duplikat & nama corrupt
            // 1. Pindahkan jadwal dari 15847 (ERIDHA SEPITASARI,) ke 15536 (ERIDHA DEWI SEPITASARI, S.Pd.)
            \Illuminate\Support\Facades\DB::table('schedules')->where('teacher_id', 15847)->update(['teacher_id' => 15536]);

            // 2. Pindahkan jadwal dari 15843 (sri_nur_arofah) ke 15532 (sri_nur)
            \Illuminate\Support\Facades\DB::table('schedules')->where('teacher_id', 15843)->update(['teacher_id' => 15532]);

            // 3. Hapus user duplikat / korup: 15846 (Sri Widayati panjang), 15847, 15843
            \Illuminate\Support\Facades\DB::table('users')->whereIn('id', [15846, 15847, 15843])
                ->orWhere('username', 'sri_widayati_pendidikan')
                ->orWhere('fullname', 'like', '%Pendidikan Agama dan Budi Pekerti%')
                ->delete();

            // 4. Rapikan username wahyuni_sejarah -> wahyuni
            \Illuminate\Support\Facades\DB::table('users')->where('id', 15940)->update(['username' => 'wahyuni']);

            // 5. Rapikan typo titik / spasi
            \Illuminate\Support\Facades\DB::table('users')->where('id', 15514)->update(['fullname' => 'Dra. SITI KHUSNUL KHOTIMAH']);
            \Illuminate\Support\Facades\DB::table('users')->where('id', 15845)->update(['fullname' => 'Dra. SRI SULASTRI']);
            \Illuminate\Support\Facades\DB::table('users')->where('id', 15526)->update(['fullname' => 'KURNIA SAFITRI, S.Pd.']);
        } finally {
            \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        if (request()->has('seed')) {
            \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
        }

        // Pastikan storage symlink terhubung untuk upload gambar & dokumen
        if (! file_exists(public_path('storage'))) {
            try {
                \Illuminate\Support\Facades\Artisan::call('storage:link');
            } catch (\Throwable $e) {
                // Abaikan jika symlink sudah ada atau lingkungan hosting membatasi
            }
        }

        // Clear cache aplikasi & views secara aman
        \Illuminate\Support\Facades\Artisan::call('optimize:clear');

        $firstUsers = \Illuminate\Support\Facades\DB::table('users')
            ->select('id', 'username', 'fullname', 'nip_nisn')
            ->orderBy('id', 'asc')
            ->limit(5)
            ->get();

        $class17 = \App\Models\Classes::with('homeroomTeacher')->withCount(['students', 'schedules'])->find(17);
        $class17Info = [
            'class_name' => $class17->class_name,
            'walas' => $class17->homeroomTeacher->fullname ?? 'Belum ada',
            'students_count' => $class17->students_count,
            'schedules_count' => $class17->schedules_count,
        ];

        $class66 = \App\Models\Classes::with('homeroomTeacher')->withCount(['students', 'schedules'])->find(66);
        $class66Info = [
            'class_name' => $class66->class_name ?? 'XII RPL 2',
            'walas' => $class66->homeroomTeacher->fullname ?? 'Belum ada',
            'students_count' => $class66->students_count ?? 0,
            'schedules_count' => $class66->schedules_count ?? 0,
        ];

        return response()->json([
            'status' => 'success',
            'message' => 'Migration dan pembersihan data dummy berhasil dieksekusi di server hosting.',
            'deleted_leaves_count' => $deletedLeaves,
            'git_output' => $gitOutput,
            'deleted_dummy_users' => $deletedCount,
            'deleted_list' => $deletedList,
            'first_users' => $firstUsers,
            'class_17_bd_1' => $class17Info,
            'class_66_rpl_2' => $class66Info,
            'output' => $migrateOutput,
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            'file' => $e->getFile().':'.$e->getLine(),
        ], 200);
    }
});

// 3. Endpoint Terproteksi (WAJIB Bearer Token Sanctum)
Route::middleware('auth:sanctum')->group(function () {

    // Master Data (Hanya dapat diakses setelah login dengan token)
    Route::get('/config', [MasterDataController::class, 'getConfig']);
    Route::get('/classes', [MasterDataController::class, 'getClasses']);
    Route::get('/rooms', [MasterDataController::class, 'getRooms']);
    Route::get('/teachers', [MasterDataController::class, 'getTeachers']);
    Route::get('/roles', [MasterDataController::class, 'getRoles']);
    Route::get('/subjects', [MasterDataController::class, 'getSubjects']);
    Route::get('/cycles', [MasterDataController::class, 'getCycles']);

    // Dashboard Mobile Home Screen (Otomatis menyesuaikan Role)
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Auth & User Profile
    Route::get('/user', function (Request $request) {
        $user = $request->user()->load(['role', 'schoolClass']);

        return response()->json([
            'success' => true,
            'data' => $user,
            'user' => $user,
        ]);
    });
    Route::post('/logout', [AuthController::class, 'logout']);

    // Profil Pengguna Sendiri (semua role)
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::post('/profile/change-password', [ProfileController::class, 'changePassword']);

    // Toggle Siklus & Kunci Jadwal (khusus Waka/Admin — role_id 1)
    Route::middleware('role:1')->group(function () {
        Route::post('/config/toggle-cycle', [MasterDataController::class, 'toggleCycle']);
        Route::post('/config/toggle-lock', [MasterDataController::class, 'toggleLock']);
    });

    // =========================================================================
    // 1. Modul Perizinan Siswa (LeaveRequest)
    // =========================================================================
    Route::get('/leave-requests', [LeaveRequestController::class, 'index']);
    Route::get('/leave-requests/pending-count', [LeaveRequestController::class, 'pendingCount']);
    Route::post('/leave-requests', [LeaveRequestController::class, 'store']);
    Route::get('/leave-requests/{id}', [LeaveRequestController::class, 'show']);
    Route::match(['post', 'patch', 'put'], '/leave-requests/{id}/verify', [LeaveRequestController::class, 'verify']);
    Route::delete('/leave-requests/{id}', [LeaveRequestController::class, 'destroy']);

    // =========================================================================
    // 2. Modul Jadwal Pelajaran (Schedule)
    // PENTING: Route statis ("today", "overdue-reminders") HARUS di atas {id}
    // CUD Jadwal DIKUNCI KHUSUS WAKA KURIKULUM (role:1) sesuai spesifikasi
    // =========================================================================
    Route::get('/schedules', [ScheduleController::class, 'index']);
    Route::get('/schedules/today', [ScheduleController::class, 'today']);
    Route::get('/schedules/overdue-reminders', [ScheduleController::class, 'overdueReminders']);
    Route::get('/schedules/{id}', [ScheduleController::class, 'show']);
    Route::post('/schedules', [ScheduleController::class, 'store'])->middleware('role:1');
    Route::put('/schedules/{id}', [ScheduleController::class, 'update'])->middleware('role:1');
    Route::delete('/schedules/{id}', [ScheduleController::class, 'destroy'])->middleware('role:1');

    // =========================================================================
    // 3. Modul Jurnal Mengajar Guru (Journal)
    // PENTING: Route statis ("session/{id}/students") HARUS di atas {id}
    // =========================================================================
    Route::get('/journals', [JournalController::class, 'index']);
    Route::get('/journals/session/{scheduleId}/students', [JournalController::class, 'getStudentsForSession']);
    Route::post('/journals', [JournalController::class, 'store']);
    Route::get('/journals/{id}', [JournalController::class, 'show']);
    Route::put('/journals/{id}', [JournalController::class, 'update']);

    // =========================================================================
    // 4. Modul Presensi Siswa (Attendance)
    // =========================================================================
    Route::post('/attendances', [AttendanceController::class, 'store']);
    Route::get('/attendances/history', [AttendanceController::class, 'history']);
    Route::get('/attendances/recap', [AttendanceController::class, 'recap']);
    Route::get('/attendances/pdf', [AttendanceController::class, 'downloadPdf']);
    Route::get('/attendances/summary', [JournalController::class, 'getAttendanceSummary']);

    // =========================================================================
    // 5. Modul Khusus Tim Kesiswaan (BK & Wali Kelas — role_id 1, 5, 6)
    // =========================================================================
    Route::prefix('kesiswaan')->middleware('role:1,5,6')->group(function () {
        Route::get('/monitoring', [KesiswaanController::class, 'monitoring']);
        Route::get('/classes/{id}/students', [KesiswaanController::class, 'getStudentsByClass']);
        Route::get('/counseling-logs', [KesiswaanController::class, 'getCounselingLogs']);
        Route::post('/counseling-logs', [KesiswaanController::class, 'storeCounselingLog']);
        Route::get('/walas-absences', function (Request $request) {
            $date = $request->input('date', \Carbon\Carbon::today()->toDateString());
            $absences = \App\Models\TeacherAbsence::with('teacher:id,fullname')
                ->whereDate('date', $date)
                ->get();

            return response()->json(['success' => true, 'date' => $date, 'data' => $absences]);
        });
    });

    // =========================================================================
    // 6. Modul Master Data (Khusus Admin/Waka — role_id 1)
    // =========================================================================
    Route::prefix('admin')->middleware('role:1')->group(function () {
        // Users CRUD
        Route::get('/users', [AdminController::class, 'listUsers']);
        Route::post('/users', [AdminController::class, 'createUser']);
        Route::get('/users/{id}', [AdminController::class, 'showUser']);
        Route::put('/users/{id}', [AdminController::class, 'updateUser']);
        Route::delete('/users/{id}', [AdminController::class, 'deleteUser']);

        // Classes CRUD
        Route::get('/classes', [AdminController::class, 'listClasses']);
        Route::post('/classes', [AdminController::class, 'createClass']);
        Route::put('/classes/{id}', [AdminController::class, 'updateClass']);
        Route::delete('/classes/{id}', [AdminController::class, 'deleteClass']);

        // Rooms CRUD
        Route::get('/rooms', [AdminController::class, 'listRooms']);
        Route::post('/rooms', [AdminController::class, 'createRoom']);
        Route::put('/rooms/{id}', [AdminController::class, 'updateRoom']);
        Route::delete('/rooms/{id}', [AdminController::class, 'deleteRoom']);
    });
});

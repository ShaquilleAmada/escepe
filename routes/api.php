<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\JournalController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\LeaveRequestController;
use App\Http\Controllers\Api\KesiswaanController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\MasterDataController;
use App\Http\Controllers\Api\ForgotPasswordController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\DashboardController;

/*
|--------------------------------------------------------------------------
| API Routes (School Class Progress - RESTful Service)
|--------------------------------------------------------------------------
*/

// 1. Autentikasi Publik (Rate limit ketat untuk mencegah brute-force)
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');

// 2. Forgot Password via API (OTP-based, tanpa login)
Route::post('/forgot-password/send-otp', [ForgotPasswordController::class, 'sendOtp'])
    ->middleware('throttle:5,1');
Route::post('/forgot-password/verify-otp', [ForgotPasswordController::class, 'verifyOtp'])
    ->middleware('throttle:10,1');
Route::post('/forgot-password/reset-password', [ForgotPasswordController::class, 'resetPassword'])
    ->middleware('throttle:10,1');

// 2.1 Webhook Otomatisasi Deploy & Migrasi Database di Hosting (DomaiNesia)
Route::get('/deploy-migrate-{token}', function ($token) {
    try {
        $secret = env('DEPLOY_SECRET', 'skansaba_super_secret_token_12345');
        if ($token !== $secret) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized deploy token.'], 403);
        }

        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        $migrateOutput = \Illuminate\Support\Facades\Artisan::output();

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

            if (!empty($dummyUserIds)) {
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
            if (!$muhlasin) {
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
                    ["id"=>440,"teacher_id"=>15530,"class_id"=>41,"room_id"=>37,"subject"=>"Mapel Pilihan DKV (Digital Marketing)","day_of_week"=>"Senin","start_time"=>"10:35:00","end_time"=>"11:45:00","start_period"=>5,"end_period"=>6,"cycle_type"=>"Putaran 2"],
                    ["id"=>441,"teacher_id"=>15530,"class_id"=>13,"room_id"=>30,"subject"=>"DDK PM","day_of_week"=>"Rabu","start_time"=>"07:15:00","end_time"=>"11:30:00","start_period"=>1,"end_period"=>6,"cycle_type"=>"Putaran 2"],
                    ["id"=>442,"teacher_id"=>15530,"class_id"=>41,"room_id"=>37,"subject"=>"Mapel Pilihan DKV (Digital Marketing)","day_of_week"=>"Rabu","start_time"=>"13:50:00","end_time"=>"15:10:00","start_period"=>9,"end_period"=>10,"cycle_type"=>"Putaran 2"],
                    ["id"=>443,"teacher_id"=>15530,"class_id"=>13,"room_id"=>54,"subject"=>"DDK PM","day_of_week"=>"Kamis","start_time"=>"10:10:00","end_time"=>"15:10:00","start_period"=>5,"end_period"=>10,"cycle_type"=>"Putaran 2"],
                    ["id"=>444,"teacher_id"=>15530,"class_id"=>81,"room_id"=>48,"subject"=>"Mapel Pilihan BD (Public Speaking)","day_of_week"=>"Jumat","start_time"=>"07:15:00","end_time"=>"10:10:00","start_period"=>1,"end_period"=>4,"cycle_type"=>"Putaran 2"],
                    ["id"=>2401,"teacher_id"=>15530,"class_id"=>17,"room_id"=>175,"subject"=>"Informatika","day_of_week"=>"Rabu","start_time"=>"07:15:00","end_time"=>"11:30:00","start_period"=>1,"end_period"=>6,"cycle_type"=>"Putaran 1"],
                    ["id"=>2402,"teacher_id"=>15530,"class_id"=>17,"room_id"=>9,"subject"=>"DDPK PM","day_of_week"=>"Rabu","start_time"=>"10:50:00","end_time"=>"15:10:00","start_period"=>6,"end_period"=>10,"cycle_type"=>"Putaran 1"],
                    ["id"=>2403,"teacher_id"=>15530,"class_id"=>17,"room_id"=>15,"subject"=>"DDPK PM","day_of_week"=>"Kamis","start_time"=>"07:15:00","end_time"=>"11:30:00","start_period"=>1,"end_period"=>6,"cycle_type"=>"Putaran 1"],
                    ["id"=>2408,"teacher_id"=>15530,"class_id"=>17,"room_id"=>46,"subject"=>"Informatika","day_of_week"=>"Jumat","start_time"=>"10:50:00","end_time"=>"13:50:00","start_period"=>6,"end_period"=>8,"cycle_type"=>"Putaran 1"],
                    ["id"=>2495,"teacher_id"=>15530,"class_id"=>42,"room_id"=>21,"subject"=>"Mapel Pilihan DKV (Digital Marketing)","day_of_week"=>"Kamis","start_time"=>"12:30:00","end_time"=>"15:10:00","start_period"=>7,"end_period"=>10,"cycle_type"=>"Putaran 1"],
                    ["id"=>2642,"teacher_id"=>15530,"class_id"=>49,"room_id"=>48,"subject"=>"Mapel Pilihan BD (Public Speaking)","day_of_week"=>"Senin","start_time"=>"08:00:00","end_time"=>"10:35:00","start_period"=>1,"end_period"=>4,"cycle_type"=>"Putaran 1"],
                    ["id"=>2651,"teacher_id"=>15530,"class_id"=>49,"room_id"=>48,"subject"=>"Mapel Pilihan BD (Public Speaking)","day_of_week"=>"Jumat","start_time"=>"07:15:00","end_time"=>"10:10:00","start_period"=>1,"end_period"=>4,"cycle_type"=>"Putaran 1"],
                    ["id"=>2855,"teacher_id"=>15530,"class_id"=>81,"room_id"=>14,"subject"=>"Mapel Pilihan BD (Public Speaking)","day_of_week"=>"Selasa","start_time"=>"09:45:00","end_time"=>"13:25:00","start_period"=>4,"end_period"=>7,"cycle_type"=>"Putaran 1"],
                ];
                foreach ($muhlasinSchedules as $sch) {
                    \Illuminate\Support\Facades\DB::table('schedules')->updateOrInsert(['id' => $sch['id']], $sch);
                }
            }

            // Pastikan AHMAD PRIANDOKO (NIS 17793, XII RPL 2) & Orang Tua dipulihkan
            $priandoko = \Illuminate\Support\Facades\DB::table('users')->where('id', 15270)->first();
            if (!$priandoko) {
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
            if (!$parentPriandoko) {
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
        if (!file_exists(public_path('storage'))) {
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
            'status'  => 'success',
            'message' => 'Migration dan pembersihan data dummy berhasil dieksekusi di server hosting.',
            'deleted_dummy_users' => $deletedCount,
            'deleted_list' => $deletedList,
            'first_users' => $firstUsers,
            'class_17_bd_1' => $class17Info,
            'class_66_rpl_2' => $class66Info,
            'output'  => $migrateOutput,
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'status'  => 'error',
            'message' => $e->getMessage(),
            'file'    => $e->getFile() . ':' . $e->getLine(),
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
            'data'    => $user,
            'user'    => $user
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
    Route::get('/leave-requests/{id}/document', [LeaveRequestController::class, 'downloadDocument'])->name('api.leave-requests.document');
    Route::match(['patch', 'put'], '/leave-requests/{id}/verify', [LeaveRequestController::class, 'verify']);

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
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
                ->where('username', '!=', 'waka_kurikulum')
                ->where(function ($q) use ($dummyUsernames) {
                    $q->whereIn('username', $dummyUsernames)
                      ->orWhereIn(\Illuminate\Support\Facades\DB::raw('TRIM(username)'), $dummyUsernames)
                      ->orWhereIn(\Illuminate\Support\Facades\DB::raw('LOWER(TRIM(username))'), $dummyUsernames)
                      ->orWhere('username', 'LIKE', '%ahmad%')
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

        return response()->json([
            'status'  => 'success',
            'message' => 'Migration dan pembersihan data dummy berhasil dieksekusi di server hosting.',
            'deleted_dummy_users' => $deletedCount,
            'deleted_list' => $deletedList,
            'first_users' => $firstUsers,
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
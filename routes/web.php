<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\ForgotPasswordController;
use App\Http\Controllers\Web\Waka\DashboardController;
use App\Http\Controllers\Web\Waka\ScheduleController;
use App\Http\Controllers\Web\Waka\JournalMonitoringController;
use App\Http\Controllers\Web\Waka\ClassController;
use App\Http\Controllers\Web\Waka\RoomController;
use App\Http\Controllers\Web\Waka\SubjectController;
use App\Http\Controllers\Web\Waka\CycleController;
use App\Http\Controllers\Web\Waka\UserController;
use App\Http\Controllers\Web\Waka\PromotionController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Landing Page (pengantar sebelum login)
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('waka.dashboard');
    }
    return view('landing');
})->name('landing');

// Autentikasi Web
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post')->middleware('throttle:6,1');
Route::match(['get', 'post'], '/logout', [AuthController::class, 'logout'])->name('logout');


// Lupa Password (Alur 3 Langkah: Email -> Verifikasi OTP -> Reset Password)
Route::get('/forgot-password', [ForgotPasswordController::class, 'showEmailForm'])
    ->name('password.forgot');
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendOtp'])
    ->name('password.send-otp')
    ->middleware('throttle:5,1');    // maks 5 permintaan OTP per menit

Route::get('/forgot-password/verify', [ForgotPasswordController::class, 'showVerifyOtpForm'])
    ->name('password.verify.form');
Route::post('/forgot-password/verify', [ForgotPasswordController::class, 'verifyOtp'])
    ->name('password.verify.post')
    ->middleware('throttle:10,1');  // maks 10 percobaan verifikasi per menit

Route::post('/forgot-password/resend', [ForgotPasswordController::class, 'resendOtp'])
    ->name('password.resend-otp')
    ->middleware('throttle:3,1');

Route::get('/reset-password', [ForgotPasswordController::class, 'showResetForm'])
    ->name('password.reset.form');
Route::post('/reset-password', [ForgotPasswordController::class, 'resetPassword'])
    ->name('password.reset.post')
    ->middleware('throttle:10,1');

// Area Khusus Waka Kurikulum / Admin (Terkunci Ketat Role ID 1)
Route::middleware(['auth', 'waka'])->prefix('waka')->name('waka.')->group(function () {
    // Dashboard & Kontrol Siklus / Kunci Jadwal
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/toggle-cycle', [DashboardController::class, 'toggleCycle'])->name('toggle-cycle');
    Route::post('/toggle-lock', [DashboardController::class, 'toggleLock'])->name('toggle-lock');

    // Manajemen Jadwal Pelajaran (Anti-Bentrok, Template Excel, & Impor Massal)
    Route::get('schedules/template', [ScheduleController::class, 'downloadTemplate'])->name('schedules.template');
    Route::post('schedules/import', [ScheduleController::class, 'import'])->name('schedules.import');
    Route::resource('schedules', ScheduleController::class);

    // Monitoring Jurnal Guru
    Route::get('/journals', [JournalMonitoringController::class, 'index'])->name('journals.index');

    // Master Data Kelas & Ruangan
    Route::post('classes/toggle-walas-absence', [ClassController::class, 'toggleWalasAbsence'])->name('classes.toggle-walas-absence');
    Route::resource('classes', ClassController::class)->except(['create', 'show', 'edit']);
    Route::post('classes/import-walas', [ClassController::class, 'importWalas'])->name('classes.import-walas');
    Route::post('classes/{id}/assign-walas', [ClassController::class, 'assignWalas'])->name('classes.assign-walas');
    Route::delete('classes/students/{studentId}', [ClassController::class, 'removeStudent'])->name('classes.remove-student');

    // Kenaikan Kelas & Kelulusan Siswa (Tingkat X, XI, XII)
    Route::get('promotions', [PromotionController::class, 'index'])->name('promotions.index');
    Route::post('promotions/promote', [PromotionController::class, 'promote'])->name('promotions.promote');
    Route::post('promotions/graduate', [PromotionController::class, 'graduate'])->name('promotions.graduate');
    Route::post('promotions/restore/{id}', [PromotionController::class, 'restoreStudent'])->name('promotions.restore');

    Route::post('rooms/import-matrix', [RoomController::class, 'importMatrix'])->name('rooms.import-matrix');
    Route::resource('rooms', RoomController::class)->except(['create', 'show', 'edit']);
    Route::get('subjects', [SubjectController::class, 'index'])->name('subjects.index');
    Route::put('subjects', [SubjectController::class, 'update'])->name('subjects.update');
    Route::get('cycles', [CycleController::class, 'index'])->name('cycles.index');

    // Manajemen Pengguna (CRUD Akun Semua Role, Template Excel, & Impor File)
    Route::get('users/template', [UserController::class, 'downloadTemplate'])->name('users.template');
    Route::post('users/import', [UserController::class, 'import'])->name('users.import');
    Route::resource('users', UserController::class)->except(['create', 'show', 'edit']);

    // Rekap Presensi Siswa
    Route::get('attendances/recap', [\App\Http\Controllers\Web\Waka\AttendanceRecapController::class, 'index'])->name('attendances.recap');
    Route::get('attendances/recap/class/{class}', [\App\Http\Controllers\Web\Waka\AttendanceRecapController::class, 'classDetail'])->name('attendances.recap.class');

    // Hari Libur Sekolah
    Route::get('holidays', [\App\Http\Controllers\Web\Waka\SchoolHolidayController::class, 'index'])->name('holidays.index');
    Route::post('holidays', [\App\Http\Controllers\Web\Waka\SchoolHolidayController::class, 'store'])->name('holidays.store');
    Route::delete('holidays/{id}', [\App\Http\Controllers\Web\Waka\SchoolHolidayController::class, 'destroy'])->name('holidays.destroy');
});



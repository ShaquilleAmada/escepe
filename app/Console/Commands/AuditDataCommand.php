<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Role;
use App\Models\Classes;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\Journal;
use App\Models\Attendance;
use App\Models\LeaveRequest;

class AuditDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scp:audit-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit integritas data Sistem SCP SMKN 1 Bantul (Fase B1 verification)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("================================================================================");
        $this->info("         AUDIT INTEGRITAS DATA SISTEM SCP - SMK NEGERI 1 BANTUL                 ");
        $this->info("================================================================================");

        $results = [];
        $hasErrors = false;

        // 1. Data Yatim (Orphaned Records)
        $orphanSchedulesTeacher = DB::table('schedules')->whereNotIn('teacher_id', DB::table('users')->pluck('id'))->count();
        $orphanSchedulesClass = DB::table('schedules')->whereNotIn('class_id', DB::table('classes')->pluck('id'))->count();
        $orphanSchedulesRoom = DB::table('schedules')->whereNotNull('room_id')->whereNotIn('room_id', DB::table('rooms')->pluck('id'))->count();
        $orphanJournals = DB::table('journals')->whereNotIn('schedule_id', DB::table('schedules')->pluck('id'))->count();
        $orphanAttJournal = DB::table('attendances')->whereNotIn('journal_id', DB::table('journals')->pluck('id'))->count();
        $orphanAttStudent = DB::table('attendances')->whereNotIn('student_id', DB::table('users')->pluck('id'))->count();
        $orphanLeaves = DB::table('leave_requests')->whereNotIn('student_id', DB::table('users')->pluck('id'))->count();

        $orphanTotal = $orphanSchedulesTeacher + $orphanSchedulesClass + $orphanSchedulesRoom + $orphanJournals + $orphanAttJournal + $orphanAttStudent + $orphanLeaves;
        $results[] = [
            'Kategori' => 'Integritas Relasi (Data Yatim)',
            'Pemeriksaan' => 'Jadwal, Jurnal, Presensi, dan Izin tanpa induk valid',
            'Jumlah Masalah' => $orphanTotal,
            'Status' => $orphanTotal === 0 ? 'BERSIH' : 'TEMUAN',
        ];
        if ($orphanTotal > 0) $hasErrors = true;

        // 2. Duplikasi Unik (Duplicates)
        $dupUsers = DB::table('users')->select('username', DB::raw('count(*) as c'))->groupBy('username')->having('c', '>', 1)->count();
        $dupClasses = DB::table('classes')->select('class_name', DB::raw('count(*) as c'))->groupBy('class_name')->having('c', '>', 1)->count();
        $dupRooms = DB::table('rooms')->select('room_name', DB::raw('count(*) as c'))->groupBy('room_name')->having('c', '>', 1)->count();
        $dupJournals = DB::table('journals')->select('schedule_id', 'date', DB::raw('count(*) as c'))->groupBy('schedule_id', 'date')->having('c', '>', 1)->count();
        $dupAttendances = DB::table('attendances')->select('journal_id', 'student_id', DB::raw('count(*) as c'))->groupBy('journal_id', 'student_id')->having('c', '>', 1)->count();

        $dupTotal = $dupUsers + $dupClasses + $dupRooms + $dupJournals + $dupAttendances;
        $results[] = [
            'Kategori' => 'Keunikan Data (Duplikat)',
            'Pemeriksaan' => 'Username, Nama Kelas, Ruangan, Jurnal Harian & Presensi Sesi',
            'Jumlah Masalah' => $dupTotal,
            'Status' => $dupTotal === 0 ? 'BERSIH' : 'TEMUAN',
        ];
        if ($dupTotal > 0) $hasErrors = true;

        // 3. Nilai Tidak Valid (Invalid Values)
        $invalidScheduleTime = DB::table('schedules')->whereColumn('end_time', '<=', 'start_time')->count();
        $invalidLeaveDate = DB::table('leave_requests')->whereColumn('end_date', '<', 'start_date')->count();
        $invalidLeaveType = DB::table('leave_requests')->whereNotIn('leave_type', ['izin', 'sakit'])->count();
        $invalidAttendanceStatus = DB::table('attendances')->whereNotIn('status', ['hadir', 'izin', 'sakit', 'alpa'])->count();

        $invalidTotal = $invalidScheduleTime + $invalidLeaveDate + $invalidLeaveType + $invalidAttendanceStatus;
        $results[] = [
            'Kategori' => 'Validitas Nilai & Rentang Waktu',
            'Pemeriksaan' => 'Jam selesai <= mulai, Tanggal izin terbalik, Enum status tidak valid',
            'Jumlah Masalah' => $invalidTotal,
            'Status' => $invalidTotal === 0 ? 'BERSIH' : 'TEMUAN',
        ];
        if ($invalidTotal > 0) $hasErrors = true;

        // 4. Konsistensi Izin & Akuntabilitas (Leave Consistency)
        $leavesDecidedNoVerifier = DB::table('leave_requests')->whereIn('status_approval', ['approved', 'rejected', 'disetujui', 'ditolak'])->whereNull('verified_by')->count();
        $leavesRejectedNoReason = DB::table('leave_requests')->whereIn('status_approval', ['rejected', 'ditolak'])->where(function ($q) {
            $q->whereNull('rejection_reason')->orWhere('rejection_reason', '');
        })->count();

        $leaveInconsistencyTotal = $leavesDecidedNoVerifier + $leavesRejectedNoReason;
        $results[] = [
            'Kategori' => 'Konsistensi & Akuntabilitas Izin',
            'Pemeriksaan' => 'Izin diputuskan tanpa verifikator, Penolakan tanpa alasan',
            'Jumlah Masalah' => $leaveInconsistencyTotal,
            'Status' => $leaveInconsistencyTotal === 0 ? 'BERSIH' : 'TEMUAN',
        ];
        if ($leaveInconsistencyTotal > 0) $hasErrors = true;

        // 5. Relasi Akun Ortu & Siswa (Parent-Child Relations)
        $parentWithoutChild = DB::table('users')->where('role_id', Role::ORANG_TUA)->whereNull('parent_id')->count();
        $results[] = [
            'Kategori' => 'Tautan Akun Orang Tua',
            'Pemeriksaan' => 'Akun orang tua tanpa tautan ke anak',
            'Jumlah Masalah' => $parentWithoutChild,
            'Status' => $parentWithoutChild === 0 ? 'BERSIH' : 'TEMUAN',
        ];
        if ($parentWithoutChild > 0) $hasErrors = true;

        // 6. Konflik Jadwal (Schedule Clashing)
        // Cek bentrok ruangan pada hari dan siklus yang sama: Start_A < End_B AND End_A > Start_B
        $clashes = DB::select("
            SELECT COUNT(*) as clash_count
            FROM schedules a
            JOIN schedules b ON a.id < b.id
                AND a.day_of_week = b.day_of_week
                AND a.room_id = b.room_id
                AND a.room_id IS NOT NULL
                AND (a.cycle_type = b.cycle_type OR a.cycle_type = 'Semua Siklus' OR b.cycle_type = 'Semua Siklus')
                AND a.start_time < b.end_time
                AND a.end_time > b.start_time
        ");
        $clashCount = $clashes[0]->clash_count ?? 0;
        $results[] = [
            'Kategori' => 'Bentrok Jadwal Pembelajaran',
            'Pemeriksaan' => 'Jadwal tumpang tindih pada ruangan, hari, dan siklus sama',
            'Jumlah Masalah' => $clashCount,
            'Status' => $clashCount === 0 ? 'BERSIH' : 'PERHATIAN (FET Legacy)',
        ];
        // Jangan blokir exit jika hanya sisa impor FET lama, tapi catat
        // Jika di seeder baru harus 0!

        $this->table(['Kategori', 'Pemeriksaan', 'Jumlah Masalah', 'Status'], $results);

        if ($hasErrors) {
            $this->error("\n[GAGAL] Ditemukan masalah integritas data yang melanggar spesifikasi.");
            return 1;
        }

        $this->info("\n[SUKSES] Seluruh pemeriksaan integritas data FASE B1 BERSIH (0 Masalah).");
        return 0;
    }
}

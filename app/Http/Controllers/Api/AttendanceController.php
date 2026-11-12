<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Classes;
use App\Models\LeaveRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\AttendanceRecapService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AttendanceController extends Controller
{
    /**
     * Guru melakukan checklist kehadiran siswa (Hadir, Sakit, Izin, Alpa)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'journal_id' => 'required|exists:journals,id',
            'date' => 'required|date',
            'attendances' => 'required|array',
            'attendances.*.student_id' => 'required|exists:users,id',
            'attendances.*.status' => 'required|in:hadir,izin,sakit,alpa',
        ]);

        $saved = [];
        foreach ($validated['attendances'] as $item) {
            $att = Attendance::updateOrCreate(
                [
                    'journal_id' => $validated['journal_id'],
                    'student_id' => $item['student_id'],
                ],
                [
                    'status' => $item['status'],
                    'date' => $validated['date'],
                ]
            );
            $saved[] = $att;
        }

        return response()->json([
            'success' => true,
            'message' => 'Presensi siswa berhasil disimpan',
            'data' => $saved,
        ], 200);
    }

    /**
     * Siswa/Ortu melihat riwayat presensi harian
     */
    public function history(Request $request)
    {
        $user = $request->user();
        $studentId = $request->student_id;

        if ($user->role_id == Role::SISWA) {
            $studentId = $user->id;
        } elseif ($user->role_id == Role::ORANG_TUA) {
            $child = $user->children()->first();
            $studentId = $child ? $child->id : null;
        }

        $query = Attendance::with(['journal.schedule.teacher:id,fullname', 'journal.schedule.room:id,room_name', 'student:id,fullname']);

        if ($studentId) {
            $query->where('student_id', $studentId);
        }

        $history = $query->orderBy('date', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $history,
        ], 200);
    }

    /**
     * Rekapitulasi absensi per siswa / per kelas (untuk Guru, Wali Kelas, dan BK)
     * Menggunakan rentang tanggal, hari efektif sekolah, dan menampilkan persentase bersama cakupan.
     */
    public function recap(Request $request)
    {
        $user = $request->user();

        // Otorisasi role:
        // Walas (Role 6): Jika menyertakan class_id, hanya boleh kelas binaannya sendiri
        $classId = $request->input('class_id');
        if ($user && $user->role_id == Role::WALI_KELAS) {
            if ($classId && $classId != $user->class_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses ditolak. Wali kelas hanya berhak melihat rekap presensi kelas binaannya.',
                ], 403);
            }
            $classId = $user->class_id;
        }

        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->toDateString());

        $effectiveDays = AttendanceRecapService::getEffectiveDays($startDate, $endDate);

        $query = User::where('role_id', Role::SISWA)->with('schoolClass');
        if ($classId) {
            $query->where('class_id', $classId);
        }

        $students = $query->orderBy('fullname')->get();

        $recap = $students->map(function ($student) use ($startDate, $endDate, $effectiveDays) {
            $attendances = Attendance::where('student_id', $student->id)
                ->whereBetween('date', [$startDate, $endDate])
                ->get();

            $hadir = $attendances->where('status', 'hadir')->count();
            $izin = $attendances->where('status', 'izin')->count();
            $sakit = $attendances->where('status', 'sakit')->count();
            $alpa = $attendances->where('status', 'alpa')->count();

            // Hitung sakit & izin dari surat izin yang sudah disetujui (Approved)
            $approvedLeaves = LeaveRequest::where('student_id', $student->id)
                ->where('status_approval', 'approved')
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('start_date', [$startDate, $endDate])
                        ->orWhereBetween('end_date', [$startDate, $endDate])
                        ->orWhere(function ($sub) use ($startDate, $endDate) {
                            $sub->where('start_date', '<=', $startDate)
                                ->where('end_date', '>=', $endDate);
                        });
                })
                ->get();

            $sakitFromLeaves = 0;
            $izinFromLeaves = 0;
            foreach ($approvedLeaves as $al) {
                $start = Carbon::parse($al->start_date)->max(Carbon::parse($startDate));
                $end = Carbon::parse($al->end_date)->min(Carbon::parse($endDate));
                if ($start->lte($end)) {
                    $days = $start->diffInDays($end) + 1;
                    if ($al->leave_type === 'sakit') {
                        $sakitFromLeaves += $days;
                    } else {
                        $izinFromLeaves += $days;
                    }
                }
            }

            $sakit = max($sakit, $sakitFromLeaves);
            $izin = max($izin, $izinFromLeaves);
            $total = max($attendances->count(), $hadir + $izin + $sakit + $alpa);

            $percentageNum = $total > 0 ? round(($hadir / $total) * 100, 1) : 0;
            $persentase = $total > 0 ? $percentageNum.'%' : '0%';
            $cakupan = "terisi {$total} sesi dari {$effectiveDays} hari efektif";

            return [
                'student_id' => $student->id,
                'fullname' => $student->fullname,
                'nip_nisn' => $student->nip_nisn ?? '-',
                'class_name' => $student->schoolClass->class_name ?? '-',
                'total_pertemuan' => $total,
                'hadir' => $hadir,
                'izin' => $izin,
                'sakit' => $sakit,
                'alpa' => $alpa,
                'persentase_kehadiran' => $persentase,
                'cakupan_kehadiran' => "{$persentase} ({$cakupan})",
            ];
        });

        return response()->json([
            'success' => true,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'effective_days' => $effectiveDays,
            'total_students' => $recap->count(),
            'data' => $recap,
        ], 200);
    }

    /**
     * Cetak PDF resmi rekapitulasi presensi kelas
     * Wali Kelas: hanya kelas binaan
     * Guru BK: semua kelas (dropdown)
     * Waka (web): hanya lihat, tanpa cetak PDF
     */
    public function downloadPdf(Request $request)
    {
        $user = $request->user();

        // Dukung otorisasi via query token untuk download langsung dari browser tab
        if (! $user && $request->filled('token')) {
            $tokenRecord = \Laravel\Sanctum\PersonalAccessToken::findToken($request->query('token'));
            if ($tokenRecord) {
                $user = $tokenRecord->tokenable;
            }
        }

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // Otorisasi ketat
        if (! in_array($user->role_id, [Role::WALI_KELAS, Role::BK])) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Cetak PDF resmi presensi hanya diizinkan untuk Wali Kelas dan Guru BK.',
            ], 403);
        }

        $classId = $request->input('class_id');
        if ($user->role_id == Role::WALI_KELAS) {
            $classId = $user->class_id ?? $classId;
        } elseif ($user->role_id == Role::BK) {
            if (! $classId) {
                $classId = $user->bkClasses()->first()?->id ?? Classes::first()?->id;
            } else {
                $checkClass = Classes::find($classId);
                if ($checkClass && $checkClass->bk_id && (int) $checkClass->bk_id !== (int) $user->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Akses ditolak: Anda hanya berwenang mencetak laporan presensi untuk kelas binaan Anda.',
                    ], 403);
                }
            }
        }

        // Fallback jika belum ada class_id
        if (! $classId) {
            $firstClass = Classes::first();
            $classId = $firstClass ? $firstClass->id : null;
        }

        if (! $classId) {
            return response()->json([
                'success' => false,
                'message' => 'Parameter class_id wajib disertakan atau belum ada data kelas di sistem.',
            ], 422);
        }

        $class = Classes::with('walas')->find($classId);
        if (! $class) {
            $class = Classes::with('walas')->first();
        }

        if (! $class) {
            return response()->json([
                'success' => false,
                'message' => 'Data kelas tidak ditemukan.',
            ], 404);
        }

        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->toDateString());

        $effectiveDays = AttendanceRecapService::getEffectiveDays($startDate, $endDate);

        $students = User::where('role_id', Role::SISWA)
            ->where('class_id', $class->id)
            ->orderBy('fullname')
            ->get()
            ->map(function ($student) use ($startDate, $endDate, $effectiveDays) {
                $attendances = Attendance::where('student_id', $student->id)
                    ->whereBetween('date', [$startDate, $endDate])
                    ->get();
                $total = $attendances->count();

                $hadir = $attendances->where('status', 'hadir')->count();
                $izin = $attendances->where('status', 'izin')->count();
                $sakit = $attendances->where('status', 'sakit')->count();
                $alpa = $attendances->where('status', 'alpa')->count();

                $percentageNum = $total > 0 ? round(($hadir / $total) * 100, 1) : 0;
                $persentase = $total > 0 ? $percentageNum.'%' : '0%';

                return [
                    'student_id' => $student->id,
                    'fullname' => $student->fullname,
                    'nip_nisn' => $student->nip_nisn ?? '-',
                    'total_pertemuan' => $total,
                    'hadir' => $hadir,
                    'izin' => $izin,
                    'sakit' => $sakit,
                    'alpa' => $alpa,
                    'persentase_kehadiran' => $persentase,
                    'cakupan' => "{$total} sesi / {$effectiveDays} hari",
                ];
            });

        // Audit Trail Pencatatan Siapa yang Mencetak PDF
        Log::info("PDF Presensi dicetak oleh User ID {$user->id} ({$user->fullname}, Role: {$user->role_id}) untuk Kelas {$class->class_name} periode {$startDate} s/d {$endDate}");

        $walasName = $class->walas->fullname ?? 'Belum Ditentukan';
        $walasNip = $class->walas->nip_nisn ?? '-';
        $printedByName = $user->fullname;
        $printedById = $user->nip_nisn ?: $user->username;

        $pdf = Pdf::loadView('pdf.attendance_recap', compact(
            'class', 'startDate', 'endDate', 'effectiveDays',
            'students', 'walasName', 'walasNip', 'printedByName', 'printedById'
        ));

        $fileName = 'Rekap_Presensi_'.str_replace(' ', '_', $class->class_name)."_{$startDate}_{$endDate}.pdf";

        return $pdf->download($fileName);
    }
}

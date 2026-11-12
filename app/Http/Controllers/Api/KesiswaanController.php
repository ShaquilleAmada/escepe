<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Classes;
use App\Models\LeaveRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class KesiswaanController extends Controller
{
    /**
     * Rekap siswa yang sering tidak hadir (alpa/izin berlebih) untuk ditindaklanjuti BK
     */
    public function monitoring(Request $request)
    {
        $students = User::where('role_id', 3)->with('schoolClass')->get();

        $problematicStudents = $students->map(function ($student) {
            $attendances = Attendance::where('student_id', $student->id)->get();
            $alpaCount = $attendances->where('status', 'alpa')->count();
            $izinCount = $attendances->where('status', 'izin')->count();
            $sakitCount = $attendances->where('status', 'sakit')->count();

            // Hitung sakit & izin dari surat izin yang sudah disetujui (Approved)
            $approvedLeaves = LeaveRequest::where('student_id', $student->id)
                ->where('status_approval', 'approved')
                ->get();
            $sakitFromLeaves = 0;
            $izinFromLeaves = 0;
            foreach ($approvedLeaves as $al) {
                $start = Carbon::parse($al->start_date);
                $end = Carbon::parse($al->end_date);
                $days = $start->diffInDays($end) + 1;
                if ($al->leave_type === 'sakit') {
                    $sakitFromLeaves += $days;
                } else {
                    $izinFromLeaves += $days;
                }
            }
            $sakitCount = max($sakitCount, $sakitFromLeaves);
            $izinCount = max($izinCount, $izinFromLeaves);
            $total = max($attendances->count(), $alpaCount + $izinCount + $sakitCount + $attendances->where('status', 'hadir')->count());

            return [
                'student_id' => $student->id,
                'fullname' => $student->fullname,
                'username' => $student->username,
                'class_name' => $student->schoolClass->class_name ?? '-',
                'alpa_count' => $alpaCount,
                'izin_count' => $izinCount,
                'sakit_count' => $sakitCount,
                'total_sesi' => $total,
                'needs_counseling' => ($alpaCount >= 2 || ($izinCount + $sakitCount) >= 4),
            ];
        })->sortByDesc('alpa_count')->values();

        return response()->json([
            'success' => true,
            'data' => $problematicStudents,
        ], 200);
    }

    /**
     * Wali kelas melihat daftar siswa di kelas binaannya beserta status kehadirannya
     */
    public function getStudentsByClass(Request $request, $classId)
    {
        $schoolClass = Classes::findOrFail($classId);
        $students = User::where('role_id', 3)
            ->where('class_id', $classId)
            ->orderBy('fullname')
            ->get();

        $data = $students->map(function ($student) {
            $attendances = Attendance::where('student_id', $student->id)->get();
            $pendingLeaves = LeaveRequest::where('student_id', $student->id)
                ->where('status_approval', 'pending')
                ->count();

            // Hitung sakit & izin dari surat izin yang sudah disetujui (Approved)
            $approvedLeaves = LeaveRequest::where('student_id', $student->id)
                ->where('status_approval', 'approved')
                ->get();
            $sakitFromLeaves = 0;
            $izinFromLeaves = 0;
            foreach ($approvedLeaves as $al) {
                $start = Carbon::parse($al->start_date);
                $end = Carbon::parse($al->end_date);
                $days = $start->diffInDays($end) + 1;
                if ($al->leave_type === 'sakit') {
                    $sakitFromLeaves += $days;
                } else {
                    $izinFromLeaves += $days;
                }
            }

            $hadirCount = $attendances->where('status', 'hadir')->count();
            $sakitCount = max($attendances->where('status', 'sakit')->count(), $sakitFromLeaves);
            $izinCount = max($attendances->where('status', 'izin')->count(), $izinFromLeaves);
            $alpaCount = $attendances->where('status', 'alpa')->count();

            return [
                'student_id' => $student->id,
                'fullname' => $student->fullname,
                'username' => $student->username,
                'nip_nisn' => $student->nip_nisn ?? '-',
                'nis' => $student->nip_nisn ?: ($student->username ?: '-'),
                'hadir' => $hadirCount,
                'izin' => $izinCount,
                'sakit' => $sakitCount,
                'alpa' => $alpaCount,
                'pending_leaves_count' => $pendingLeaves,
            ];
        });

        return response()->json([
            'success' => true,
            'class' => $schoolClass,
            'total_students' => $data->count(),
            'students' => $data,
        ], 200);
    }

    /**
     * Mengambil riwayat catatan konseling BK
     */
    public function getCounselingLogs(Request $request)
    {
        $logs = \App\Models\CounselingLog::with(['student.schoolClass', 'counselor'])
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $data = $logs->map(function ($log) {
            $notesData = [];
            if (!empty($log->notes)) {
                $decoded = json_decode($log->notes, true);
                if (is_array($decoded)) {
                    $notesData = $decoded;
                }
            }

            return [
                'id' => (string) $log->id,
                'student_id' => $log->student_id,
                'nama_siswa' => $notesData['nama_siswa'] ?? ($log->student->fullname ?? 'Siswa'),
                'kelas' => $notesData['kelas'] ?? ($log->student->schoolClass->class_name ?? '-'),
                'tanggal' => $log->date ? \Carbon\Carbon::parse($log->date)->translatedFormat('d M Y') : '-',
                'jenis_kasus' => $notesData['jenis_kasus'] ?? ($log->consecutive_alpa_days > 0 ? "Akumulasi Alpa {$log->consecutive_alpa_days} Hari" : 'Konseling Disiplin'),
                'ringkasan_masalah' => $notesData['ringkasan_masalah'] ?? (is_string($log->notes) && !json_decode($log->notes) ? $log->notes : 'Catatan konseling tercatat.'),
                'solusi_tindak_lanjut' => $notesData['solusi_tindak_lanjut'] ?? 'Dalam pemantauan guru BK dan wali kelas.',
                'status' => $notesData['status'] ?? 'Dalam Pemantauan',
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200);
    }

    /**
     * Menyimpan catatan konseling baru oleh Guru BK
     */
    public function storeCounselingLog(Request $request)
    {
        $request->validate([
            'nama_siswa' => 'nullable|string',
            'kelas' => 'nullable|string',
            'jenis_kasus' => 'nullable|string',
            'ringkasan_masalah' => 'nullable|string',
            'solusi_tindak_lanjut' => 'nullable|string',
            'status' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $counselorId = $request->user() ? $request->user()->id : null;
        if (!$counselorId) {
            $counselor = User::where('role_id', 5)->first();
            $counselorId = $counselor ? $counselor->id : 1;
        }

        $studentId = $request->input('student_id');
        if ($studentId && !User::where('id', $studentId)->exists()) {
            $studentId = null;
        }

        if (!$studentId && $request->filled('nama_siswa')) {
            $student = User::where('role_id', 3)
                ->where('fullname', 'like', '%' . $request->input('nama_siswa') . '%')
                ->first();
            if ($student) {
                $studentId = $student->id;
            }
        }

        if (!$studentId) {
            $defaultStudent = User::where('role_id', 3)->first();
            $studentId = $defaultStudent ? $defaultStudent->id : 1;
        }

        $notesPayload = [
            'nama_siswa' => $request->input('nama_siswa', 'Siswa'),
            'kelas' => $request->input('kelas', '-'),
            'jenis_kasus' => $request->input('jenis_kasus', 'Bimbingan Konseling'),
            'ringkasan_masalah' => $request->input('ringkasan_masalah', $request->input('notes', 'Catatan pembinaan kedisiplinan.')),
            'solusi_tindak_lanjut' => $request->input('solusi_tindak_lanjut', 'Siswa diberi arahan dan menyetujui komitmen pembinaan.'),
            'status' => $request->input('status', 'Dalam Pemantauan'),
        ];

        $log = \App\Models\CounselingLog::create([
            'student_id' => $studentId,
            'counselor_id' => $counselorId,
            'date' => $request->input('date', \Carbon\Carbon::today()->toDateString()),
            'consecutive_alpa_days' => (int) $request->input('consecutive_alpa_days', 0),
            'notes' => json_encode($notesPayload, JSON_UNESCAPED_UNICODE),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Catatan konseling berhasil disimpan ke database.',
            'data' => [
                'id' => (string) $log->id,
                'student_id' => $log->student_id,
                'nama_siswa' => $notesPayload['nama_siswa'],
                'kelas' => $notesPayload['kelas'],
                'tanggal' => \Carbon\Carbon::parse($log->date)->translatedFormat('d M Y'),
                'jenis_kasus' => $notesPayload['jenis_kasus'],
                'ringkasan_masalah' => $notesPayload['ringkasan_masalah'],
                'solusi_tindak_lanjut' => $notesPayload['solusi_tindak_lanjut'],
                'status' => $notesPayload['status'],
            ],
        ], 201);
    }
}

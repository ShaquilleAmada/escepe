<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Classes;
use App\Models\Attendance;
use App\Models\LeaveRequest;

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
            $total = $attendances->count();

            return [
                'student_id' => $student->id,
                'fullname' => $student->fullname,
                'username' => $student->username,
                'class_name' => $student->schoolClass->class_name ?? '-',
                'alpa_count' => $alpaCount,
                'izin_count' => $izinCount,
                'sakit_count' => $sakitCount,
                'total_sesi' => $total,
                'needs_counseling' => ($alpaCount >= 2 || ($izinCount + $sakitCount) >= 4)
            ];
        })->sortByDesc('alpa_count')->values();

        return response()->json([
            'success' => true,
            'data' => $problematicStudents
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

            return [
                'student_id' => $student->id,
                'fullname' => $student->fullname,
                'username' => $student->username,
                'nip_nisn' => $student->nip_nisn ?? '-',
                'nis' => $student->nip_nisn ?: ($student->username ?: '-'),
                'hadir' => $attendances->where('status', 'hadir')->count(),
                'izin' => $attendances->where('status', 'izin')->count(),
                'sakit' => $attendances->where('status', 'sakit')->count(),
                'alpa' => $attendances->where('status', 'alpa')->count(),
                'pending_leaves_count' => $pendingLeaves,
            ];
        });

        return response()->json([
            'success' => true,
            'class' => $schoolClass,
            'total_students' => $data->count(),
            'students' => $data
        ], 200);
    }
}

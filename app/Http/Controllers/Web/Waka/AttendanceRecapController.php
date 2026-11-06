<?php

namespace App\Http\Controllers\Web\Waka;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Classes;
use App\Models\Journal;
use App\Models\Attendance;
use App\Models\User;
use App\Models\Schedule;
use App\Models\SystemConfiguration;
use App\Services\AttendanceRecapService;
use Carbon\Carbon;

class AttendanceRecapController extends Controller
{
    public function index(Request $request)
    {
        $grades   = ['XII', 'XI', 'X'];
        $majors   = Classes::MAJORS;
        sort($majors);
        $sections = Classes::SECTIONS;

        // Default tanggal: Hari ini (Y-m-d)
        $date = $request->input('date', Carbon::today()->toDateString());
        $statusFilter = $request->input('status_filter', 'all'); // all, pending, final

        // Filter Kelas berdasarkan Tingkat, Jurusan, Rombel
        $classQuery = Classes::query();
        if ($request->filled('grade_level')) {
            $classQuery->where('grade_level', $request->grade_level);
        }
        if ($request->filled('major')) {
            $classQuery->where('major', $request->major);
        }
        if ($request->filled('section')) {
            $classQuery->where('section', $request->section);
        }

        // Urutkan dari kelas 12, 11, 10 (XII -> XI -> X), lalu jurusan (A-Z), lalu rombel (1 -> 4)
        $classes = $classQuery
            ->orderByRaw("CASE 
                WHEN grade_level = 'XII' OR class_name LIKE 'XII %' THEN 1 
                WHEN grade_level = 'XI' OR class_name LIKE 'XI %' THEN 2 
                WHEN grade_level = 'X' OR class_name LIKE 'X %' THEN 3 
                ELSE 4 END ASC")
            ->orderBy('major', 'asc')
            ->orderByRaw("CAST(section AS UNSIGNED) ASC")
            ->orderBy('class_name', 'asc')
            ->get();

        $classIds = $classes->pluck('id')->toArray();

        // Ambil data Jurnal pada tanggal tersebut untuk kelas-kelas ini
        $journals = Journal::whereDate('date', $date)
            ->whereHas('schedule', function ($q) use ($classIds) {
                $q->whereIn('class_id', $classIds);
            })
            ->with(['schedule'])
            ->get();

        $journalsByClass = $journals->groupBy(function ($j) {
            return $j->schedule->class_id ?? 0;
        });

        // Ambil data Attendance pada tanggal tersebut
        $attendances = Attendance::whereDate('date', $date)
            ->where(function ($query) use ($classIds) {
                $query->whereHas('journal.schedule', function ($q) use ($classIds) {
                    $q->whereIn('class_id', $classIds);
                })->orWhereHas('student', function ($q) use ($classIds) {
                    $q->whereIn('class_id', $classIds);
                });
            })
            ->with(['student', 'journal.schedule'])
            ->get();

        // Rekapitulasi per Kelas beserta Status Belum Final / Final
        $recapPerClass = $classes->map(function ($class) use ($journalsByClass, $attendances, $date) {
            $classJournals = $journalsByClass->get($class->id, collect());
            $journalCount  = $classJournals->count();

            $classAtts = $attendances->filter(function ($att) use ($class) {
                $studentClassId = $att->student->class_id ?? null;
                $journalClassId = $att->journal->schedule->class_id ?? null;
                return $studentClassId == $class->id || $journalClassId == $class->id;
            });

            $hadir = $classAtts->where('status', 'hadir')->count();
            $izin  = $classAtts->where('status', 'izin')->count();
            $sakit = $classAtts->where('status', 'sakit')->count();
            $alpa  = $classAtts->where('status', 'alpa')->count();

            // Status Final vs Belum Final otomatis
            $recapStatus = AttendanceRecapService::getClassDayStatus($class->id, $date);

            return [
                'class_id'     => $class->id,
                'class_name'   => $class->class_name,
                'jurnal'       => $journalCount,
                'hadir'        => $hadir,
                'izin'         => $izin,
                'sakit'        => $sakit,
                'alpa'         => $alpa,
                'status'       => $recapStatus['status'],
                'status_label' => $recapStatus['label'],
                'status_color' => $recapStatus['color'],
                'status_reason'=> $recapStatus['reason'],
                'is_final'     => $recapStatus['is_final'],
            ];
        });

        $totalClasses = $recapPerClass->count();
        $finalClassesCount = $recapPerClass->where('is_final', true)->count();
        $pendingClassesCount = $recapPerClass->where('is_final', false)->count();

        // Terapkan filter cepat status (Semua | Hanya yang Belum Final | Final)
        if ($statusFilter === 'pending') {
            $recapPerClass = $recapPerClass->where('is_final', false)->values();
        } elseif ($statusFilter === 'final') {
            $recapPerClass = $recapPerClass->where('is_final', true)->values();
        }

        // Summary totals across classes
        $totalHadir = $recapPerClass->sum('hadir');
        $totalSakit = $recapPerClass->sum('sakit');
        $totalIzin  = $recapPerClass->sum('izin');
        $totalAlpa  = $recapPerClass->sum('alpa');

        return view('waka.attendances.recap', compact(
            'classes', 'grades', 'majors', 'sections', 'date', 'statusFilter',
            'recapPerClass', 'totalHadir', 'totalSakit', 'totalIzin', 'totalAlpa',
            'totalClasses', 'finalClassesCount', 'pendingClassesCount'
        ));
    }

    /**
     * Detail Presensi Siswa per Kelas untuk Modal Pop-up
     */
    public function classDetail(Request $request, Classes $class)
    {
        $date = $request->input('date', Carbon::today()->toDateString());

        // Ambil semua siswa di kelas ini (role_id = 3)
        $students = User::where('role_id', 3)
            ->where('class_id', $class->id)
            ->orderBy('fullname')
            ->get();

        // Ambil Jurnal kelas ini pada tanggal tersebut
        $journals = Journal::whereDate('date', $date)
            ->whereHas('schedule', fn($q) => $q->where('class_id', $class->id))
            ->with(['schedule.teacher'])
            ->get();

        // Ambil Attendance siswa kelas ini pada tanggal tersebut
        $attendances = Attendance::whereDate('date', $date)
            ->whereIn('student_id', $students->pluck('id'))
            ->with('journal.schedule.teacher')
            ->get()
            ->groupBy('student_id');

        $studentList = $students->map(function ($s) use ($attendances) {
            $studentAtts = $attendances->get($s->id, collect());
            
            if ($studentAtts->isEmpty()) {
                $status = 'belum_diabsen';
                $statusLabel = 'Belum Ada Data';
            } else {
                $first = $studentAtts->first();
                $status = $first->status;
                $statusLabel = ucfirst($first->status == 'alpa' ? 'Alpha' : $first->status);
            }

            return [
                'id'           => $s->id,
                'fullname'     => $s->fullname,
                'username'     => $s->username,
                'nip_nisn'     => $s->nip_nisn ?? '-',
                'status'       => $status,
                'status_label' => $statusLabel,
                'attendances'  => $studentAtts->map(fn($a) => [
                    'status'  => $a->status,
                    'subject' => $a->journal->schedule->subject ?? '-',
                    'teacher' => $a->journal->schedule->teacher->fullname ?? '-',
                ]),
            ];
        });

        $summary = [
            'total_students' => $students->count(),
            'hadir'          => $studentList->where('status', 'hadir')->count(),
            'izin'           => $studentList->where('status', 'izin')->count(),
            'sakit'          => $studentList->where('status', 'sakit')->count(),
            'alpa'           => $studentList->where('status', 'alpa')->count(),
            'belum_diabsen'  => $studentList->where('status', 'belum_diabsen')->count(),
        ];

        return response()->json([
            'class_id'       => $class->id,
            'class_name'     => $class->class_name,
            'date'           => $date,
            'date_formatted' => Carbon::parse($date)->format('d/m/Y'),
            'journals'       => $journals->map(fn($j) => [
                'id'          => $j->id,
                'subject'     => $j->schedule->subject ?? '-',
                'teacher'     => $j->schedule->teacher->fullname ?? '-',
                'period_text' => $j->schedule->period_text ?? '-',
            ]),
            'summary'        => $summary,
            'students'       => $studentList->values(),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Web\Waka;

use App\Http\Controllers\Controller;
use App\Models\Classes;
use App\Models\Journal;
use App\Models\User;
use Illuminate\Http\Request;

class JournalMonitoringController extends Controller
{
    public function index(Request $request)
    {
        $teachers = User::whereIn('role_id', [1, 2, 5, 6])->orderBy('fullname')->get();
        $classes = Classes::orderBy('class_name')->get();
        $grades = Classes::GRADE_LEVELS;
        $majors = Classes::MAJORS;
        sort($majors);
        $sections = Classes::SECTIONS;

        $query = Journal::with(['schedule.teacher', 'schedule.schoolClass', 'schedule.room', 'attendances.student:id,fullname,username,nip_nisn']);

        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }
        if ($request->filled('teacher_id')) {
            $query->whereHas('schedule', function ($q) use ($request) {
                $q->where('teacher_id', $request->teacher_id);
            });
        }
        if ($request->filled('class_id')) {
            $query->whereHas('schedule', function ($q) use ($request) {
                $q->where('class_id', $request->class_id);
            });
        }
        if ($request->filled('grade_level')) {
            $query->whereHas('schedule.schoolClass', function ($q) use ($request) {
                $q->where(function ($sub) use ($request) {
                    $sub->where('grade_level', $request->grade_level)
                        ->orWhere('class_name', 'LIKE', $request->grade_level.' %');
                });
            });
        }
        if ($request->filled('major')) {
            $query->whereHas('schedule.schoolClass', function ($q) use ($request) {
                $q->where(function ($sub) use ($request) {
                    $sub->where('major', strtoupper($request->major))
                        ->orWhere('class_name', 'LIKE', '% '.$request->major.' %');
                });
            });
        }
        if ($request->filled('section')) {
            $query->whereHas('schedule.schoolClass', function ($q) use ($request) {
                $q->where(function ($sub) use ($request) {
                    $sub->where('section', $request->section)
                        ->orWhere('class_name', 'LIKE', '% '.$request->section);
                });
            });
        }

        $journals = $query->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('waka.journals.index', compact('journals', 'teachers', 'classes', 'grades', 'majors', 'sections'));
    }

    /**
     * Mengambil detail satu jurnal beserta rincian presensi siswa untuk modal monitoring.
     */
    public function show($id)
    {
        $journal = Journal::with([
            'schedule.teacher:id,fullname,username',
            'schedule.schoolClass:id,class_name',
            'schedule.room:id,room_name',
            'attendances.student:id,fullname,username,nip_nisn',
        ])->findOrFail($id);

        $hadir = $journal->attendances->where('status', 'hadir')->count();
        $izin = $journal->attendances->where('status', 'izin')->count();
        $sakit = $journal->attendances->where('status', 'sakit')->count();
        $alpa = $journal->attendances->where('status', 'alpa')->count();

        $photoUrl = $journal->photo_path ? route('waka.journals.photo', $journal->id) : null;

        return response()->json([
            'success' => true,
            'journal' => [
                'id' => $journal->id,
                'date' => date('d/m/Y', strtotime($journal->date)),
                'topic' => $journal->topic_description,
                'teacher' => $journal->schedule->teacher->fullname ?? 'Guru Pengampu',
                'subject' => $journal->schedule->subject ?? '-',
                'class_name' => $journal->schedule->schoolClass->class_name ?? '-',
                'room_name' => $journal->schedule->room->room_name ?? 'Ruang Kelas',
                'time' => substr($journal->schedule->start_time ?? '', 0, 5).' - '.substr($journal->schedule->end_time ?? '', 0, 5),
                'photo_path' => $journal->photo_path,
                'attendances' => $journal->attendances->map(function ($att) {
                    return [
                        'id' => $att->id,
                        'student_id' => $att->student_id,
                        'name' => $att->student->fullname ?? 'Siswa',
                        'nisn' => $att->student->nip_nisn ?? ($att->student->username ?? '-'),
                        'status' => $att->status,
                    ];
                }),
            ],
            'stats' => [
                'total' => $journal->attendances->count(),
                'hadir' => $hadir,
                'izin' => $izin,
                'sakit' => $sakit,
                'alpa' => $alpa,
            ],
            'photo_url' => $photoUrl,
        ]);
    }

    /**
     * Streaming berkas foto jurnal bukti KBM secara aman.
     */
    public function photo($id)
    {
        $journal = Journal::findOrFail($id);
        if (! $journal->photo_path) {
            abort(404, 'Foto tidak ditemukan');
        }

        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($journal->photo_path)) {
            return response()->file(\Illuminate\Support\Facades\Storage::disk('public')->path($journal->photo_path), [
                'Access-Control-Allow-Origin' => '*',
                'Cache-Control' => 'no-cache, private',
            ]);
        }

        $altPath = public_path('storage/'.$journal->photo_path);
        if (file_exists($altPath)) {
            return response()->file($altPath, [
                'Access-Control-Allow-Origin' => '*',
                'Cache-Control' => 'no-cache, private',
            ]);
        }

        abort(404, 'File fisik foto tidak ditemukan');
    }
}

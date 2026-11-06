<?php

namespace App\Http\Controllers\Web\Waka;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Schedule;
use App\Models\User;

class SubjectController extends Controller
{
    /**
     * Menampilkan daftar mata pelajaran berdasarkan jadwal mengajar guru di database.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $teacherFilter = $request->input('teacher_id');

        // Query seluruh jadwal dengan relasi guru, kelas, ruangan
        $query = Schedule::with(['teacher', 'schoolClass', 'room']);

        if ($teacherFilter) {
            $query->where('teacher_id', $teacherFilter);
        }

        $allSchedules = $query->get();

        // Kelompokkan berdasarkan nama mata pelajaran
        $grouped = $allSchedules->groupBy('subject')->map(function ($items, $subjectName) {
            // Urutkan rincian jadwal berdasarkan hari dan jam
            $sortedSchedules = $items->sortBy([
                ['day_of_week', 'asc'],
                ['start_time', 'asc'],
            ])->values();

            return (object) [
                'subject' => $subjectName,
                'teachers' => $items->pluck('teacher')->filter()->unique('id')->values(),
                'classes' => $items->pluck('schoolClass')->filter()->unique('id')->values(),
                'rooms' => $items->pluck('room')->filter()->unique('id')->values(),
                'total_sessions' => $items->count(),
                'schedules' => $sortedSchedules,
            ];
        })->values();

        // Pencarian jika ada
        if ($search) {
            $searchLower = strtolower(trim($search));
            $isBkSearch = in_array($searchLower, ['bk', 'guru bk', 'pelajaran bk', 'mapel bk', 'bimbingan konseling']);
            $grouped = $grouped->filter(function ($item) use ($searchLower, $isBkSearch) {
                $subLower = strtolower($item->subject);
                $matchSubject = str_contains($subLower, $searchLower)
                    || ($isBkSearch && (str_contains($subLower, 'bimbingan') || str_contains($subLower, 'konseling')));
                $matchTeacher = $item->teachers->contains(function ($t) use ($searchLower) {
                    return str_contains(strtolower($t->fullname), $searchLower);
                });
                $matchClass = $item->classes->contains(function ($c) use ($searchLower) {
                    return str_contains(strtolower($c->class_name), $searchLower);
                });
                return $matchSubject || $matchTeacher || $matchClass;
            })->values();
        }

        // Urutkan: Normada (normatif/adaptif) terlebih dahulu, kemudian Kejuruan RPL, masing-masing alfabetis
        $subjects = $grouped->sort(function ($a, $b) {
            $isKejuruanA = str_contains(strtoupper($a->subject), 'KK') || str_contains(strtoupper($a->subject), 'RPL');
            $isKejuruanB = str_contains(strtoupper($b->subject), 'KK') || str_contains(strtoupper($b->subject), 'RPL');

            if ($isKejuruanA === $isKejuruanB) {
                return strcasecmp($a->subject, $b->subject);
            }

            return $isKejuruanA ? 1 : -1;
        })->values();

        // Daftar seluruh guru untuk dropdown filter (Guru Mapel, BK, Walas, Waka)
        $teachers = User::whereIn('role_id', [1, 2, 5, 6])->orderBy('fullname')->get();

        $totalSubjects = $subjects->count();
        $totalTeachers = $subjects->pluck('teachers')->flatten()->unique('id')->count();
        $totalSessions = $subjects->sum('total_sessions');

        return view('waka.subjects.index', compact(
            'subjects',
            'teachers',
            'totalSubjects',
            'totalTeachers',
            'totalSessions',
            'search',
            'teacherFilter'
        ));
    }

    /**
     * Memperbarui nama mata pelajaran di seluruh jadwal terkait.
     */
    public function update(Request $request)
    {
        $request->validate([
            'old_subject' => 'required|string',
            'new_subject' => 'required|string|max:100',
        ]);

        Schedule::where('subject', $request->old_subject)
            ->update(['subject' => $request->new_subject]);

        return redirect()->route('waka.subjects.index')
            ->with('success', "Mata pelajaran '{$request->old_subject}' berhasil diperbarui menjadi '{$request->new_subject}'.");
    }
}

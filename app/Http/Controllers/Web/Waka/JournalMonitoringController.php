<?php

namespace App\Http\Controllers\Web\Waka;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Journal;
use App\Models\User;
use App\Models\Classes;

class JournalMonitoringController extends Controller
{
    public function index(Request $request)
    {
        $teachers = User::whereIn('role_id', [1, 2, 5, 6])->orderBy('fullname')->get();
        $classes  = Classes::orderBy('class_name')->get();
        $grades   = Classes::GRADE_LEVELS;
        $majors   = Classes::MAJORS;
        sort($majors);
        $sections = Classes::SECTIONS;

        $query = Journal::with(['schedule.teacher', 'schedule.schoolClass', 'schedule.room', 'attendances']);

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
                        ->orWhere('class_name', 'LIKE', $request->grade_level . ' %');
                });
            });
        }
        if ($request->filled('major')) {
            $query->whereHas('schedule.schoolClass', function ($q) use ($request) {
                $q->where(function ($sub) use ($request) {
                    $sub->where('major', strtoupper($request->major))
                        ->orWhere('class_name', 'LIKE', '% ' . $request->major . ' %');
                });
            });
        }
        if ($request->filled('section')) {
            $query->whereHas('schedule.schoolClass', function ($q) use ($request) {
                $q->where(function ($sub) use ($request) {
                    $sub->where('section', $request->section)
                        ->orWhere('class_name', 'LIKE', '% ' . $request->section);
                });
            });
        }

        $journals = $query->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('waka.journals.index', compact('journals', 'teachers', 'classes', 'grades', 'majors', 'sections'));
    }
}

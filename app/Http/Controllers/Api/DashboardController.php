<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Classes;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\Journal;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\SystemConfiguration;
use Carbon\Carbon;

/**
 * DashboardController (API)
 *
 * Endpoint serbaguna untuk Home Screen SCP Mobile.
 * Mengembalikan ringkasan data yang disesuaikan secara dinamis
 * sesuai role pengguna yang sedang login.
 */
class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user()->load(['role', 'schoolClass']);

        // Konfigurasi Sistem
        $config = SystemConfiguration::first();
        if (!$config) {
            $config = SystemConfiguration::create([
                'active_cycle' => 'Putaran 1',
                'is_schedule_locked' => false,
                'updated_by' => $user->id,
            ]);
        }

        $activeCycle = $config->active_cycle ?? 'Putaran 1';

        // Mapping hari dalam bahasa Indonesia
        $dayMap = [
            1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu',
            4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'
        ];
        $now = Carbon::now('Asia/Jakarta');
        $todayIndo = $dayMap[(int) $now->format('N')] ?? 'Senin';
        $todayDate = $now->format('Y-m-d');
        $currentTime = $now->format('H:i:s');
        $timeThreshold = $now->copy()->subMinutes(30)->format('H:i:s');

        $response = [
            'success'      => true,
            'role'         => $user->role->role_name ?? 'User',
            'role_id'      => $user->role_id,
            'today_day'    => $todayIndo,
            'today_date'   => $todayDate,
            'current_time' => substr($currentTime, 0, 5),
            'active_cycle' => $activeCycle,
            'is_schedule_locked' => (bool) $config->is_schedule_locked,
        ];

        // -------------------------------------------------------------
        // ROLE 1: ADMIN / WAKA KURIKULUM
        // -------------------------------------------------------------
        if ($user->role_id == 1) {
            $totalTeachers = User::whereIn('role_id', [Role::GURU, Role::BK, Role::WALI_KELAS])->count();
            $totalStudents = User::where('role_id', Role::SISWA)->count();
            $totalClasses  = Classes::count();
            $totalRooms    = Room::count();

            $todaySchedules = Schedule::with(['teacher:id,fullname', 'schoolClass:id,class_name', 'room:id,room_name'])
                ->where('day_of_week', $todayIndo)
                ->where(function ($q) use ($activeCycle) {
                    $q->where('cycle_type', $activeCycle)->orWhere('cycle_type', 'semua');
                })
                ->orderBy('start_time', 'asc')
                ->get();

            $filledCount = 0;
            $overdueList = [];

            foreach ($todaySchedules as $sch) {
                $hasJournal = Journal::where('schedule_id', $sch->id)
                    ->whereDate('date', $todayDate)
                    ->exists();

                if ($hasJournal) {
                    $filledCount++;
                    $sch->is_filled = true;
                    $sch->status_text = 'Selesai';
                } else {
                    $sch->is_filled = false;
                    if ($currentTime < $sch->start_time) {
                        $sch->status_text = 'Menunggu';
                    } elseif ($currentTime >= $sch->start_time && $currentTime <= $sch->end_time) {
                        $sch->status_text = 'Berlangsung';
                    } else {
                        $sch->status_text = 'Belum Diisi';
                    }

                    if ($sch->end_time <= $timeThreshold) {
                        $endTimeCarbon = Carbon::createFromTimeString($sch->end_time, 'Asia/Jakarta');
                        $overdueList[] = [
                            'schedule_id'  => $sch->id,
                            'teacher_name' => $sch->teacher->fullname ?? 'Guru',
                            'subject'      => $sch->subject,
                            'class_name'   => $sch->schoolClass->class_name ?? '-',
                            'room_name'    => $sch->room->room_name ?? '-',
                            'time'         => substr($sch->start_time, 0, 5) . ' - ' . substr($sch->end_time, 0, 5),
                            'late_minutes' => (int) $endTimeCarbon->diffInMinutes($now),
                        ];
                    }
                }
            }

            $response['summary'] = [
                'total_users'          => User::count(),
                'total_teachers'       => $totalTeachers,
                'total_students'       => $totalStudents,
                'total_classes'        => $totalClasses,
                'total_rooms'          => $totalRooms,
                'schedules_today_count'=> $todaySchedules->count(),
                'journals_filled_count'=> $filledCount,
                'journals_unfilled_count' => $todaySchedules->count() - $filledCount,
                'overdue_count'        => count($overdueList),
            ];
            $response['overdue_journals'] = $overdueList;
            $response['today_schedules']  = $todaySchedules;

            return response()->json($response, 200);
        }

        // -------------------------------------------------------------
        // ROLE 2: GURU
        // -------------------------------------------------------------
        if ($user->role_id == 2) {
            $todaySchedules = Schedule::with(['schoolClass:id,class_name', 'room:id,room_name'])
                ->where('teacher_id', $user->id)
                ->where('day_of_week', $todayIndo)
                ->where(function ($q) use ($activeCycle) {
                    $q->where('cycle_type', $activeCycle)->orWhere('cycle_type', 'semua');
                })
                ->orderBy('start_time', 'asc')
                ->get();

            $filledCount = 0;
            $overdueList = [];

            $todaySchedulesFormatted = $todaySchedules->map(function ($sch) use ($todayDate, $currentTime, $timeThreshold, $now, &$filledCount, &$overdueList) {
                $journal = Journal::where('schedule_id', $sch->id)
                    ->whereDate('date', $todayDate)
                    ->first();

                $hasJournal = !is_null($journal);
                $statusText = 'Belum Diisi';

                if ($hasJournal) {
                    $filledCount++;
                    $statusText = 'Selesai';
                } else {
                    if ($currentTime < $sch->start_time) {
                        $statusText = 'Menunggu';
                    } elseif ($currentTime >= $sch->start_time && $currentTime <= $sch->end_time) {
                        $statusText = 'Berlangsung';
                    } else {
                        $statusText = 'Belum Diisi';
                    }

                    if ($sch->end_time <= $timeThreshold) {
                        $endTimeCarbon = Carbon::createFromTimeString($sch->end_time, 'Asia/Jakarta');
                        $overdueList[] = [
                            'schedule_id'  => $sch->id,
                            'subject'      => $sch->subject,
                            'class_name'   => $sch->schoolClass->class_name ?? '-',
                            'room_name'    => $sch->room->room_name ?? '-',
                            'time'         => substr($sch->start_time, 0, 5) . ' - ' . substr($sch->end_time, 0, 5),
                            'late_minutes' => (int) $endTimeCarbon->diffInMinutes($now),
                        ];
                    }
                }

                return [
                    'id'                => $sch->id,
                    'subject'           => $sch->subject,
                    'day_of_week'       => $sch->day_of_week,
                    'start_time'        => substr($sch->start_time, 0, 5),
                    'end_time'          => substr($sch->end_time, 0, 5),
                    'time_formatted'    => substr($sch->start_time, 0, 5) . ' - ' . substr($sch->end_time, 0, 5),
                    'cycle_type'        => $sch->cycle_type,
                    'class'             => $sch->schoolClass,
                    'room'              => $sch->room,
                    'has_journal_today' => $hasJournal,
                    'journal_id'        => $hasJournal ? $journal->id : null,
                    'status_text'       => $statusText,
                ];
            });

            $response['summary'] = [
                'schedules_today_count' => $todaySchedules->count(),
                'journals_filled_count' => $filledCount,
                'journals_unfilled_count' => $todaySchedules->count() - $filledCount,
                'overdue_count'         => count($overdueList),
            ];
            $response['overdue_journals'] = $overdueList;
            $response['today_schedules']  = $todaySchedulesFormatted;

            return response()->json($response, 200);
        }

        // -------------------------------------------------------------
        // ROLE 3: SISWA
        // -------------------------------------------------------------
        if ($user->role_id == 3) {
            $classId = $user->class_id;

            $todaySchedules = [];
            if ($classId) {
                $todaySchedules = Schedule::with(['teacher:id,fullname', 'room:id,room_name'])
                    ->where('class_id', $classId)
                    ->where('day_of_week', $todayIndo)
                    ->where(function ($q) use ($activeCycle) {
                        $q->where('cycle_type', $activeCycle)->orWhere('cycle_type', 'semua');
                    })
                    ->orderBy('start_time', 'asc')
                    ->get()
                    ->map(function ($sch) use ($todayDate) {
                        $journal = Journal::where('schedule_id', $sch->id)
                            ->whereDate('date', $todayDate)
                            ->first();

                        return [
                            'id'             => $sch->id,
                            'subject'        => $sch->subject,
                            'start_time'     => substr($sch->start_time, 0, 5),
                            'end_time'       => substr($sch->end_time, 0, 5),
                            'time_formatted' => substr($sch->start_time, 0, 5) . ' - ' . substr($sch->end_time, 0, 5),
                            'teacher'        => $sch->teacher,
                            'room'           => $sch->room,
                            'has_journal'    => !is_null($journal),
                        ];
                    });
            }

            // Kehadiran Siswa
            $attendances = Attendance::where('student_id', $user->id)->get();
            $todayAttendance = Attendance::where('student_id', $user->id)
                ->whereDate('date', $todayDate)
                ->latest('id')
                ->first();

            // Pengajuan Izin Terakhir
            $latestLeave = LeaveRequest::where('student_id', $user->id)
                ->latest('id')
                ->first();

            $response['class_name'] = $user->schoolClass->class_name ?? '-';
            $response['attendance_summary'] = [
                'total_recorded' => $attendances->count(),
                'hadir'          => $attendances->where('status', 'hadir')->count(),
                'izin'           => $attendances->where('status', 'izin')->count(),
                'sakit'          => $attendances->where('status', 'sakit')->count(),
                'alpa'           => $attendances->where('status', 'alpa')->count(),
                'today_status'   => $todayAttendance ? $todayAttendance->status : 'Belum Ada Sesi',
            ];
            $response['latest_leave_request'] = $latestLeave;
            $response['today_schedules']      = $todaySchedules;

            return response()->json($response, 200);
        }

        // -------------------------------------------------------------
        // ROLE 4: ORANG TUA
        // -------------------------------------------------------------
        if ($user->role_id == 4) {
            $child = $user->children()->with('schoolClass')->first();

            if (!$child) {
                $response['child'] = null;
                $response['message'] = 'Belum ada data siswa/anak yang terhubung ke akun ini.';
                return response()->json($response, 200);
            }

            $attendances = Attendance::where('student_id', $child->id)->get();
            $todayAttendance = Attendance::where('student_id', $child->id)
                ->whereDate('date', $todayDate)
                ->latest('id')
                ->first();

            $todaySchedules = [];
            if ($child->class_id) {
                $todaySchedules = Schedule::with(['teacher:id,fullname', 'room:id,room_name'])
                    ->where('class_id', $child->class_id)
                    ->where('day_of_week', $todayIndo)
                    ->where(function ($q) use ($activeCycle) {
                        $q->where('cycle_type', $activeCycle)->orWhere('cycle_type', 'semua');
                    })
                    ->orderBy('start_time', 'asc')
                    ->get();
            }

            $latestLeave = LeaveRequest::where('student_id', $child->id)
                ->latest('id')
                ->first();

            $response['child'] = [
                'id'         => $child->id,
                'fullname'   => $child->fullname,
                'username'   => $child->username,
                'class_name' => $child->schoolClass->class_name ?? '-',
            ];
            $response['attendance_summary'] = [
                'total_recorded' => $attendances->count(),
                'hadir'          => $attendances->where('status', 'hadir')->count(),
                'izin'           => $attendances->where('status', 'izin')->count(),
                'sakit'          => $attendances->where('status', 'sakit')->count(),
                'alpa'           => $attendances->where('status', 'alpa')->count(),
                'today_status'   => $todayAttendance ? $todayAttendance->status : 'Belum Ada Sesi',
            ];
            $response['latest_leave_request'] = $latestLeave;
            $response['today_schedules']      = $todaySchedules;

            return response()->json($response, 200);
        }

        // -------------------------------------------------------------
        // ROLE 5 (BK) & ROLE 6 (WALI KELAS)
        // -------------------------------------------------------------
        if (in_array($user->role_id, [5, 6])) {
            $pendingLeavesCount = LeaveRequest::where('status_approval', 'pending')->count();

            // Monitoring siswa butuh perhatian (alpa >= 2 atau sakit/izin >= 4)
            $studentsQuery = User::where('role_id', 3)->with('schoolClass');
            if ($user->role_id == 6 && $user->class_id) {
                $studentsQuery->where('class_id', $user->class_id);
            }
            $students = $studentsQuery->get();

            $problemCount = 0;
            foreach ($students as $student) {
                $atts = Attendance::where('student_id', $student->id)->get();
                $alpa = $atts->where('status', 'alpa')->count();
                $izinSakit = $atts->whereIn('status', ['izin', 'sakit'])->count();
                if ($alpa >= 2 || $izinSakit >= 4) {
                    $problemCount++;
                }
            }

            $response['summary'] = [
                'pending_leaves_count'     => $pendingLeavesCount,
                'students_need_counseling' => $problemCount,
                'total_students_monitored' => $students->count(),
                'class_name'               => $user->schoolClass->class_name ?? null,
            ];

            // Jika Walas (Role 6) atau BK (Role 5) memiliki jadwal mengajar/masuk kelas hari ini, sertakan today_schedules
            if (in_array($user->role_id, [5, 6])) {
                $todaySchedules = Schedule::with(['schoolClass:id,class_name', 'room:id,room_name'])
                    ->where('teacher_id', $user->id)
                    ->where('day_of_week', $todayIndo)
                    ->where(function ($q) use ($activeCycle) {
                        $q->where('cycle_type', $activeCycle)->orWhere('cycle_type', 'semua');
                    })
                    ->orderBy('start_time', 'asc')
                    ->get();

                $todaySchedulesFormatted = $todaySchedules->map(function ($sch) use ($todayDate, $currentTime) {
                    $journal = Journal::where('schedule_id', $sch->id)
                        ->whereDate('date', $todayDate)
                        ->first();

                    $hasJournal = !is_null($journal);
                    $statusText = 'Belum Diisi';

                    if ($hasJournal) {
                        $statusText = 'Selesai';
                    } else {
                        if ($currentTime < $sch->start_time) {
                            $statusText = 'Menunggu';
                        } elseif ($currentTime >= $sch->start_time && $currentTime <= $sch->end_time) {
                            $statusText = 'Berlangsung';
                        }
                    }

                    return [
                        'id'                => $sch->id,
                        'subject'           => $sch->subject,
                        'day_of_week'       => $sch->day_of_week,
                        'start_time'        => substr($sch->start_time, 0, 5),
                        'end_time'          => substr($sch->end_time, 0, 5),
                        'time_formatted'    => substr($sch->start_time, 0, 5) . ' - ' . substr($sch->end_time, 0, 5),
                        'cycle_type'        => $sch->cycle_type,
                        'class'             => $sch->schoolClass,
                        'room'              => $sch->room,
                        'has_journal_today' => $hasJournal,
                        'journal_id'        => $hasJournal ? $journal->id : null,
                        'status_text'       => $statusText,
                    ];
                });

                $response['today_schedules'] = $todaySchedulesFormatted;
                $response['summary']['schedules_today_count'] = $todaySchedules->count();
            }

            return response()->json($response, 200);
        }

        return response()->json($response, 200);
    }
}

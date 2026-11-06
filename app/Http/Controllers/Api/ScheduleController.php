<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Schedule;
use App\Models\SystemConfiguration;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $config = SystemConfiguration::first();
        $activeCycle = $config->active_cycle ?? 'Putaran 1';

        $query = Schedule::with(['teacher:id,fullname,username', 'schoolClass:id,class_name', 'room:id,room_name']);

        $user = $request->user();
        if (in_array($user->role_id, [2, 5, 6])) { // Guru, BK & Wali Kelas (Pengajar)
            $query->where('teacher_id', $user->id);
        } elseif (in_array($user->role_id, [3, 4])) { // Siswa / Ortu
            $classId = $user->class_id ?? ($user->children()->first()->class_id ?? null);
            if ($classId) {
                $query->where('class_id', $classId);
            }
        }

        if ($request->filled('day')) {
            $query->where('day_of_week', $request->day);
        }
        if ($request->filled('cycle')) {
            $query->where(function ($q) use ($request) {
                $q->where('cycle_type', $request->cycle)
                  ->orWhere('cycle_type', 'semua');
            });
        } else {
            $query->where(function ($q) use ($activeCycle) {
                $q->where('cycle_type', $activeCycle)
                  ->orWhere('cycle_type', 'semua');
            });
        }

        $schedules = $query->orderBy('class_id', 'asc')
            ->orderByRaw("FIELD(cycle_type, 'Putaran 1', 'Putaran 2', 'semua') ASC")
            ->orderByRaw("FIELD(day_of_week, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat')")
            ->orderBy('start_time', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'active_cycle' => $activeCycle,
            'total' => $schedules->count(),
            'data' => $schedules
        ], 200);
    }

    /**
     * Jadwal Khusus Hari Ini Mengikuti Siklus Aktif
     */
    public function today(Request $request)
    {
        $config = SystemConfiguration::first();
        $activeCycle = $config->active_cycle ?? 'Putaran 1';

        $daysMap = [
            'Sunday' => 'Minggu',
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
        ];
        $todayIndo = $daysMap[date('l')] ?? 'Senin';

        $query = Schedule::with(['teacher:id,fullname', 'schoolClass:id,class_name', 'room:id,room_name'])
            ->where('day_of_week', $todayIndo)
            ->where(function ($q) use ($activeCycle) {
                $q->where('cycle_type', $activeCycle)
                  ->orWhere('cycle_type', 'semua');
            });

        $user = $request->user();
        if (in_array($user->role_id, [2, 5, 6])) { // Guru, BK & Wali Kelas (Pengajar)
            $query->where('teacher_id', $user->id);
        } elseif (in_array($user->role_id, [3, 4])) { // Siswa/Ortu
            $classId = $user->class_id ?? ($user->children()->first()->class_id ?? null);
            if ($classId) {
                $query->where('class_id', $classId);
            }
        }

        $todayDate = date('Y-m-d');
        $currentTime = date('H:i:s');

        $schedules = $query->orderBy('start_time', 'asc')->get()->map(function ($sch) use ($todayDate, $currentTime) {
            $journal = \App\Models\Journal::where('schedule_id', $sch->id)
                ->whereDate('date', $todayDate)
                ->first();

            $hasJournal = !is_null($journal);
            $statusText = 'Belum Diisi';
            if ($hasJournal) {
                $statusText = 'Selesai';
            } elseif ($currentTime < $sch->start_time) {
                $statusText = 'Menunggu';
            } elseif ($currentTime >= $sch->start_time && $currentTime <= $sch->end_time) {
                $statusText = 'Berlangsung';
            }

            $sch->has_journal_today = $hasJournal;
            $sch->journal_id        = $hasJournal ? $journal->id : null;
            $sch->status_text       = $statusText;

            return $sch;
        });

        return response()->json([
            'success'      => true,
            'today_day'    => $todayIndo,
            'today_date'   => $todayDate,
            'active_cycle' => $activeCycle,
            'data'         => $schedules
        ], 200);
    }

    /**
     * Peringatan Jurnal Terlambat > 30 Menit (Kunci Kelulusan UKK)
     */
    public function overdueReminders(Request $request)
    {
        $config = SystemConfiguration::first();
        $activeCycle = $config->active_cycle ?? 'Putaran 1';

        $daysMap = [
            'Sunday' => 'Minggu',
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
        ];
        $todayIndo = $daysMap[date('l')] ?? 'Senin';
        $todayDate = date('Y-m-d');
        $currentTime = date('H:i:s');

        $query = Schedule::with(['teacher:id,fullname', 'schoolClass:id,class_name', 'room:id,room_name'])
            ->where('day_of_week', $todayIndo)
            ->where(function ($q) use ($activeCycle) {
                $q->where('cycle_type', $activeCycle)
                  ->orWhere('cycle_type', 'semua');
            });

        $user = $request->user();
        if (in_array($user->role_id, [2, 5, 6])) { // Guru, BK & Wali Kelas
            $query->where('teacher_id', $user->id);
        }

        $todaySchedules = $query->get();
        $overdue = [];

        foreach ($todaySchedules as $sched) {
            $hasJournal = \App\Models\Journal::where('schedule_id', $sched->id)
                ->whereDate('date', $todayDate)
                ->exists();

            if (!$hasJournal) {
                $endTimeStr = $sched->end_time;
                $endTimeTimestamp = strtotime($todayDate . ' ' . $endTimeStr);
                $nowTimestamp = strtotime($todayDate . ' ' . $currentTime);

                // Lebih dari 30 menit (1800 detik) setelah jam selesai
                if ($nowTimestamp > ($endTimeTimestamp + 1800)) {
                    $minutesLate = round(($nowTimestamp - $endTimeTimestamp) / 60);
                    $overdue[] = [
                        'schedule_id' => $sched->id,
                        'subject' => $sched->subject,
                        'class_name' => $sched->schoolClass->class_name ?? '-',
                        'room_name' => $sched->room->room_name ?? '-',
                        'teacher_name' => $sched->teacher->fullname ?? '-',
                        'time' => $sched->start_time . ' - ' . $sched->end_time,
                        'minutes_overdue' => $minutesLate,
                        'message' => "Jadwal '{$sched->subject}' di kelas {$sched->schoolClass->class_name} telah selesai sejak {$minutesLate} menit yang lalu namun belum mengisi jurnal."
                    ];
                }
            }
        }

        return response()->json([
            'success' => true,
            'total_overdue' => count($overdue),
            'has_overdue' => count($overdue) > 0,
            'data' => $overdue
        ], 200);
    }

    public function show($id)
    {
        $schedule = Schedule::with(['teacher:id,fullname,username', 'schoolClass:id,class_name', 'room:id,room_name'])->findOrFail($id);
        return response()->json([
            'success' => true,
            'data' => $schedule
        ], 200);
    }

    public function store(Request $request)
    {
        $config = SystemConfiguration::first();
        if ($config && $config->is_schedule_locked) {
            return response()->json([
                'success' => false,
                'message' => 'Pengisian jadwal pelajaran sedang dikunci karena sudah fix/final.'
            ], 403);
        }

        $validated = $request->validate([
            'teacher_id' => 'required|exists:users,id',
            'class_id' => 'required|exists:classes,id',
            'room_id' => 'required|exists:rooms,id',
            'subject' => 'required|string|max:100',
            'day_of_week' => 'required|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'cycle_type' => 'required|in:semua,Putaran 1,Putaran 2',
        ]);

        $conflict = $this->checkScheduleConflict(
            $validated['day_of_week'],
            $validated['start_time'],
            $validated['end_time'],
            $validated['cycle_type'],
            $validated['room_id'],
            $validated['class_id'],
            $validated['teacher_id']
        );

        if ($conflict) {
            return response()->json([
                'success' => false,
                'message' => $conflict
            ], 422);
        }

        $schedule = Schedule::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Jadwal pelajaran berhasil ditambahkan',
            'data' => $schedule
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $config = SystemConfiguration::first();
        if ($config && $config->is_schedule_locked) {
            return response()->json([
                'success' => false,
                'message' => 'Perubahan jadwal pelajaran sedang dikunci karena sudah fix/final.'
            ], 403);
        }

        $schedule = Schedule::findOrFail($id);

        $validated = $request->validate([
            'teacher_id' => 'required|exists:users,id',
            'class_id' => 'required|exists:classes,id',
            'room_id' => 'required|exists:rooms,id',
            'subject' => 'required|string|max:100',
            'day_of_week' => 'required|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'cycle_type' => 'required|in:semua,Putaran 1,Putaran 2',
        ]);

        $conflict = $this->checkScheduleConflict(
            $validated['day_of_week'],
            $validated['start_time'],
            $validated['end_time'],
            $validated['cycle_type'],
            $validated['room_id'],
            $validated['class_id'],
            $validated['teacher_id'],
            $schedule->id
        );

        if ($conflict) {
            return response()->json([
                'success' => false,
                'message' => $conflict
            ], 422);
        }

        $schedule->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Jadwal pelajaran berhasil diperbarui',
            'data' => $schedule
        ], 200);
    }

    public function destroy($id)
    {
        $config = SystemConfiguration::first();
        if ($config && $config->is_schedule_locked) {
            return response()->json([
                'success' => false,
                'message' => 'Penghapusan jadwal pelajaran sedang dikunci karena sudah fix/final.'
            ], 403);
        }

        $schedule = Schedule::findOrFail($id);
        $schedule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Jadwal pelajaran berhasil dihapus'
        ], 200);
    }

    /**
     * Cek bentrok jadwal: Ruangan, Kelas, dan Guru
     * Rumus Irisan Waktu: start_A < end_B AND end_A > start_B
     */
    private function checkScheduleConflict($day, $startTime, $endTime, $cycleType, $roomId, $classId, $teacherId, $ignoreId = null): ?string
    {
        // 1. Bentrok Ruangan
        $roomConflict = $this->buildConflictQuery($day, $startTime, $endTime, $cycleType, $ignoreId)
            ->where('room_id', $roomId)
            ->with(['room', 'schoolClass', 'teacher'])
            ->first();

        if ($roomConflict) {
            return "Bentrok Ruangan: {$roomConflict->room->room_name} sudah digunakan oleh kelas {$roomConflict->schoolClass->class_name} ({$roomConflict->teacher->fullname}) pada {$roomConflict->start_time} - {$roomConflict->end_time}.";
        }

        // 2. Bentrok Kelas
        $classConflict = $this->buildConflictQuery($day, $startTime, $endTime, $cycleType, $ignoreId)
            ->where('class_id', $classId)
            ->with(['schoolClass', 'teacher'])
            ->first();

        if ($classConflict) {
            return "Bentrok Kelas: Kelas {$classConflict->schoolClass->class_name} sudah memiliki jadwal mapel '{$classConflict->subject}' oleh {$classConflict->teacher->fullname} pada jam tersebut.";
        }

        // 3. Bentrok Guru
        $teacherConflict = $this->buildConflictQuery($day, $startTime, $endTime, $cycleType, $ignoreId)
            ->where('teacher_id', $teacherId)
            ->with(['teacher', 'schoolClass', 'room'])
            ->first();

        if ($teacherConflict) {
            return "Bentrok Guru: Guru {$teacherConflict->teacher->fullname} sudah mengajar di kelas {$teacherConflict->schoolClass->class_name} ({$teacherConflict->room->room_name}) pada jam tersebut.";
        }

        return null; // Tidak ada bentrok
    }

    /**
     * Membangun query dasar untuk pengecekan bentrok jadwal.
     * Setiap pemanggilan membuat instance Query Builder baru (tidak ada shared state).
     */
    private function buildConflictQuery($day, $startTime, $endTime, $cycleType, $ignoreId = null)
    {
        $query = Schedule::where('day_of_week', $day)
            ->where(function ($cycleQ) use ($cycleType) {
                if ($cycleType === 'semua') {
                    // 'semua' bertabrakan dengan semua siklus yang ada
                    $cycleQ->whereIn('cycle_type', ['semua', 'Putaran 1', 'Putaran 2']);
                } else {
                    $cycleQ->where('cycle_type', $cycleType)
                           ->orWhere('cycle_type', 'semua');
                }
            })
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query;
    }
}

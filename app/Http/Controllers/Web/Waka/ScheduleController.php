<?php

namespace App\Http\Controllers\Web\Waka;

use App\Http\Controllers\Controller;
use App\Models\Classes;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\SystemConfiguration;
use App\Models\User;
use App\Services\ScheduleImportService;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $config = SystemConfiguration::first();
        $teachers = User::whereIn('role_id', [1, 2, 5, 6])->orderBy('fullname')->get();
        $classes = Classes::orderBy('class_name')->get();
        $rooms = Room::orderBy('room_name')->get();

        $query = Schedule::with(['teacher', 'schoolClass', 'room']);

        $todayActive = false;
        if ($request->boolean('today') || $request->input('today') === '1') {
            $todayActive = true;
            $dayMap = [
                'Monday' => 'Senin',
                'Tuesday' => 'Selasa',
                'Wednesday' => 'Rabu',
                'Thursday' => 'Kamis',
                'Friday' => 'Jumat',
                'Saturday' => 'Sabtu',
                'Sunday' => 'Minggu',
            ];
            $currentDayEn = date('l');
            $currentDayId = $dayMap[$currentDayEn] ?? 'Senin';
            $query->where('day_of_week', $currentDayId);

            $activeCycle = $config->active_cycle ?? 'Putaran 1';
            $query->where(function ($q) use ($activeCycle) {
                $q->where('cycle_type', $activeCycle)
                    ->orWhere('cycle_type', 'semua');
            });
        }

        if ($request->filled('day') && ! $todayActive) {
            $query->where('day_of_week', $request->day);
        }
        if ($request->filled('cycle') && ! $todayActive) {
            $query->where('cycle_type', $request->cycle);
        }
        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }
        if ($request->filled('grade_level')) {
            $query->whereHas('schoolClass', function ($q) use ($request) {
                $q->where(function ($sub) use ($request) {
                    $sub->where('grade_level', $request->grade_level)
                        ->orWhere('class_name', 'LIKE', $request->grade_level.' %');
                });
            });
        }
        if ($request->filled('major')) {
            $query->whereHas('schoolClass', function ($q) use ($request) {
                $q->where(function ($sub) use ($request) {
                    $sub->where('major', strtoupper($request->major))
                        ->orWhere('class_name', 'LIKE', '% '.$request->major.' %');
                });
            });
        }
        if ($request->filled('section')) {
            $query->whereHas('schoolClass', function ($q) use ($request) {
                $q->where(function ($sub) use ($request) {
                    $sub->where('section', $request->section)
                        ->orWhere('class_name', 'LIKE', '% '.$request->section);
                });
            });
        }
        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }
        if ($request->filled('room_id')) {
            $query->where('room_id', $request->room_id);
        }

        $schedules = $query->orderBy('class_id', 'asc')
            ->orderByRaw("FIELD(cycle_type, 'Putaran 1', 'Putaran 2', 'semua') ASC")
            ->orderByRaw("FIELD(day_of_week, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu') ASC")
            ->orderBy('start_time', 'asc')
            ->paginate(100)
            ->withQueryString();

        $gradeLevels = Classes::GRADE_LEVELS;
        $majors = Classes::MAJORS;
        $sections = Classes::SECTIONS;

        return view('waka.schedules.index', compact(
            'schedules',
            'config',
            'teachers',
            'classes',
            'rooms',
            'gradeLevels',
            'majors',
            'sections',
            'todayActive'
        ));
    }

    public function create()
    {
        $config = SystemConfiguration::first();
        if ($config && $config->is_schedule_locked) {
            return redirect()->route('waka.schedules.index')
                ->with('error', 'Akses ditolak: Pengisian jadwal pelajaran saat ini sedang DIKUNCI karena jadwal sudah fix/final. Silakan buka kunci di Dashboard jika ingin mengubah atau menambah jadwal.');
        }

        $teachers = User::whereIn('role_id', [1, 2, 5, 6])->orderBy('fullname')->get();
        $classes = Classes::orderBy('class_name')->get();
        $rooms = Room::orderBy('room_name')->get();
        $startTimes = Schedule::START_TIMES;
        $endTimes = Schedule::END_TIMES;
        $dailyPeriods = Schedule::DAILY_PERIODS;

        return view('waka.schedules.create', compact('teachers', 'classes', 'rooms', 'startTimes', 'endTimes', 'dailyPeriods'));
    }

    public function store(Request $request)
    {
        $config = SystemConfiguration::first();
        if ($config && $config->is_schedule_locked) {
            return redirect()->route('waka.schedules.index')
                ->with('error', 'Gagal: Jadwal pelajaran sedang DIKUNCI dan tidak dapat ditambahkan.');
        }

        // Jika input jam ke- (period) dipilih, otomatis ambil start_time dan end_time
        if ($request->filled('start_period') && $request->filled('end_period') && $request->filled('day_of_week')) {
            $day = $request->day_of_week;
            $sp = (int) $request->start_period;
            $ep = (int) $request->end_period;
            $periods = Schedule::DAILY_PERIODS[$day] ?? [];
            if (isset($periods[$sp]) && isset($periods[$ep])) {
                $request->merge([
                    'start_time' => $periods[$sp]['start'],
                    'end_time' => $periods[$ep]['end'],
                ]);
            }
        }

        $validated = $request->validate([
            'teacher_id' => 'required|exists:users,id',
            'class_id' => 'required|exists:classes,id',
            'room_id' => 'required|exists:rooms,id',
            'subject' => 'required|string|max:100',
            'day_of_week' => 'required|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'start_period' => 'nullable|integer|min:1|max:12',
            'end_period' => 'nullable|integer|min:1|max:12',
            'cycle_type' => 'required|in:semua,Putaran 1,Putaran 2',
        ]);

        // Cek Bentrok Jadwal (Conflict Prevention)
        $conflictError = $this->checkScheduleConflict(
            $validated['teacher_id'],
            $validated['class_id'],
            $validated['room_id'],
            $validated['day_of_week'],
            $validated['start_time'],
            $validated['end_time'],
            $validated['cycle_type']
        );

        if ($conflictError) {
            return back()->withInput()->with('error', $conflictError)->withErrors(['conflict' => $conflictError]);
        }

        Schedule::create($validated);

        return redirect()->route('waka.schedules.index')
            ->with('success', 'Jadwal pelajaran berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $config = SystemConfiguration::first();
        if ($config && $config->is_schedule_locked) {
            return redirect()->route('waka.schedules.index')
                ->with('error', 'Akses ditolak: Jadwal pelajaran saat ini sedang DIKUNCI dan tidak dapat diubah.');
        }

        $schedule = Schedule::findOrFail($id);
        $teachers = User::whereIn('role_id', [1, 2, 5, 6])->orderBy('fullname')->get();
        $classes = Classes::orderBy('class_name')->get();
        $rooms = Room::orderBy('room_name')->get();
        $startTimes = Schedule::START_TIMES;
        $endTimes = Schedule::END_TIMES;
        $dailyPeriods = Schedule::DAILY_PERIODS;

        return view('waka.schedules.edit', compact('schedule', 'teachers', 'classes', 'rooms', 'startTimes', 'endTimes', 'dailyPeriods'));
    }

    public function update(Request $request, $id)
    {
        $config = SystemConfiguration::first();
        if ($config && $config->is_schedule_locked) {
            return redirect()->route('waka.schedules.index')
                ->with('error', 'Gagal: Jadwal pelajaran sedang DIKUNCI dan tidak dapat diubah.');
        }

        $schedule = Schedule::findOrFail($id);

        if ($request->filled('start_period') && $request->filled('end_period') && $request->filled('day_of_week')) {
            $day = $request->day_of_week;
            $sp = (int) $request->start_period;
            $ep = (int) $request->end_period;
            $periods = Schedule::DAILY_PERIODS[$day] ?? [];
            if (isset($periods[$sp]) && isset($periods[$ep])) {
                $request->merge([
                    'start_time' => $periods[$sp]['start'],
                    'end_time' => $periods[$ep]['end'],
                ]);
            }
        }

        $validated = $request->validate([
            'teacher_id' => 'required|exists:users,id',
            'class_id' => 'required|exists:classes,id',
            'room_id' => 'required|exists:rooms,id',
            'subject' => 'required|string|max:100',
            'day_of_week' => 'required|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'start_period' => 'nullable|integer|min:1|max:12',
            'end_period' => 'nullable|integer|min:1|max:12',
            'cycle_type' => 'required|in:semua,Putaran 1,Putaran 2',
        ]);

        // Cek Bentrok Jadwal (Conflict Prevention) kecuali jadwal ini sendiri
        $conflictError = $this->checkScheduleConflict(
            $validated['teacher_id'],
            $validated['class_id'],
            $validated['room_id'],
            $validated['day_of_week'],
            $validated['start_time'],
            $validated['end_time'],
            $validated['cycle_type'],
            $schedule->id
        );

        if ($conflictError) {
            return back()->withInput()->with('error', $conflictError)->withErrors(['conflict' => $conflictError]);
        }

        $schedule->update($validated);

        return redirect()->route('waka.schedules.index')
            ->with('success', 'Jadwal pelajaran berhasil diperbarui.');
    }

    /**
     * Mengunduh file template Excel (.xlsx) untuk impor massal jadwal pelajaran.
     */
    public function downloadTemplate(ScheduleImportService $importService)
    {
        $spreadsheet = $importService->generateTemplateSpreadsheet();
        $fileName = 'template_import_jadwal.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Memproses impor massal data jadwal pelajaran dari file Excel, Word, atau PDF.
     */
    public function import(Request $request, ScheduleImportService $importService)
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');

        $config = SystemConfiguration::first();
        if ($config && $config->is_schedule_locked) {
            return redirect()->route('waka.schedules.index')
                ->with('error', 'Akses ditolak: Jadwal pelajaran sedang DIKUNCI dan tidak dapat diimpor.');
        }

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'cycle_type' => 'nullable|string|in:Putaran 1,Putaran 2,semua,auto',
        ], [
            'file.required' => 'Silakan pilih file jadwal yang ingin diimpor.',
            'file.mimes' => 'Format file tidak didukung! Harap unggah file template resmi Excel (.xlsx, .xls) atau CSV (.csv). File Word (.docx) atau PDF tidak diperbolehkan.',
            'file.max' => 'Ukuran file melebihi batas maksimum (10 MB).',
        ]);

        try {
            $cycleOverride = ($request->cycle_type && $request->cycle_type !== 'auto') ? $request->cycle_type : null;
            $result = $importService->import($request->file('file'), $cycleOverride);

            $message = "Proses impor jadwal selesai! Total baris diproses: {$result['total_rows']}. Jadwal berhasil ditambahkan: {$result['created']}.";

            if ($result['skipped'] > 0) {
                $message .= " Dilewati: {$result['skipped']}.";
            }

            if (! empty($result['errors'])) {
                return redirect()->route('waka.schedules.index')
                    ->with('warning', $message)
                    ->with('import_errors', $result['errors']);
            }

            return redirect()->route('waka.schedules.index')->with('success', $message);
        } catch (\Throwable $e) {
            return redirect()->route('waka.schedules.index')->with('error', 'Gagal memproses file impor: '.$e->getMessage());
        }
    }

    public function destroy($id)
    {
        $config = SystemConfiguration::first();
        if ($config && $config->is_schedule_locked) {
            return redirect()->route('waka.schedules.index')
                ->with('error', 'Gagal: Jadwal pelajaran sedang DIKUNCI dan tidak dapat dihapus.');
        }

        $schedule = Schedule::findOrFail($id);
        $schedule->delete();

        return redirect()->route('waka.schedules.index')
            ->with('success', 'Jadwal pelajaran berhasil dihapus.');
    }

    /**
     * Logika Validasi Tabrakan Waktu & Tempat (Conflict Prevention)
     * Rumus Irisan Waktu: Start_A < End_B AND End_A > Start_B
     */
    private function checkScheduleConflict($teacherId, $classId, $roomId, $day, $startTime, $endTime, $cycleType, $ignoreId = null)
    {
        $cleanStart = substr($startTime, 0, 5);
        $cleanEnd = substr($endTime, 0, 5);

        // Helper irisan waktu & siklus:
        $baseConflictQuery = function ($query) use ($day, $cleanStart, $cleanEnd, $cycleType, $ignoreId) {
            $query->where('day_of_week', $day);
            if ($ignoreId) {
                $query->where('id', '!=', $ignoreId);
            }
            if ($cycleType !== 'semua') {
                $query->whereIn('cycle_type', [$cycleType, 'semua']);
            }
            $query->where('start_time', '<', $cleanEnd)
                ->where('end_time', '>', $cleanStart);
        };

        // 1. Bentrok Guru: Guru yang sama di jam & hari yang sama
        if ($teacherId) {
            $teacherConflict = Schedule::with(['teacher', 'schoolClass', 'room'])
                ->where('teacher_id', $teacherId)
                ->where($baseConflictQuery)
                ->first();

            if ($teacherConflict) {
                $tName = $teacherConflict->teacher->fullname ?? 'Guru bersangkutan';
                $cName = $teacherConflict->schoolClass->class_name ?? 'Kelas lain';
                $rName = $teacherConflict->room->room_name ?? 'Tanpa Ruang';
                $tRange = substr($teacherConflict->start_time, 0, 5).' - '.substr($teacherConflict->end_time, 0, 5);

                return "Bentrok Guru! {$tName} sudah memiliki jadwal mengajar di kelas {$cName} ({$teacherConflict->subject}, {$rName}) pada hari {$day} pukul {$tRange} ({$teacherConflict->cycle_type}).";
            }
        }

        // 2. Bentrok Ruangan: Ruangan yang sama dipakai kelas lain di jam yang sama (jika ruangan dipilih)
        if ($roomId) {
            $roomConflict = Schedule::with(['teacher', 'schoolClass', 'room'])
                ->where('room_id', $roomId)
                ->where($baseConflictQuery)
                ->first();

            if ($roomConflict) {
                $rName = $roomConflict->room->room_name ?? 'Ruang bersangkutan';
                $cName = $roomConflict->schoolClass->class_name ?? 'Kelas lain';
                $tName = $roomConflict->teacher->fullname ?? 'Guru lain';
                $tRange = substr($roomConflict->start_time, 0, 5).' - '.substr($roomConflict->end_time, 0, 5);

                return "Bentrok Ruangan! Ruang {$rName} sudah digunakan oleh kelas {$cName} dengan pengajar {$tName} pada hari {$day} pukul {$tRange} ({$roomConflict->cycle_type}).";
            }
        }

        // 3. Bentrok Kelas: Rombel yang sama diajar mapel lain di jam yang sama
        if ($classId) {
            $classConflict = Schedule::with(['teacher', 'schoolClass', 'room'])
                ->where('class_id', $classId)
                ->where($baseConflictQuery)
                ->first();

            if ($classConflict) {
                $cName = $classConflict->schoolClass->class_name ?? 'Kelas bersangkutan';
                $tName = $classConflict->teacher->fullname ?? 'Guru lain';
                $tRange = substr($classConflict->start_time, 0, 5).' - '.substr($classConflict->end_time, 0, 5);

                return "Bentrok Kelas! Kelas {$cName} sudah memiliki jadwal pelajaran {$classConflict->subject} bersama {$tName} pada hari {$day} pukul {$tRange} ({$classConflict->cycle_type}).";
            }
        }

        return null;
    }
}

<?php

namespace App\Http\Controllers\Web\Waka;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Schedule;
use App\Models\User;
use App\Models\Classes;
use App\Models\Room;
use App\Models\SystemConfiguration;
use App\Services\ScheduleImportService;
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

        if ($request->filled('day')) {
            $query->where('day_of_week', $request->day);
        }
        if ($request->filled('cycle')) {
            $query->where('cycle_type', $request->cycle);
        }
        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }
        if ($request->filled('grade_level')) {
            $query->whereHas('schoolClass', function ($q) use ($request) {
                $q->where(function ($sub) use ($request) {
                    $sub->where('grade_level', $request->grade_level)
                        ->orWhere('class_name', 'LIKE', $request->grade_level . ' %');
                });
            });
        }
        if ($request->filled('major')) {
            $query->whereHas('schoolClass', function ($q) use ($request) {
                $q->where(function ($sub) use ($request) {
                    $sub->where('major', strtoupper($request->major))
                        ->orWhere('class_name', 'LIKE', '% ' . $request->major . ' %');
                });
            });
        }
        if ($request->filled('section')) {
            $query->whereHas('schoolClass', function ($q) use ($request) {
                $q->where(function ($sub) use ($request) {
                    $sub->where('section', $request->section)
                        ->orWhere('class_name', 'LIKE', '% ' . $request->section);
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
            'sections'
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
            $sp = (int)$request->start_period;
            $ep = (int)$request->end_period;
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
            return back()->withInput()->withErrors(['conflict' => $conflictError]);
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
            $sp = (int)$request->start_period;
            $ep = (int)$request->end_period;
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
            return back()->withInput()->withErrors(['conflict' => $conflictError]);
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
            'file' => 'required|file|mimes:xlsx,xls,csv,docx,pdf|max:10240',
            'cycle_type' => 'nullable|string|in:Putaran 1,Putaran 2,semua,auto',
        ], [
            'file.required' => 'Silakan pilih file jadwal yang ingin diimpor.',
            'file.mimes'    => 'Format file tidak didukung! Harap unggah file berekstensi Excel (.xlsx, .xls, .csv), Word (.docx), atau PDF (.pdf).',
            'file.max'      => 'Ukuran file melebihi batas maksimum (10 MB).',
        ]);

        try {
            $cycleOverride = ($request->cycle_type && $request->cycle_type !== 'auto') ? $request->cycle_type : null;
            $result = $importService->import($request->file('file'), $cycleOverride);

            $message = "Proses impor jadwal selesai! Total baris diproses: {$result['total_rows']}. Jadwal berhasil ditambahkan: {$result['created']}.";

            if ($result['skipped'] > 0) {
                $message .= " Dilewati: {$result['skipped']}.";
            }

            if (!empty($result['errors'])) {
                return redirect()->route('waka.schedules.index')
                    ->with('warning', $message)
                    ->with('import_errors', $result['errors']);
            }

            return redirect()->route('waka.schedules.index')->with('success', $message);
        } catch (\Throwable $e) {
            return redirect()->route('waka.schedules.index')->with('error', 'Gagal memproses file impor: ' . $e->getMessage());
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
        // Proteksi bentrok dinonaktifkan sesuai permintaan sekolah
        return null;
    }
}

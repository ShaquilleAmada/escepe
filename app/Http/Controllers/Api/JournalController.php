<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Journal;
use App\Models\Attendance;
use App\Models\Schedule;
use App\Models\LeaveRequest;
use App\Models\User;

class JournalController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Journal::with(['schedule.teacher:id,fullname', 'schedule.schoolClass:id,class_name', 'schedule.room:id,room_name', 'attendances.student:id,fullname']);

        if (in_array($user->role_id, [2, 5, 6])) { // Guru Mapel, BK, & Wali Kelas (Pengajar)
            $query->whereHas('schedule', function ($q) use ($user) {
                $q->where('teacher_id', $user->id);
            });
        } elseif (in_array($user->role_id, [3, 4])) { // Siswa / Ortu
            $classId = $user->class_id ?? ($user->children()->first()->class_id ?? null);
            if ($classId) {
                $query->whereHas('schedule', function ($q) use ($classId) {
                    $q->where('class_id', $classId);
                });
            }
        }

        $journals = $query->orderBy('date', 'desc')->paginate(10);

        return response()->json([
            'success' => true,
            'data'    => $journals
        ], 200);
    }

    public function show($id)
    {
        $journal = Journal::with([
            'schedule.teacher:id,fullname',
            'schedule.schoolClass:id,class_name',
            'schedule.room:id,room_name',
            'attendances.student:id,fullname,username'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $journal
        ], 200);
    }

    public function getStudentsForSession(Request $request, $scheduleId)
    {
        $schedule  = Schedule::with(['schoolClass', 'room'])->findOrFail($scheduleId);
        $todayDate = date('Y-m-d');

        // Cek apakah jurnal sudah pernah diisi untuk hari ini
        $existingJournal = Journal::where('schedule_id', $scheduleId)
            ->whereDate('date', $todayDate)
            ->first();

        $students = User::where('role_id', 3)
            ->where('class_id', $schedule->class_id)
            ->select('id', 'fullname', 'username')
            ->orderBy('fullname')
            ->get();

        // Cari perizinan siswa yang SUDAH DI-APPROVE untuk hari ini
        $approvedLeaves = LeaveRequest::whereIn('student_id', $students->pluck('id'))
            ->where('status_approval', 'approved')
            ->whereDate('start_date', '<=', $todayDate)
            ->whereDate('end_date', '>=', $todayDate)
            ->get()
            ->keyBy('student_id');

        $formattedStudents = $students->map(function ($student) use ($approvedLeaves) {
            $hasLeave      = isset($approvedLeaves[$student->id]);
            $defaultStatus = 'hadir';
            $leaveReason   = null;

            if ($hasLeave) {
                $leaveInfo     = $approvedLeaves[$student->id];
                $leaveReason   = $leaveInfo->reason;
                $defaultStatus = stripos($leaveReason, 'sakit') !== false ? 'sakit' : 'izin';
            }

            return [
                'id'               => $student->id,
                'fullname'         => $student->fullname,
                'username'         => $student->username,
                'default_status'   => $defaultStatus,
                'is_approved_leave'=> $hasLeave,
                'leave_reason'     => $leaveReason,
            ];
        });

        return response()->json([
            'success'          => true,
            'schedule'         => $schedule,
            'date'             => $todayDate,
            'journal_exists'   => !is_null($existingJournal),
            'existing_journal' => $existingJournal,
            'students'         => $formattedStudents,
            'data'             => [
                'schedule'         => $schedule,
                'date'             => $todayDate,
                'journal_exists'   => !is_null($existingJournal),
                'existing_journal' => $existingJournal,
                'students'         => $formattedStudents,
            ],
        ], 200);
    }

    /**
     * Menyimpan Jurnal & Presensi Kelas via API
     * Dilengkapi pengecekan duplikat: satu jadwal hanya boleh satu jurnal per hari.
     */
    public function store(Request $request)
    {
        // Decode attendances jika dikirim sebagai JSON string (multipart/form-data)
        if (is_string($request->attendances)) {
            $decoded = json_decode($request->attendances, true);
            if (is_array($decoded)) {
                $request->merge(['attendances' => $decoded]);
            }
        }

        $validated = $request->validate([
            'schedule_id'              => 'required|exists:schedules,id',
            'date'                     => 'required|date',
            'topic_description'        => 'required|string',
            'photo'                    => 'required|image|mimes:jpg,jpeg,png|max:2048',
            'attendances'              => 'required|array',
            'attendances.*.student_id' => 'required|exists:users,id',
            'attendances.*.status'     => 'required|in:hadir,izin,sakit,alpa',
        ]);

        // Cegah duplikasi jurnal: satu jadwal + satu tanggal = satu jurnal
        $existing = Journal::where('schedule_id', $validated['schedule_id'])
            ->whereDate('date', $validated['date'])
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Jurnal untuk jadwal ini pada tanggal ' . $validated['date'] . ' sudah ada. Gunakan data yang sudah tersimpan.',
                'data'    => $existing->load(['attendances']),
            ], 422);
        }

        $photoPath = $request->file('photo')->store('photos/journals', 'public');

        $journal = Journal::create([
            'schedule_id'       => $validated['schedule_id'],
            'date'              => $validated['date'],
            'topic_description' => $validated['topic_description'],
            'photo_path'        => $photoPath,
        ]);

        foreach ($validated['attendances'] as $att) {
            Attendance::create([
                'journal_id' => $journal->id,
                'student_id' => $att['student_id'],
                'status'     => $att['status'],
                'date'       => $validated['date'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Jurnal dan presensi kelas berhasil disimpan',
            'data'    => $journal->load(['attendances']),
        ], 201);
    }

    /**
     * Update Jurnal & Presensi Kelas via API
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        $journal = Journal::with('schedule')->findOrFail($id);

        // Hanya guru pemilik jadwal atau admin yang boleh update
        if ($user->role_id != 1 && $journal->schedule->teacher_id != $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Anda hanya dapat mengubah jurnal mengajar Anda sendiri.'
            ], 403);
        }

        // Decode attendances jika dikirim sebagai JSON string (multipart/form-data)
        if (is_string($request->attendances)) {
            $decoded = json_decode($request->attendances, true);
            if (is_array($decoded)) {
                $request->merge(['attendances' => $decoded]);
            }
        }

        $validated = $request->validate([
            'topic_description'        => 'sometimes|required|string',
            'photo'                    => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'attendances'              => 'nullable|array',
            'attendances.*.student_id' => 'required_with:attendances|exists:users,id',
            'attendances.*.status'     => 'required_with:attendances|in:hadir,izin,sakit,alpa',
        ]);

        if ($request->has('topic_description')) {
            $journal->topic_description = $validated['topic_description'];
        }

        if ($request->hasFile('photo')) {
            $journal->photo_path = $request->file('photo')->store('photos/journals', 'public');
        }

        $journal->save();

        // Update attendances jika disertakan
        if (!empty($validated['attendances'])) {
            foreach ($validated['attendances'] as $att) {
                Attendance::updateOrCreate(
                    [
                        'journal_id' => $journal->id,
                        'student_id' => $att['student_id'],
                    ],
                    [
                        'status' => $att['status'],
                        'date'   => $journal->date,
                    ]
                );
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Jurnal dan presensi berhasil diperbarui',
            'data'    => $journal->fresh()->load(['attendances.student:id,fullname,username', 'schedule']),
        ], 200);
    }

    /**
     * Rekap Statistik Kehadiran Siswa
     * Fix null safety untuk role Ortu yang tidak punya anak terdaftar
     */
    public function getAttendanceSummary(Request $request)
    {
        $user      = $request->user();
        $studentId = $request->student_id;

        if ($user->role_id == 3) { // Siswa
            $studentId = $user->id;
        } elseif ($user->role_id == 4) { // Ortu — fix null safety
            $child     = $user->children()->first();
            $studentId = $child ? $child->id : null;

            if (!$studentId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akun orang tua ini belum dikaitkan dengan data siswa.',
                ], 404);
            }
        }

        $query = Attendance::query();
        if ($studentId) {
            $query->where('student_id', $studentId);
        }

        $summary = [
            'total_sesi' => $query->count(),
            'hadir'      => (clone $query)->where('status', 'hadir')->count(),
            'izin'       => (clone $query)->where('status', 'izin')->count(),
            'sakit'      => (clone $query)->where('status', 'sakit')->count(),
            'alpa'       => (clone $query)->where('status', 'alpa')->count(),
        ];

        return response()->json([
            'success'    => true,
            'student_id' => $studentId,
            'summary'    => $summary,
        ], 200);
    }
}

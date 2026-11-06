<?php

namespace App\Http\Controllers\Web\Waka;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Classes;
use App\Models\User;
use App\Services\WalasImportService;
use Illuminate\Support\Facades\Hash;

class ClassController extends Controller
{
    public function index(Request $request)
    {
        $gradeLevels = Classes::GRADE_LEVELS;
        $majors = Classes::MAJORS;
        $sections = Classes::SECTIONS;

        $query = Classes::withCount(['students', 'schedules'])
            ->with(['homeroomTeacher', 'students' => function ($q) {
                $q->orderBy('fullname');
            }]);

        if ($request->filled('grade_level')) {
            $query->where(function ($q) use ($request) {
                $q->where('grade_level', $request->grade_level)
                  ->orWhere('class_name', 'LIKE', $request->grade_level . ' %');
            });
        }

        if ($request->filled('major')) {
            $query->where(function ($q) use ($request) {
                $q->where('major', strtoupper($request->major))
                  ->orWhere('class_name', 'LIKE', '% ' . $request->major . ' %');
            });
        }

        if ($request->filled('section')) {
            $query->where(function ($q) use ($request) {
                $q->where('section', $request->section)
                  ->orWhere('class_name', 'LIKE', '% ' . $request->section);
            });
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where('class_name', 'like', "%{$search}%");
        }

        $classes = $query->orderByRaw("FIELD(grade_level, 'X', 'XI', 'XII')")
            ->orderBy('major')
            ->orderBy('section')
            ->orderBy('class_name')
            ->paginate(15)
            ->withQueryString();

        // Daftar semua guru untuk pilihan dropdown Wali Kelas
        $teachers = User::whereIn('role_id', [2, 6])
            ->orderBy('fullname')
            ->get();

        return view('waka.classes.index', compact('classes', 'gradeLevels', 'majors', 'sections', 'teachers'));
    }

    public function store(Request $request)
    {
        $gradeLevel = $request->grade_level ? strtoupper(trim($request->grade_level)) : null;
        $major = $request->major ? strtoupper(trim($request->major)) : null;
        $section = $request->section ? trim($request->section) : null;

        if ($gradeLevel && $major && $section) {
            $className = "{$gradeLevel} {$major} {$section}";
            $request->merge(['class_name' => $className]);
        } else {
            $className = trim($request->class_name ?? '');
            if ($className && (!$gradeLevel || !$major || !$section)) {
                $parts = preg_split('/\s+/', $className);
                $gradeLevel = $gradeLevel ?: ($parts[0] ?? null);
                $major = $major ?: (isset($parts[1]) ? strtoupper($parts[1]) : null);
                $section = $section ?: ($parts[2] ?? null);
            }
        }

        $request->validate([
            'grade_level' => 'required|string|max:10',
            'major' => 'required|string|max:20',
            'section' => 'required|string|max:10',
            'class_name' => 'required|string|max:50|unique:classes,class_name',
        ], [
            'grade_level.required' => 'Masukkan tingkat kelas terlebih dahulu.',
            'major.required' => 'Masukkan jurusan terlebih dahulu.',
            'section.required' => 'Masukkan rombel terlebih dahulu.',
            'class_name.unique' => 'Kelas Sudah Ada',
        ]);

        Classes::create([
            'class_name' => $className,
            'grade_level' => $gradeLevel,
            'major' => $major,
            'section' => $section,
        ]);

        return redirect()->route('waka.classes.index')->with('success', "Kelas {$className} berhasil ditambahkan.");
    }

    public function update(Request $request, $id)
    {
        $class = Classes::findOrFail($id);

        $gradeLevel = $request->grade_level ? strtoupper(trim($request->grade_level)) : $class->grade_level;
        $major = $request->major ? strtoupper(trim($request->major)) : $class->major;
        $section = $request->section ? trim($request->section) : $class->section;

        if ($gradeLevel && $major && $section) {
            $className = "{$gradeLevel} {$major} {$section}";
            $request->merge(['class_name' => $className]);
        } else {
            $className = trim($request->class_name ?? $class->class_name);
            if ($className && (!$gradeLevel || !$major || !$section)) {
                $parts = preg_split('/\s+/', $className);
                $gradeLevel = $gradeLevel ?: ($parts[0] ?? null);
                $major = $major ?: (isset($parts[1]) ? strtoupper($parts[1]) : null);
                $section = $section ?: ($parts[2] ?? null);
            }
        }

        $request->validate([
            'grade_level' => 'required|string|max:10',
            'major' => 'required|string|max:20',
            'section' => 'required|string|max:10',
            'class_name' => 'required|string|max:50|unique:classes,class_name,' . $class->id,
        ], [
            'grade_level.required' => 'Masukkan tingkat kelas terlebih dahulu.',
            'major.required' => 'Masukkan jurusan terlebih dahulu.',
            'section.required' => 'Masukkan rombel terlebih dahulu.',
            'class_name.unique' => 'Kelas Sudah Ada',
        ]);

        $class->update([
            'class_name' => $className,
            'grade_level' => $gradeLevel,
            'major' => $major,
            'section' => $section,
        ]);

        return redirect()->route('waka.classes.index')->with('success', "Data kelas {$className} berhasil diperbarui.");
    }

    public function destroy($id)
    {
        $class = Classes::findOrFail($id);

        // Proteksi Relasi: Cegah penghapusan kelas jika masih ada siswa atau jadwal KBM
        $studentsCount = $class->students()->count();
        if ($studentsCount > 0) {
            return redirect()->route('waka.classes.index')
                ->with('error', "Gagal menghapus: Masih ada {$studentsCount} siswa terdaftar di kelas {$class->class_name}. Kosongkan atau naikkan siswa terlebih dahulu.");
        }

        $schedulesCount = $class->schedules()->count();
        if ($schedulesCount > 0) {
            return redirect()->route('waka.classes.index')
                ->with('error', "Gagal menghapus: Masih ada {$schedulesCount} jadwal KBM terkait kelas {$class->class_name}. Hapus jadwal terkait terlebih dahulu.");
        }

        $className = $class->class_name;
        $class->delete();

        return redirect()->route('waka.classes.index')->with('success', "Data kelas {$className} berhasil dihapus.");
    }

    /**
     * Menandai status kehadiran Wali Kelas Hari Ini (Hadir / Berhalangan)
     * Mengontrol mode Bypass BK secara seketika sesuai spesifikasi.
     */
    public function toggleWalasAbsence(Request $request)
    {
        $request->validate([
            'teacher_id' => 'required|exists:users,id',
            'date'       => 'nullable|date',
        ]);

        $date = $request->input('date', \Carbon\Carbon::today()->toDateString());
        $teacherId = $request->teacher_id;

        $absence = \App\Models\TeacherAbsence::where('teacher_id', $teacherId)->whereDate('date', $date)->first();

        if ($absence) {
            $absence->delete();
            $isAbsent = false;
            $msg = 'Status kehadiran wali kelas berhasil diubah menjadi: HADIR.';
        } else {
            \App\Models\TeacherAbsence::create([
                'teacher_id' => $teacherId,
                'date'       => $date,
                'reason'     => $request->input('reason', 'Berhalangan hadir (ditandai Waka Kurikulum)'),
                'marked_by'  => auth()->id(),
            ]);
            $isAbsent = true;
            $msg = 'Status wali kelas berhasil diubah menjadi: BERHALANGAN HADIR (Bypass BK aktif seketika).';
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'is_absent' => $isAbsent, 'message' => $msg]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Menghapus siswa dari kelas (jika siswa keluar / pindah sekolah)
     */
    public function removeStudent($studentId)
    {
        $student = User::where('role_id', 3)->findOrFail($studentId);
        $className = $student->schoolClass->class_name ?? '';
        $name = $student->fullname;
        $student->delete();

        return redirect()->route('waka.classes.index')->with('success', "Siswa '{$name}' berhasil dihapus dari kelas {$className}.");
    }

    /**
     * Menetapkan atau mengganti Wali Kelas untuk suatu rombel kelas
     */
    public function assignWalas(Request $request, $id)
    {
        $class = Classes::findOrFail($id);

        $request->validate([
            'teacher_id' => 'nullable|exists:users,id',
        ]);

        $newTeacherId = $request->teacher_id;

        // 1. Wali kelas lama di kelas ini dikembalikan ke role Guru (role_id = 2) dan class_id = null
        $currentWalas = User::where('class_id', $class->id)
            ->where('role_id', 6)
            ->get();

        foreach ($currentWalas as $old) {
            if ($old->id != $newTeacherId) {
                $old->update([
                    'role_id' => 2, // Kembalikan ke Guru Mapel biasa
                    'class_id' => null,
                ]);
            }
        }

        // 2. Jika dipilih guru baru sebagai walas
        if ($newTeacherId) {
            $newWalas = User::findOrFail($newTeacherId);

            // Jika guru ini sebelumnya walas di kelas lain, lepas dari kelas lama
            $newWalas->update([
                'role_id' => 6, // Ubah role jadi WaliKelas
                'class_id' => $class->id,
            ]);

            return redirect()->route('waka.classes.index')
                ->with('success', "Wali Kelas untuk {$class->class_name} berhasil ditetapkan ke {$newWalas->fullname}.");
        }

        return redirect()->route('waka.classes.index')
            ->with('success', "Wali Kelas untuk {$class->class_name} berhasil dikosongkan.");
    }

    /**
     * Impor Massal Penetapan Wali Kelas dari file Excel, CSV, Word, atau PDF.
     */
    public function importWalas(Request $request, WalasImportService $importService)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,docx,pdf|max:10240',
        ], [
            'file.required' => 'Pilih file dokumen terlebih dahulu.',
            'file.mimes' => 'Format file harus berupa Excel (.xlsx, .xls, .csv), Word (.docx), atau PDF.',
            'file.max' => 'Ukuran file maksimal adalah 10 MB.',
        ]);

        try {
            $result = $importService->import($request->file('file'));

            $msg = "Proses impor selesai! Total {$result['updated']} Wali Kelas berhasil ditetapkan.";
            if ($result['skipped'] > 0) {
                $msg .= " ({$result['skipped']} baris dilewati).";
            }

            return redirect()->route('waka.classes.index')
                ->with('success', $msg)
                ->with('walas_import_errors', $result['errors']);
        } catch (\Throwable $e) {
            return redirect()->route('waka.classes.index')
                ->with('error', 'Gagal memproses file: ' . $e->getMessage());
        }
    }
}

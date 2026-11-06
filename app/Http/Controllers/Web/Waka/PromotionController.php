<?php

namespace App\Http\Controllers\Web\Waka;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Classes;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PromotionController extends Controller
{
    /**
     * Tampilan utama kenaikan kelas dan kelulusan siswa
     */
    public function index(Request $request)
    {
        // 1. Ambil semua kelas dengan jumlah siswa aktif
        $classes = Classes::withCount(['students' => function ($q) {
            $q->where('role_id', 3)->where('student_status', 'aktif');
        }])
        ->orderByRaw("FIELD(grade_level, 'X', 'XI', 'XII')")
        ->orderBy('major')
        ->orderBy('section')
        ->get();

        $gradeLevels = Classes::GRADE_LEVELS;
        $majors      = Classes::MAJORS;
        sort($majors);
        $sections    = Classes::SECTIONS;

        // 2. Filter kelas asal jika dipilih
        $sourceClassId      = $request->get('source_class_id');
        $selectedGradeLevel = $request->get('grade_level');
        $selectedMajor      = $request->get('major');
        $selectedSection    = $request->get('section');

        // Jika user memilih via dropdown Tingkat, Jurusan, Rombel
        if (!$sourceClassId && ($selectedGradeLevel || $selectedMajor || $selectedSection)) {
            if (!$selectedGradeLevel || !$selectedMajor || !$selectedSection) {
                session()->now('warning', 'Silakan pilih Tingkat Kelas, Jurusan, dan Rombel secara lengkap untuk membuka data kelas.');
            } else {
                $matchedClass = Classes::where(function ($q) use ($selectedGradeLevel) {
                    $q->where('grade_level', $selectedGradeLevel)
                      ->orWhere('class_name', 'LIKE', $selectedGradeLevel . ' %');
                })->where(function ($q) use ($selectedMajor) {
                    $q->where('major', strtoupper($selectedMajor))
                      ->orWhere('class_name', 'LIKE', '% ' . $selectedMajor . ' %');
                })->where(function ($q) use ($selectedSection) {
                    $q->where('section', $selectedSection)
                      ->orWhere('class_name', 'LIKE', '% ' . $selectedSection);
                })->first();

                if ($matchedClass) {
                    $sourceClassId = $matchedClass->id;
                } else {
                    session()->now('warning', "Kelas {$selectedGradeLevel} {$selectedMajor} {$selectedSection} tidak ditemukan di sistem.");
                }
            }
        }

        $selectedClass = null;
        $students = collect();

        if ($sourceClassId) {
            $selectedClass = Classes::withCount(['students' => function ($q) {
                $q->where('role_id', 3)->where('student_status', 'aktif');
            }])->find($sourceClassId);

            if ($selectedClass) {
                $selectedGradeLevel = $selectedClass->grade_level;
                $selectedMajor      = $selectedClass->major;
                $selectedSection    = $selectedClass->section;

                $students = User::where('role_id', 3)
                    ->where('class_id', $sourceClassId)
                    ->where('student_status', 'aktif')
                    ->orderBy('fullname')
                    ->get();
            }
        }

        // 3. Rekomendasi kelas tujuan otomatis (jika kelas X RPL 1 -> XI RPL 1, jika XI RPL 1 -> XII RPL 1)
        $suggestedDestClass = null;
        if ($selectedClass) {
            $nextGrade = match ($selectedClass->grade_level) {
                'X' => 'XI',
                'XI' => 'XII',
                default => null,
            };

            if ($nextGrade) {
                $suggestedDestClass = Classes::where('grade_level', $nextGrade)
                    ->where('major', $selectedClass->major)
                    ->where('section', $selectedClass->section)
                    ->first();
            }
        }

        // 4. Statistik ringkas
        $totalActiveStudents = User::where('role_id', 3)->where('student_status', 'aktif')->count();
        $emptyClassesCount = $classes->where('students_count', 0)->count();
        $occupiedClassesCount = $classes->where('students_count', '>', 0)->count();

        return view('waka.promotions.index', compact(
            'classes',
            'gradeLevels',
            'majors',
            'sections',
            'selectedGradeLevel',
            'selectedMajor',
            'selectedSection',
            'sourceClassId',
            'selectedClass',
            'students',
            'suggestedDestClass',
            'totalActiveStudents',
            'emptyClassesCount',
            'occupiedClassesCount'
        ));
    }

    /**
     * Memproses kenaikan kelas (Promotion)
     * Aturan ketat: Kelas tujuan HARUS KOSONG (0 siswa aktif)
     */
    public function promote(Request $request)
    {
        $request->validate([
            'source_class_id' => 'required|exists:classes,id',
            'destination_class_id' => 'required|exists:classes,id|different:source_class_id',
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:users,id',
        ], [
            'destination_class_id.required' => 'Pilih kelas tujuan terlebih dahulu.',
            'destination_class_id.different' => 'Kelas tujuan tidak boleh sama dengan kelas asal.',
            'student_ids.required' => 'Pilih minimal satu siswa untuk dinaikkan kelasnya.',
            'student_ids.min' => 'Pilih minimal satu siswa untuk dinaikkan kelasnya.',
        ]);

        $sourceClass = Classes::findOrFail($request->source_class_id);
        $destClass = Classes::withCount(['students' => function ($q) {
            $q->where('role_id', 3)->where('student_status', 'aktif');
        }])->findOrFail($request->destination_class_id);

        // VALIDASI KRUSIAL: Kelas tujuan HARUS KOSONG
        if ($destClass->students_count > 0) {
            return redirect()->back()
                ->withInput()
                ->with('error', "Gagal menaikkan kelas! Kelas tujuan '{$destClass->class_name}' belum kosong (masih ada {$destClass->students_count} siswa aktif). Sesuai aturan, data siswa pada kelas diatasnya harus dikosongkan terlebih dahulu (luluskan kelas XII atau naikkan kelas XI).");
        }

        // Ambil siswa yang valid dari kelas asal
        $validStudents = User::where('role_id', 3)
            ->where('class_id', $sourceClass->id)
            ->where('student_status', 'aktif')
            ->whereIn('id', $request->student_ids)
            ->get();

        if ($validStudents->isEmpty()) {
            return redirect()->back()
                ->with('error', 'Tidak ada siswa valid yang dipilih dari kelas asal.');
        }

        DB::beginTransaction();
        try {
            // Update class_id ke kelas tujuan
            User::whereIn('id', $validStudents->pluck('id'))->update([
                'class_id' => $destClass->id,
            ]);

            DB::commit();

            return redirect()->route('waka.promotions.index', ['source_class_id' => $destClass->id])
                ->with('success', "Sukses! Sebanyak {$validStudents->count()} siswa berhasil dinaikkan dari {$sourceClass->class_name} ke {$destClass->class_name}. Kelas asal kini telah berkurang/kosong.");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan sistem saat memproses kenaikan kelas: ' . $e->getMessage());
        }
    }

    /**
     * Memproses kelulusan siswa (Graduation)
     * Siswa kelas XII diluluskan, data tetap tersimpan di database (status = 'lulus')
     * dan class_id diset null sehingga kelas XII menjadi KOSONG.
     */
    public function graduate(Request $request)
    {
        $request->validate([
            'source_class_id' => 'required|exists:classes,id',
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:users,id',
            'graduation_year' => 'required|string|max:20',
        ], [
            'student_ids.required' => 'Pilih minimal satu siswa untuk diluluskan.',
            'graduation_year.required' => 'Tahun kelulusan wajib diisi.',
        ]);

        $sourceClass = Classes::findOrFail($request->source_class_id);

        $validStudents = User::where('role_id', 3)
            ->where('class_id', $sourceClass->id)
            ->where('student_status', 'aktif')
            ->whereIn('id', $request->student_ids)
            ->get();

        if ($validStudents->isEmpty()) {
            return redirect()->back()
                ->with('error', 'Tidak ada siswa aktif yang dipilih untuk diluluskan.');
        }

        DB::beginTransaction();
        try {
            foreach ($validStudents as $student) {
                $student->update([
                    'student_status' => 'lulus',
                    'graduation_year' => trim($request->graduation_year),
                    'last_class_name' => $sourceClass->class_name,
                    'class_id' => null, // Melepaskan dari kelas sehingga kelas XII menjadi kosong
                ]);

                // Cabut semua token sesi mobile siswa yang lulus
                $student->tokens()->delete();

                // Nonaktifkan akun orang tua terkait dan cabut sesinya
                $parent = null;
                if ($student->parent_id) {
                    $parent = User::find($student->parent_id);
                }
                if (!$parent) {
                    $parent = User::where('role_id', 4)->where('parent_id', $student->id)->first();
                }

                if ($parent) {
                    $parent->update([
                        'student_status' => 'lulus',
                        'class_id' => null,
                    ]);
                    $parent->tokens()->delete();
                }
            }

            DB::commit();

            return redirect()->route('waka.promotions.index', ['source_class_id' => $sourceClass->id])
                ->with('success', "Sukses! Sebanyak {$validStudents->count()} siswa dari {$sourceClass->class_name} berhasil diluluskan. Data siswa dan akun orang tua dinonaktifkan sebagai alumni, dan kelas {$sourceClass->class_name} sekarang telah KOSONG sehingga siap diisi oleh kenaikan kelas XI.");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan saat meluluskan siswa: ' . $e->getMessage());
        }
    }

    /**
     * Mengembalikan status siswa lulus menjadi aktif ke kelas tertentu (Pembatalan/Restorasi jika salah klik)
     */
    public function restoreStudent(Request $request, $id)
    {
        $request->validate([
            'target_class_id' => 'required|exists:classes,id',
        ]);

        $student = User::where('role_id', 3)->where('student_status', 'lulus')->findOrFail($id);
        $targetClass = Classes::findOrFail($request->target_class_id);

        DB::beginTransaction();
        try {
            $student->update([
                'student_status' => 'aktif',
                'class_id' => $targetClass->id,
                'graduation_year' => null,
            ]);

            // Pulihkan juga status akun orang tua
            $parent = null;
            if ($student->parent_id) {
                $parent = User::find($student->parent_id);
            }
            if (!$parent) {
                $parent = User::where('role_id', 4)->where('parent_id', $student->id)->first();
            }

            if ($parent) {
                $parent->update([
                    'student_status' => 'aktif',
                    'class_id' => $targetClass->id,
                ]);
            }

            DB::commit();

            return redirect()->back()->with('success', "Siswa {$student->fullname} dan akun orang tua berhasil dikembalikan ke status aktif di kelas {$targetClass->class_name}.");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal memulihkan status siswa: ' . $e->getMessage());
        }
    }
}

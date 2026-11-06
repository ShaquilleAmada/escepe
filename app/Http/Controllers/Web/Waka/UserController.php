<?php

namespace App\Http\Controllers\Web\Waka;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Classes;
use App\Services\UserImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class UserController extends Controller
{
    /**
     * Menampilkan daftar pengguna beserta filter role & kelas.
     */
    public function index(Request $request)
    {
        $authId = Auth::id() ?? 0;
        $query = User::with(['role', 'schoolClass', 'parent'])
            ->leftJoin('classes', 'users.class_id', '=', 'classes.id')
            ->select('users.*')
            // 1. Akun Waka/Admin yang sedang login di paling atas
            ->orderByRaw('CASE WHEN users.id = ? THEN 0 WHEN users.role_id = 1 THEN 1 ELSE 2 END', [$authId])
            // 2. Guru / BK (role non-siswa tanpa kelas) jika ada
            ->orderByRaw('CASE WHEN users.role_id IN (1, 2, 5) AND users.class_id IS NULL THEN 1 ELSE 2 END')
            // 3. Urutkan berdasarkan Tingkat Kelas: Kelas 10 (X) -> Kelas 11 (XI) -> Kelas 12 (XII)
            ->orderByRaw("CASE 
                WHEN classes.grade_level = 'X' OR classes.class_name LIKE 'X %' THEN 1
                WHEN classes.grade_level = 'XI' OR classes.class_name LIKE 'XI %' THEN 2
                WHEN classes.grade_level = 'XII' OR classes.class_name LIKE 'XII %' THEN 3
                ELSE 4 END ASC")
            // 4. Urutkan Jurusan menurut Abjad (A-Z) & Rombel (1, 2, ...)
            ->orderBy('classes.major', 'asc')
            ->orderByRaw("CAST(classes.section AS UNSIGNED) ASC")
            ->orderBy('classes.class_name', 'asc')
            // 5. Urutkan Nama Pengguna menurut Abjad (A-Z)
            ->orderBy('users.fullname', 'asc');

        // Filter Pencarian (Nama, Username, Email)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('users.fullname', 'like', "%{$search}%")
                  ->orWhere('users.username', 'like', "%{$search}%")
                  ->orWhere('users.email', 'like', "%{$search}%");
            });
        }

        // Filter Role
        if ($request->filled('role_id')) {
            $query->where('users.role_id', $request->role_id);
        } elseif ($request->filled('role')) {
            $roleVal = strtolower(trim($request->role));
            $targetRoleId = match ($roleVal) {
                'admin', 'waka' => 1,
                'guru', 'guru_mapel' => 2,
                'siswa' => 3,
                'ortu', 'orangtua', 'orang_tua' => 4,
                'bk', 'bimbingan_konseling', 'konseling' => 5,
                'walas', 'walikelas', 'wali_kelas', 'wali' => 6,
                default => null,
            };
            if ($targetRoleId) {
                $query->where('users.role_id', $targetRoleId);
            }
        }

        // Filter Kelas
        if ($request->filled('class_id')) {
            $query->where('users.class_id', $request->class_id);
        }

        $users = $query->paginate(15)->withQueryString();

        $roles = Role::orderBy('id')->get();
        $classes = Classes::orderByRaw("CASE 
                WHEN grade_level = 'X' OR class_name LIKE 'X %' THEN 1
                WHEN grade_level = 'XI' OR class_name LIKE 'XI %' THEN 2
                WHEN grade_level = 'XII' OR class_name LIKE 'XII %' THEN 3
                ELSE 4 END ASC")
            ->orderBy('major', 'asc')
            ->orderByRaw("CAST(section AS UNSIGNED) ASC")
            ->orderBy('class_name', 'asc')
            ->get();
        $students = User::where('role_id', 3)->orderBy('fullname')->get();

        // Hitung statistik per role
        $counts = [
            'total' => User::count(),
            'waka' => User::where('role_id', 1)->count(),
            'guru' => User::where('role_id', 2)->count(),
            'siswa' => User::where('role_id', 3)->count(),
            'ortu' => User::where('role_id', 4)->count(),
            'bk' => User::where('role_id', 5)->count(),
            'walas' => User::where('role_id', 6)->count(),
        ];

        return view('waka.users.index', compact('users', 'roles', 'classes', 'students', 'counts'));
    }

    /**
     * Menyimpan pengguna baru ke database.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'fullname'  => 'required|string|max:100',
            'nip_nisn'  => 'nullable|string|max:30|unique:users,nip_nisn',
            'username'  => 'nullable|string|max:50|unique:users,username',
            'email'     => 'nullable|email|max:100|unique:users,email',
            'password'  => 'required|string|min:6',
            'role_id'   => 'required|exists:roles,id',
            'class_id'  => 'nullable|exists:classes,id',
            'parent_id' => 'nullable|exists:users,id',
        ], [
            'fullname.required' => 'Nama lengkap wajib diisi.',
            'nip_nisn.unique'   => 'NIP/NISN sudah digunakan oleh akun lain.',
            'nip_nisn.max'      => 'NIP/NISN maksimal 30 karakter.',
            'username.unique'   => 'Username sudah digunakan oleh akun lain.',
            'email.email'       => 'Format email tidak valid.',
            'email.unique'      => 'Email sudah terdaftar pada akun lain.',
            'password.required' => 'Password wajib diisi.',
            'password.min'      => 'Password minimal harus 6 karakter.',
            'role_id.required'  => 'Peran/Role wajib dipilih.',
            'role_id.exists'    => 'Role tidak valid.',
        ]);

        // Jika username kosong, otomatis generate: nama_pertama_nama_kedua
        if (empty($validated['username'])) {
            $existing = [];
            $validated['username'] = app(UserImportService::class)->generateUsername($validated['fullname'], $existing);
        }

        // Normalisasi: jika nip_nisn kosong set null agar unique tidak konflik
        if (empty($validated['nip_nisn'])) {
            $validated['nip_nisn'] = null;
        }

        $validated['password'] = Hash::make($validated['password']);

        // Jika bukan siswa atau walas, kosongkan class_id kecuali diisi
        if (!in_array($validated['role_id'], [3, 6]) && empty($validated['class_id'])) {
            $validated['class_id'] = null;
        }

        // Jika bukan orang tua, kosongkan parent_id
        if ($validated['role_id'] != 4) {
            $validated['parent_id'] = null;
        }

        $newUser = User::create($validated);

        // Jika user adalah siswa dan memiliki NIS, otomatis buat / tautkan akun Orang Tua terpisah (P + NIS)
        if ($newUser->role_id == 3) {
            $studentNis = !empty($newUser->nip_nisn) ? $newUser->nip_nisn : $newUser->username;
            if (!empty($studentNis)) {
                $parentUsername = 'P' . $studentNis;
                $parentUser = User::where('username', $parentUsername)->first();
                if (!$parentUser) {
                    $parentUser = User::create([
                        'fullname'  => 'Orang Tua ' . $newUser->fullname,
                        'username'  => $parentUsername,
                        'password'  => Hash::make('password123'),
                        'role_id'   => 4, // OrangTua
                        'parent_id' => $newUser->id,
                        'class_id'  => $newUser->class_id,
                    ]);
                } else {
                    $parentUser->update([
                        'parent_id' => $newUser->id,
                        'class_id'  => $newUser->class_id,
                    ]);
                }
                $newUser->update(['parent_id' => $parentUser->id]);
            }
        }

        return redirect()->route('waka.users.index')->with('success', "Akun '{$validated['fullname']}' berhasil ditambahkan.");
    }

    /**
     * Memperbarui data pengguna.
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'fullname'  => 'required|string|max:100',
            'nip_nisn'  => 'nullable|string|max:30|unique:users,nip_nisn,' . $user->id,
            'username'  => 'required|string|max:50|unique:users,username,' . $user->id,
            'email'     => 'nullable|email|max:100|unique:users,email,' . $user->id,
            'password'  => 'nullable|string|min:6',
            'role_id'   => 'required|exists:roles,id',
            'class_id'  => 'nullable|exists:classes,id',
            'parent_id' => 'nullable|exists:users,id',
        ], [
            'fullname.required' => 'Nama lengkap wajib diisi.',
            'nip_nisn.unique'   => 'NIP/NISN sudah digunakan oleh akun lain.',
            'nip_nisn.max'      => 'NIP/NISN maksimal 30 karakter.',
            'username.required' => 'Username wajib diisi.',
            'username.unique'   => 'Username sudah digunakan oleh akun lain.',
            'email.email'       => 'Format email tidak valid.',
            'email.unique'      => 'Email sudah terdaftar pada akun lain.',
            'password.min'      => 'Password baru minimal harus 6 karakter.',
            'role_id.required'  => 'Peran/Role wajib dipilih.',
            'role_id.exists'    => 'Role tidak valid.',
        ]);

        // Normalisasi: jika nip_nisn kosong set null agar unique tidak konflik
        if (empty($validated['nip_nisn'])) {
            $validated['nip_nisn'] = null;
        }

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        if (!in_array($validated['role_id'], [3, 6]) && empty($validated['class_id'])) {
            $validated['class_id'] = null;
        }

        if ($validated['role_id'] != 4) {
            $validated['parent_id'] = null;
        }

        $user->update($validated);

        // Jika user adalah siswa dan memiliki NIS, sinkronkan akun Orang Tua terpisah (P + NIS)
        if ($user->role_id == 3) {
            $studentNis = !empty($user->nip_nisn) ? $user->nip_nisn : $user->username;
            if (!empty($studentNis)) {
                $parentUsername = 'P' . $studentNis;
                $parentUser = User::where('username', $parentUsername)->first();
                if (!$parentUser) {
                    $parentUser = User::create([
                        'fullname'  => 'Orang Tua ' . $user->fullname,
                        'username'  => $parentUsername,
                        'password'  => Hash::make('password123'),
                        'role_id'   => 4, // OrangTua
                        'parent_id' => $user->id,
                        'class_id'  => $user->class_id,
                    ]);
                } else {
                    $parentUser->update([
                        'parent_id' => $user->id,
                        'class_id'  => $user->class_id,
                    ]);
                }
                $user->update(['parent_id' => $parentUser->id]);
            }
        }

        return redirect()->route('waka.users.index')->with('success', "Data akun '{$user->fullname}' berhasil diperbarui.");
    }

    /**
     * Menghapus akun pengguna dari sistem.
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        // Mencegah waka menghapus akunnya sendiri saat sedang login
        if ($user->id === Auth::id()) {
            return redirect()->route('waka.users.index')->with('error', 'Aksi ditolak! Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif digunakan.');
        }

        $name = $user->fullname;
        $user->delete();

        return redirect()->route('waka.users.index')->with('success', "Akun '{$name}' berhasil dihapus dari sistem.");
    }

    /**
     * Mengunduh file template Excel (.xlsx) untuk impor data pengguna.
     */
    public function downloadTemplate(UserImportService $importService)
    {
        $spreadsheet = $importService->generateTemplateSpreadsheet();
        $fileName = 'template_import_pengguna.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Memproses impor data pengguna dari file Excel, Word, atau PDF.
     */
    public function import(Request $request, UserImportService $importService)
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,docx,pdf|max:10240',
        ], [
            'file.required' => 'Silakan pilih file yang ingin diimpor.',
            'file.mimes'    => 'Format file tidak didukung! Harap unggah file berekstensi Excel (.xlsx, .xls, .csv), Word (.docx), atau PDF (.pdf).',
            'file.max'      => 'Ukuran file melebihi batas maksimum (10 MB).',
        ]);

        try {
            $result = $importService->import($request->file('file'));

            $message = "Proses impor selesai! Total baris diproses: {$result['total_rows']}. Akun baru dibuat: {$result['created']}, Akun diperbarui: {$result['updated']}.";

            if ($result['skipped'] > 0) {
                $message .= " Dilewati: {$result['skipped']}.";
            }

            if (!empty($result['errors'])) {
                return redirect()->route('waka.users.index')
                    ->with('warning', $message)
                    ->with('import_errors', $result['errors']);
            }

            return redirect()->route('waka.users.index')->with('success', $message);
        } catch (\Throwable $e) {
            return redirect()->route('waka.users.index')->with('error', 'Gagal memproses file impor: ' . $e->getMessage());
        }
    }
}


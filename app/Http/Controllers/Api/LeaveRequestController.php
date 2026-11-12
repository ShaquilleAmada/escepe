<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Journal;
use App\Models\LeaveRequest;
use App\Models\Role;
use App\Services\LeaveBypassService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class LeaveRequestController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = LeaveRequest::with(['student:id,fullname,username,class_id', 'applicant:id,fullname,role_id', 'verifier:id,fullname']);

        if ($user->role_id == Role::SISWA) { // Siswa
            $query->where('student_id', $user->id);
        } elseif ($user->role_id == Role::ORANG_TUA) { // Orang Tua
            $child = $user->children()->first();
            $childId = $child ? $child->id : null;
            if ($childId) {
                $query->where('student_id', $childId);
            }
        } elseif ($user->role_id == Role::WALI_KELAS) { // Wali Kelas hanya kelas binaan
            $query->whereHas('student', function ($q) use ($user) {
                $q->where('class_id', $user->class_id);
            });
        } elseif ($user->role_id == Role::BK) { // Guru BK hanya kelas binaan (Pilihan A)
            $query->whereHas('student.schoolClass', function ($q) use ($user) {
                $q->where('bk_id', $user->id);
            });
        }

        if ($request->filled('status')) {
            $query->where('status_approval', $request->status);
        }

        $leaveRequests = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $leaveRequests,
        ], 200);
    }

    /**
     * Jumlah Surat Izin Pending (Badge Counter untuk Mobile)
     */
    public function pendingCount(Request $request)
    {
        $user = $request->user();
        $query = LeaveRequest::where('status_approval', 'pending');

        if ($user->role_id == Role::WALI_KELAS) {
            $query->whereHas('student', function ($q) use ($user) {
                $q->where('class_id', $user->class_id);
            });
        } elseif ($user->role_id == Role::BK) {
            $query->whereHas('student.schoolClass', function ($q) use ($user) {
                $q->where('bk_id', $user->id);
            });
        }

        $count = $query->count();

        return response()->json([
            'success' => true,
            'pending_count' => $count,
        ], 200);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'student_id' => 'nullable|exists:users,id',
            'leave_type' => 'required|in:izin,sakit',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string',
            'document' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ], [
            'leave_type.required' => 'Jenis permohonan (izin/sakit) wajib dipilih.',
            'start_date.required' => 'Tanggal mulai izin wajib diisi.',
            'end_date.required' => 'Tanggal akhir izin wajib diisi.',
            'end_date.after_or_equal' => 'Tanggal selesai izin tidak boleh sebelum tanggal mulai.',
            'reason.required' => 'Keterangan/alasan izin wajib diisi.',
            'document.required' => 'Dokumen bukti pendukung (surat dokter/surat resmi) wajib diunggah.',
            'document.mimes' => 'Format berkas lampiran harus berupa JPG, PNG, atau PDF.',
            'document.max' => 'Ukuran berkas lampiran maksimal 2 MB.',
        ]);

        $studentId = $user->id;
        if ($user->role_id == Role::ORANG_TUA) {
            $child = $user->children()->first();
            $studentId = $child ? $child->id : ($validated['student_id'] ?? $user->id);
        }

        // ── Validasi: Maks 1 izin aktif per hari (tidak boleh ada izin pending/approved yg tumpang tindih) ──
        $existing = LeaveRequest::where('student_id', $studentId)
            ->whereNotIn('status_approval', ['rejected'])
            ->where('start_date', '<=', $validated['end_date'])
            ->where('end_date', '>=', $validated['start_date'])
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Sudah ada permohonan izin yang aktif pada rentang tanggal tersebut. Hanya 1 surat izin per hari yang diperbolehkan.',
            ], 422);
        }

        // Simpan dokumen di disk 'local' privat (bukan 'public' agar tidak bisa diakses publik tanpa login)
        $documentPath = null;
        if ($request->hasFile('document')) {
            $documentPath = $request->file('document')->store('private/documents/leave_requests', 'local');
        }

        $leave = LeaveRequest::create([
            'student_id' => $studentId,
            'applicant_id' => $user->id,
            'leave_type' => $validated['leave_type'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'reason' => $validated['reason'],
            'document_path' => $documentPath,
            'status_approval' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan surat izin berhasil dibuat',
            'data' => $leave,
        ], 201);
    }

    public function show($id)
    {
        $leave = LeaveRequest::with([
            'student:id,fullname,username,class_id',
            'student.schoolClass:id,class_name',
            'applicant:id,fullname,role_id',
            'verifier:id,fullname',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $leave,
        ], 200);
    }

    /**
     * Mengunduh/streaming dokumen bukti surat izin dengan otorisasi ketat
     */
    public function downloadDocument(Request $request, $id)
    {
        $user = $request->user();

        // Dukung otorisasi via query ?token= atau header Authorization (khusus Flutter Web / HTML <img> tag)
        if (! $user) {
            $rawToken = $request->query('token') ?: $request->bearerToken();
            if ($rawToken) {
                $tokenModel = \Laravel\Sanctum\PersonalAccessToken::findToken($rawToken);
                if ($tokenModel) {
                    $user = $tokenModel->tokenable;
                }
            }
        }

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Anda belum login atau token tidak valid. Silakan login kembali.',
            ], 401);
        }

        $leave = LeaveRequest::with('student')->findOrFail($id);

        if (! $leave->document_path) {
            return response()->json(['success' => false, 'message' => 'Berkas lampiran tidak ditemukan.'], 404);
        }

        // Pengecekan Otorisasi:
        // Waka (1), BK rombel binaan (5), Siswa pemilik (3), Ortu dari siswa (4), Walas kelas siswa (6)
        $isAuthorized = false;
        $roleId = (int) $user->role_id;
        $studentClassId = (int) ($leave->student->class_id ?? 0);

        if ($roleId === Role::ADMIN) {
            $isAuthorized = true;
        } elseif ($roleId === Role::BK) {
            $studentClass = $leave->student?->schoolClass;
            $isAuthorized = ! $studentClass || ! $studentClass->bk_id || (int) $studentClass->bk_id === (int) $user->id;
        } elseif ($roleId === Role::SISWA && (int) $user->id === (int) $leave->student_id) {
            $isAuthorized = true;
        } elseif ($roleId === Role::ORANG_TUA && $user->children()->where('users.id', $leave->student_id)->exists()) {
            $isAuthorized = true;
        } elseif ($roleId === Role::WALI_KELAS && (int) $user->class_id === $studentClassId) {
            $isAuthorized = true;
        }

        if (! $isAuthorized) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak: Anda tidak memiliki wewenang melihat dokumen ini.'], 403);
        }

        if (! Storage::disk('local')->exists($leave->document_path)) {
            // Cek fallback jika tersimpan di disk public lama
            if (Storage::disk('public')->exists($leave->document_path)) {
                return response()->file(Storage::disk('public')->path($leave->document_path), [
                    'Access-Control-Allow-Origin' => '*',
                    'Access-Control-Allow-Methods' => 'GET, OPTIONS',
                    'Access-Control-Allow-Headers' => 'Authorization, Content-Type, X-Requested-With',
                    'Cache-Control' => 'no-cache, private',
                ]);
            }

            return response()->json(['success' => false, 'message' => 'File fisik dokumen tidak ditemukan di server.'], 404);
        }

        return response()->file(Storage::disk('local')->path($leave->document_path), [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
            'Access-Control-Allow-Headers' => 'Authorization, Content-Type, X-Requested-With',
            'Cache-Control' => 'no-cache, private',
        ]);
    }

    public function verify(Request $request, $id, LeaveBypassService $bypassService)
    {
        $user = $request->user();
        $userRoleId = (int) $user->role_id;
        if (! in_array($userRoleId, [Role::ADMIN, Role::BK, Role::WALI_KELAS])) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Anda tidak memiliki wewenang untuk memverifikasi izin.',
            ], 403);
        }

        $validated = $request->validate([
            'status_approval' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status_approval,rejected|nullable|string|min:3',
        ], [
            'status_approval.required' => 'Status persetujuan (approved/rejected) wajib dikirim.',
            'rejection_reason.required_if' => 'Alasan penolakan wajib diisi jika permohonan izin ditolak.',
            'rejection_reason.min' => 'Alasan penolakan minimal 3 karakter.',
        ]);

        return DB::transaction(function () use ($id, $user, $validated, $bypassService) {
            // Gunakan lockForUpdate untuk mencegah race-condition / persetujuan ganda simultan
            $leave = LeaveRequest::with('student')->where('id', $id)->lockForUpdate()->firstOrFail();

            if ($leave->status_approval !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Keputusan sudah pernah diambil sebelumnya oleh verifikator lain. Status saat ini: '.$leave->status_approval,
                    'data' => $leave->load(['student', 'verifier']),
                ], 409);
            }

            // Evaluasi aturan wewenang & Bypass BK
            $authCheck = $bypassService->evaluateVerificationAuthority($user, $leave);
            if (! $authCheck['allowed']) {
                return response()->json([
                    'success' => false,
                    'message' => $authCheck['message'],
                ], 403);
            }

            $leave->status_approval = $validated['status_approval'];
            $leave->verified_by = $user->id;
            $leave->is_bypass = $authCheck['is_bypass'];
            $leave->decided_at = now();
            $leave->rejection_reason = $validated['status_approval'] === 'rejected' ? $validated['rejection_reason'] : null;
            $leave->save();

            // CRIT-06: Sinkronisasi Otomatis ke Presensi KBM jika status Disetujui (Approved)
            if ($leave->status_approval === 'approved' && $leave->student && $leave->student->class_id) {
                $statusPresensi = $leave->leave_type ?: 'izin'; // 'izin' atau 'sakit'

                // Ambil semua jurnal pada kelas siswa dalam rentang tanggal izin
                $journals = Journal::whereBetween('date', [$leave->start_date, $leave->end_date])
                    ->whereHas('schedule', function ($q) use ($leave) {
                        $q->where('class_id', $leave->student->class_id);
                    })
                    ->get();

                foreach ($journals as $journal) {
                    Attendance::updateOrCreate(
                        [
                            'journal_id' => $journal->id,
                            'student_id' => $leave->student_id,
                        ],
                        [
                            'status' => $statusPresensi,
                            'date' => $journal->date,
                        ]
                    );
                }
            }

            $msg = 'Status surat izin berhasil diperbarui menjadi '.$leave->status_approval;
            if ($leave->is_bypass) {
                $msg .= ' melalui mode Bypass BK ('.$authCheck['message'].')';
            }

            return response()->json([
                'success' => true,
                'message' => $msg,
                'data' => $leave->load(['student', 'verifier']),
            ], 200);
        });
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $userRoleId = (int) $user->role_id;
        $leave = LeaveRequest::with('student')->findOrFail($id);

        // Otorisasi: Admin (1), Walas kelas siswa (6), BK binaan (5), atau siswa pembuat (3)
        $canDelete = false;
        if ($userRoleId === Role::ADMIN) {
            $canDelete = true;
        } elseif ($userRoleId === Role::SISWA && (int) $user->id === (int) $leave->student_id) {
            $canDelete = true;
        } elseif ($userRoleId === Role::ORANG_TUA && $user->children()->where('users.id', $leave->student_id)->exists()) {
            $canDelete = true;
        } elseif ($userRoleId === Role::WALI_KELAS && (int) $user->class_id === (int) ($leave->student->class_id ?? null)) {
            $canDelete = true;
        } elseif ($userRoleId === Role::BK) {
            $studentClass = $leave->student?->schoolClass;
            $canDelete = ! $studentClass || ! $studentClass->bk_id || (int) $studentClass->bk_id === (int) $user->id;
        }

        if (! $canDelete) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak: Anda tidak memiliki wewenang menghapus surat izin ini.'], 403);
        }

        if ($leave->document_path && Storage::disk('local')->exists($leave->document_path)) {
            Storage::disk('local')->delete($leave->document_path);
        }

        $leave->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data surat izin berhasil dihapus.',
        ], 200);
    }
}

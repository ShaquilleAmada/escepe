<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Models\Journal;
use App\Models\Attendance;
use App\Models\Role;
use App\Services\LeaveBypassService;
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
            $child   = $user->children()->first();
            $childId = $child ? $child->id : null;
            if ($childId) {
                $query->where('student_id', $childId);
            }
        } elseif ($user->role_id == Role::WALI_KELAS) { // Wali Kelas hanya kelas binaan
            $query->whereHas('student', function ($q) use ($user) {
                $q->where('class_id', $user->class_id);
            });
        }

        if ($request->filled('status')) {
            $query->where('status_approval', $request->status);
        }

        $leaveRequests = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'data'    => $leaveRequests
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
        }

        $count = $query->count();

        return response()->json([
            'success'       => true,
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
            'end_date'   => 'required|date|after_or_equal:start_date',
            'reason'     => 'required|string',
            'document'   => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ], [
            'leave_type.required' => 'Jenis permohonan (izin/sakit) wajib dipilih.',
            'start_date.required' => 'Tanggal mulai izin wajib diisi.',
            'end_date.required'   => 'Tanggal akhir izin wajib diisi.',
            'end_date.after_or_equal' => 'Tanggal selesai izin tidak boleh sebelum tanggal mulai.',
            'reason.required'     => 'Keterangan/alasan izin wajib diisi.',
            'document.mimes'      => 'Format berkas lampiran harus berupa JPG, PNG, atau PDF.',
            'document.max'        => 'Ukuran berkas lampiran maksimal 2 MB.',
        ]);

        $studentId = $user->id;
        if ($user->role_id == Role::ORANG_TUA) {
            $child     = $user->children()->first();
            $studentId = $child ? $child->id : ($validated['student_id'] ?? $user->id);
        }

        // Simpan dokumen di disk 'local' privat (bukan 'public' agar tidak bisa diakses publik tanpa login)
        $documentPath = null;
        if ($request->hasFile('document')) {
            $documentPath = $request->file('document')->store('private/documents/leave_requests', 'local');
        }

        $leave = LeaveRequest::create([
            'student_id'      => $studentId,
            'applicant_id'    => $user->id,
            'leave_type'      => $validated['leave_type'],
            'start_date'      => $validated['start_date'],
            'end_date'        => $validated['end_date'],
            'reason'          => $validated['reason'],
            'document_path'   => $documentPath,
            'status_approval' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan surat izin berhasil dibuat',
            'data'    => $leave
        ], 201);
    }

    public function show($id)
    {
        $leave = LeaveRequest::with([
            'student:id,fullname,username,class_id',
            'student.schoolClass:id,class_name',
            'applicant:id,fullname,role_id',
            'verifier:id,fullname'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $leave
        ], 200);
    }

    /**
     * Mengunduh/streaming dokumen bukti surat izin dengan otorisasi ketat
     */
    public function downloadDocument(Request $request, $id)
    {
        $user = $request->user();
        $leave = LeaveRequest::with('student')->findOrFail($id);

        if (!$leave->document_path) {
            return response()->json(['success' => false, 'message' => 'Berkas lampiran tidak ditemukan.'], 404);
        }

        // Pengecekan Otorisasi:
        // Waka (1), BK (5), Siswa pemilik (3), Ortu dari siswa (4), Walas kelas siswa (6)
        $isAuthorized = false;
        if (in_array($user->role_id, [Role::ADMIN, Role::BK])) {
            $isAuthorized = true;
        } elseif ($user->role_id == Role::SISWA && $user->id == $leave->student_id) {
            $isAuthorized = true;
        } elseif ($user->role_id == Role::ORANG_TUA && $user->children()->where('users.id', $leave->student_id)->exists()) {
            $isAuthorized = true;
        } elseif ($user->role_id == Role::WALI_KELAS && $user->class_id == ($leave->student->class_id ?? null)) {
            $isAuthorized = true;
        }

        if (!$isAuthorized) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak: Anda tidak memiliki wewenang melihat dokumen ini.'], 403);
        }

        if (!Storage::disk('local')->exists($leave->document_path)) {
            // Cek fallback jika tersimpan di disk public lama
            if (Storage::disk('public')->exists($leave->document_path)) {
                return response()->file(Storage::disk('public')->path($leave->document_path));
            }
            return response()->json(['success' => false, 'message' => 'File fisik dokumen tidak ditemukan di server.'], 404);
        }

        return response()->file(Storage::disk('local')->path($leave->document_path));
    }

    public function verify(Request $request, $id, LeaveBypassService $bypassService)
    {
        $user = $request->user();
        if (!in_array($user->role_id, [Role::ADMIN, Role::BK, Role::WALI_KELAS])) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Anda tidak memiliki wewenang untuk memverifikasi izin.'
            ], 403);
        }

        $validated = $request->validate([
            'status_approval'  => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status_approval,rejected|nullable|string|min:3',
        ], [
            'status_approval.required' => 'Status persetujuan (approved/rejected) wajib dikirim.',
            'rejection_reason.required_if' => 'Alasan penolakan wajib diisi jika permohonan izin ditolak.',
            'rejection_reason.min' => 'Alasan penolakan minimal 3 karakter.',
        ]);

        return DB::transaction(function () use ($request, $id, $user, $validated, $bypassService) {
            // Gunakan lockForUpdate untuk mencegah race-condition / persetujuan ganda simultan
            $leave = LeaveRequest::with('student')->where('id', $id)->lockForUpdate()->firstOrFail();

            if ($leave->status_approval !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Keputusan sudah pernah diambil sebelumnya oleh verifikator lain. Status saat ini: ' . $leave->status_approval,
                    'data'    => $leave->load(['student', 'verifier'])
                ], 409);
            }

            // Evaluasi aturan wewenang & Bypass BK
            $authCheck = $bypassService->evaluateVerificationAuthority($user, $leave);
            if (!$authCheck['allowed']) {
                return response()->json([
                    'success' => false,
                    'message' => $authCheck['message']
                ], 403);
            }

            $leave->status_approval = $validated['status_approval'];
            $leave->verified_by     = $user->id;
            $leave->is_bypass       = $authCheck['is_bypass'];
            $leave->decided_at      = now();
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
                            'date'   => $journal->date,
                        ]
                    );
                }
            }

            $msg = 'Status surat izin berhasil diperbarui menjadi ' . $leave->status_approval;
            if ($leave->is_bypass) {
                $msg .= ' melalui mode Bypass BK (' . $authCheck['message'] . ')';
            }

            return response()->json([
                'success' => true,
                'message' => $msg,
                'data'    => $leave->load(['student', 'verifier'])
            ], 200);
        });
    }
}

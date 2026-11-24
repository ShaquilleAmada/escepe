@extends('layouts.app')

@section('title', 'Monitoring Jurnal Mengajar Guru')

@section('content')
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-3">
<div>
<h4 class="fw-bold mb-1 text-dark">Monitoring Jurnal Mengajar Guru</h4>
<p class="text-secondary small mb-0" style="max-width: 55ch;">Pantau keterisian materi dan bukti dokumentasi proses belajar mengajar di kelas secara real-time.</p>
</div>
</div>

<!-- Filter Toolbar -->
<div class="card mb-4">
<div class="card-body p-3">
<form method="GET" action="{{ route('waka.journals.index') }}" class="row g-2 align-items-end">
<div class="col-6 col-md-4 col-lg-2">
<label class="form-label small fw-semibold text-secondary mb-1">Tanggal</label>
<input type="date" name="date" class="form-control form-control-sm" value="{{ request('date') }}">
</div>

<div class="col-6 col-md-4 col-lg-3">
<label class="form-label small fw-semibold text-secondary mb-1">Guru Pengampu</label>
<select name="teacher_id" class="form-select form-select-sm">
<option value="">Semua Guru</option>
@foreach($teachers as $t)
<option value="{{ $t->id }}" {{ request('teacher_id') == $t->id ? 'selected' : '' }}>{{ $t->fullname }}</option>
@endforeach
</select>
</div>

<div class="col-6 col-md-4 col-lg-2">
<label class="form-label small fw-semibold text-secondary mb-1">Tingkat Kelas</label>
<select name="grade_level" class="form-select form-select-sm">
<option value="">Semua Tingkat</option>
@foreach($grades as $g)
<option value="{{ $g }}" {{ request('grade_level') == $g ? 'selected' : '' }}>{{ $g }}</option>
@endforeach
</select>
</div>

<div class="col-6 col-md-4 col-lg-2">
<label class="form-label small fw-semibold text-secondary mb-1">Jurusan</label>
<select name="major" class="form-select form-select-sm">
<option value="">Semua Jurusan</option>
@foreach($majors as $m)
<option value="{{ $m }}" {{ request('major') == $m ? 'selected' : '' }}>{{ $m }}</option>
@endforeach
</select>
</div>

<div class="col-6 col-md-4 col-lg-1">
<label class="form-label small fw-semibold text-secondary mb-1">Rombel</label>
<select name="section" class="form-select form-select-sm">
<option value="">Semua</option>
@foreach($sections as $s)
<option value="{{ $s }}" {{ request('section') == $s ? 'selected' : '' }}>{{ $s }}</option>
@endforeach
</select>
</div>

<div class="col-6 col-md-4 col-lg-2 d-flex gap-1">
<button type="submit" class="btn btn-sm btn-primary w-100" title="Terapkan Filter">
<i class="bi bi-filter me-1"></i> Filter
</button>
<a href="{{ route('waka.journals.index') }}" class="btn btn-sm btn-outline-secondary" title="Reset Filter">
<i class="bi bi-arrow-counterclockwise"></i>
</a>
</div>
</form>
</div>
</div>

<!-- Tabel Riwayat Jurnal -->
<div class="card">
<div class="card-header d-flex justify-content-between align-items-center">
<span class="fw-bold text-dark">Daftar Jurnal Mengajar</span>
<span class="text-secondary small">Total: {{ $journals->total() }} Data</span>
</div>
<div class="card-body p-0">
<div class="table-responsive">
<table class="table table-hover align-middle mb-0">
<thead>
<tr>
<th style="width: 120px;">Tanggal</th>
<th>Guru & Mapel</th>
<th>Kelas & Ruangan</th>
<th>Topik / Ringkasan Materi</th>
<th style="width: 110px;" class="text-center">Presensi</th>
<th style="width: 110px;" class="text-center">Foto Bukti</th>
</tr>
</thead>
<tbody>
@forelse($journals as $j)
<tr>
<td class="tabular-time fw-semibold text-dark">
{{ date('d/m/Y', strtotime($j->date)) }}
</td>
<td>
<div class="fw-bold text-dark">{{ $j->schedule->teacher->fullname ?? '-' }}</div>
<div class="small text-muted">{{ $j->schedule->subject ?? '-' }}</div>
</td>
<td>
<div class="fw-semibold text-dark">{{ $j->schedule->schoolClass->class_name ?? '-' }}</div>
<small class="text-muted">{{ $j->schedule->room->room_name ?? '-' }}</small>
</td>
<td>
<div class="text-secondary small">{{ $j->topic_description }}</div>
</td>
<td class="text-center">
    <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2.5 d-inline-flex align-items-center gap-1 shadow-none btn-detail-journal" data-journal-id="{{ $j->id }}" title="Lihat Rincian Presensi Siswa">
        <i class="bi bi-people-fill"></i>
        <span>{{ $j->attendances->count() }} Siswa</span>
    </button>
</td>
<td class="text-center">
    @if($j->photo_path)
    <button type="button" class="btn btn-sm btn-outline-success py-1 px-2.5 d-inline-flex align-items-center gap-1 shadow-none btn-detail-journal" data-journal-id="{{ $j->id }}" title="Lihat Foto Bukti KBM Realtime">
        <i class="bi bi-camera-fill"></i>
        <span>Foto KBM</span>
    </button>
    @else
    <span class="text-muted small">-</span>
    @endif
</td>
</tr>
@empty
<tr>
<td colspan="6" class="text-center py-5 text-muted">
<i class="bi bi-journal-x fs-3 d-block mb-2"></i>
Belum ada riwayat jurnal yang ditemukan.
</td>
</tr>
@endforelse
</tbody>
</table>
</div>
</div>
@if($journals->hasPages())
<div class="card-footer bg-white border-top py-3">
<div class="d-flex justify-content-end">
{{ $journals->links('pagination::bootstrap-5') }}
</div>
</div>
@endif
</div>

<!-- Modal Rincian Jurnal & Presensi Siswa -->
<div class="modal fade" id="journalDetailModal" tabindex="-1" aria-labelledby="journalDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light border-bottom py-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-primary text-white p-2 rounded-3 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="bi bi-journal-text fs-5"></i>
                    </div>
                    <div>
                        <h6 class="modal-title fw-bold text-dark mb-0" id="journalDetailModalLabel">Detail Jurnal & Presensi Siswa</h6>
                        <small class="text-muted" id="modalJournalSubtitle">Memuat data jurnal...</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Loading State -->
                <div id="modalLoading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="text-muted small mt-2 mb-0">Mengambil data jurnal & presensi...</p>
                </div>

                <!-- Error State -->
                <div id="modalError" class="alert alert-danger d-none mb-0">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <span id="modalErrorMessage">Gagal memuat detail jurnal.</span>
                </div>

                <!-- Content Container -->
                <div id="modalContent" class="d-none">
                    <!-- Info KBM Card -->
                    <div class="card border-0 bg-light rounded-3 p-3 mb-3">
                        <div class="row g-2">
                            <div class="col-sm-6">
                                <span class="text-muted small d-block">Mata Pelajaran:</span>
                                <strong class="text-dark" id="modalSubject">-</strong>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted small d-block">Guru Pengampu:</span>
                                <strong class="text-dark" id="modalTeacher">-</strong>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted small d-block">Kelas & Ruang:</span>
                                <span class="text-dark fw-semibold" id="modalClassRoom">-</span>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-muted small d-block">Waktu & Jam:</span>
                                <span class="text-dark fw-semibold" id="modalTime">-</span>
                            </div>
                            <div class="col-12 mt-2 pt-2 border-top">
                                <span class="text-muted small d-block">Topik / Materi Pembelajaran:</span>
                                <p class="text-dark mb-0 mt-1 small" id="modalTopic" style="white-space: pre-wrap;">-</p>
                            </div>
                        </div>
                    </div>

                    <!-- Foto Dokumentasi KBM Realtime -->
                    <div class="card border mb-3" id="modalPhotoContainer">
                        <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                            <span class="fw-semibold small text-dark"><i class="bi bi-camera-fill text-success me-1"></i> Foto Bukti KBM Real-time</span>
                            <a href="#" id="modalPhotoFullBtn" target="_blank" class="btn btn-sm btn-link text-decoration-none p-0 small">
                                <i class="bi bi-box-arrow-up-right me-1"></i>Buka Ukuran Penuh
                            </a>
                        </div>
                        <div class="card-body text-center p-2 bg-dark rounded-bottom">
                            <img id="modalJournalPhoto" src="" alt="Foto Jurnal KBM" class="img-fluid rounded" style="max-height: 280px; object-fit: contain;">
                            <div id="modalNoPhoto" class="py-4 text-white-50 d-none">
                                <i class="bi bi-camera-slash fs-2 d-block mb-1"></i>
                                <span class="small">Tidak ada foto dokumentasi untuk jurnal ini.</span>
                            </div>
                        </div>
                    </div>

                    <!-- Statistik Presensi Siswa -->
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <span class="badge bg-secondary-subtle text-secondary px-3 py-2 rounded-pill fw-semibold">
                            Total: <strong id="modalStatTotal">0</strong> Siswa
                        </span>
                        <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill fw-semibold">
                            Hadir: <strong id="modalStatHadir">0</strong>
                        </span>
                        <span class="badge bg-info-subtle text-info px-3 py-2 rounded-pill fw-semibold">
                            Izin: <strong id="modalStatIzin">0</strong>
                        </span>
                        <span class="badge bg-warning-subtle text-warning px-3 py-2 rounded-pill fw-semibold">
                            Sakit: <strong id="modalStatSakit">0</strong>
                        </span>
                        <span class="badge bg-danger-subtle text-danger px-3 py-2 rounded-pill fw-semibold">
                            Alpa: <strong id="modalStatAlpa">0</strong>
                        </span>
                    </div>

                    <!-- Tabel Presensi Siswa -->
                    <div class="table-responsive border rounded-3">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light small text-muted text-uppercase">
                                <tr>
                                    <th style="width: 40px;" class="text-center">#</th>
                                    <th>NISN</th>
                                    <th>Nama Siswa</th>
                                    <th class="text-center" style="width: 100px;">Status</th>
                                    <th>Catatan</th>
                                </tr>
                            </thead>
                            <tbody id="modalAttendanceBody" class="small">
                                <!-- Populated by JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const detailModalEl = document.getElementById('journalDetailModal');
    if (!detailModalEl) return;
    const detailModal = new bootstrap.Modal(detailModalEl);

    const loadingEl = document.getElementById('modalLoading');
    const errorEl = document.getElementById('modalError');
    const contentEl = document.getElementById('modalContent');
    const errorMessageEl = document.getElementById('modalErrorMessage');

    const subtitleEl = document.getElementById('modalJournalSubtitle');
    const subjectEl = document.getElementById('modalSubject');
    const teacherEl = document.getElementById('modalTeacher');
    const classRoomEl = document.getElementById('modalClassRoom');
    const timeEl = document.getElementById('modalTime');
    const topicEl = document.getElementById('modalTopic');

    const photoContainerEl = document.getElementById('modalPhotoContainer');
    const photoEl = document.getElementById('modalJournalPhoto');
    const noPhotoEl = document.getElementById('modalNoPhoto');
    const photoFullBtn = document.getElementById('modalPhotoFullBtn');

    const statTotalEl = document.getElementById('modalStatTotal');
    const statHadirEl = document.getElementById('modalStatHadir');
    const statIzinEl = document.getElementById('modalStatIzin');
    const statSakitEl = document.getElementById('modalStatSakit');
    const statAlpaEl = document.getElementById('modalStatAlpa');
    const attendanceBodyEl = document.getElementById('modalAttendanceBody');

    document.querySelectorAll('.btn-detail-journal').forEach(btn => {
        btn.addEventListener('click', function () {
            const journalId = this.getAttribute('data-journal-id');
            if (!journalId) return;

            // Reset modal state
            loadingEl.classList.remove('d-none');
            errorEl.classList.add('d-none');
            contentEl.classList.add('d-none');
            detailModal.show();

            // Fetch JSON detail
            fetch(`/waka/journals/${journalId}/detail`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => {
                if (!res.ok) throw new Error(`HTTP ${res.status}: Gagal memuat data`);
                return res.json();
            })
            .then(res => {
                loadingEl.classList.add('d-none');
                if (!res.success || !res.data) {
                    errorEl.classList.remove('d-none');
                    errorMessageEl.textContent = res.message || 'Data tidak ditemukan.';
                    return;
                }

                const d = res.data;
                subtitleEl.textContent = `ID #${d.id} • ${d.teaching_date || '-'}`;
                subjectEl.textContent = d.subject || '-';
                teacherEl.textContent = d.teacher || '-';
                classRoomEl.textContent = `${d.class || '-'} (${d.room || '-'})`;
                timeEl.textContent = `${d.teaching_date || ''} [${d.time_start || ''} - ${d.time_end || ''}]`;
                topicEl.textContent = d.topic_description || '-';

                // Photo handling
                if (d.photo_url) {
                    photoEl.src = d.photo_url;
                    photoEl.classList.remove('d-none');
                    noPhotoEl.classList.add('d-none');
                    photoFullBtn.href = d.photo_url;
                    photoFullBtn.classList.remove('d-none');
                } else {
                    photoEl.src = '';
                    photoEl.classList.add('d-none');
                    noPhotoEl.classList.remove('d-none');
                    photoFullBtn.classList.add('d-none');
                }

                // Stats
                statTotalEl.textContent = d.stats.total;
                statHadirEl.textContent = d.stats.hadir;
                statIzinEl.textContent = d.stats.izin;
                statSakitEl.textContent = d.stats.sakit;
                statAlpaEl.textContent = d.stats.alpa;

                // Students attendance table
                attendanceBodyEl.innerHTML = '';
                if (!d.attendances || d.attendances.length === 0) {
                    attendanceBodyEl.innerHTML = `<tr><td colspan="5" class="text-center py-3 text-muted">Tidak ada data rincian siswa pada jurnal ini.</td></tr>`;
                } else {
                    d.attendances.forEach((att, idx) => {
                        let badgeClass = 'bg-secondary';
                        const statusLower = (att.status || '').toLowerCase();
                        if (statusLower === 'h' || statusLower === 'hadir') badgeClass = 'bg-success';
                        else if (statusLower === 'i' || statusLower === 'izin') badgeClass = 'bg-info text-dark';
                        else if (statusLower === 's' || statusLower === 'sakit') badgeClass = 'bg-warning text-dark';
                        else if (statusLower === 'a' || statusLower === 'alpa') badgeClass = 'bg-danger';

                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td class="text-center text-muted">${idx + 1}</td>
                            <td class="fw-mono">${att.nip_nisn || '-'}</td>
                            <td class="fw-semibold text-dark">${att.fullname || '-'}</td>
                            <td class="text-center">
                                <span class="badge ${badgeClass} text-uppercase">${att.status_label || att.status || '-'}</span>
                            </td>
                            <td class="text-muted small">${att.notes || '-'}</td>
                        `;
                        attendanceBodyEl.appendChild(tr);
                    });
                }

                contentEl.classList.remove('d-none');
            })
            .catch(err => {
                loadingEl.classList.add('d-none');
                errorEl.classList.remove('d-none');
                errorMessageEl.textContent = err.message || 'Terjadi kesalahan saat memuat data.';
            });
        });
    });
});
</script>
@endpush
@endsection

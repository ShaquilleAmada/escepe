@extends('layouts.app')

@section('title', 'Rekap Presensi Siswa')

@section('content')
<style>
/* Tombol Status Presensi - Seragam Dark Pill */
.btn-status-filter {
    background-color: #ffffff;
    border: 1px solid #cbd5e1;
    color: #334155;
    font-weight: 500;
    border-radius: 6px;
    padding: 4px 12px;
    font-size: 0.85rem;
    transition: all 0.15s ease-in-out;
}
.btn-status-filter:hover {
    background-color: #f1f5f9;
    color: #0f172a;
    border-color: #94a3b8;
}
.btn-status-filter.active {
    background-color: #0f172a !important;
    border-color: #0f172a !important;
    color: #ffffff !important;
    font-weight: 600;
}
</style>

{{-- Page Header --}}
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-2">
    <div>
        <h4 class="fw-bold mb-1 text-dark">Rekap Presensi Siswa</h4>
        <p class="text-secondary small mb-0">Pantau rekapitulasi kehadiran siswa per kelas berdasarkan tanggal dan jurnal mengajar.</p>
    </div>
</div>

{{-- Filter Card --}}
<div class="card mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('waka.attendances.recap') }}" id="filterForm" class="row g-2 align-items-end">
            <input type="hidden" name="status_filter" id="statusFilterInput" value="{{ $statusFilter }}">
            <div class="col-12 col-sm-6 col-md-3">
                <label class="form-label small fw-semibold text-secondary mb-1">Tanggal</label>
                <input type="date" name="date" class="form-control form-control-sm filter-auto-submit" value="{{ $date }}">
            </div>
            <div class="col-6 col-sm-4 col-md-3">
                <label class="form-label small fw-semibold text-secondary mb-1">Tingkat Kelas</label>
                <select name="grade_level" class="form-select form-select-sm filter-auto-submit">
                    <option value="">Semua Tingkat</option>
                    @foreach($grades as $g)
                        <option value="{{ $g }}" {{ request('grade_level') == $g ? 'selected' : '' }}>{{ $g }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-sm-4 col-md-3">
                <label class="form-label small fw-semibold text-secondary mb-1">Jurusan</label>
                <select name="major" class="form-select form-select-sm filter-auto-submit">
                    <option value="">Semua Jurusan</option>
                    @foreach($majors as $m)
                        <option value="{{ $m }}" {{ request('major') == $m ? 'selected' : '' }}>{{ $m }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-sm-4 col-md-2">
                <label class="form-label small fw-semibold text-secondary mb-1">Rombel</label>
                <select name="section" class="form-select form-select-sm filter-auto-submit">
                    <option value="">Semua</option>
                    @foreach($sections as $s)
                        <option value="{{ $s }}" {{ request('section') == $s ? 'selected' : '' }}>{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-sm-4 col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary w-100" title="Terapkan Filter">
                    <i class="bi bi-filter me-1"></i>Filter
                </button>
                <a href="{{ route('waka.attendances.recap') }}" class="btn btn-sm btn-outline-secondary" title="Reset Filter">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>

        {{-- Filter Cepat Status Rekap: Semua | Hanya yang Belum Final | Final --}}
        <div class="d-flex flex-wrap align-items-center gap-2 mt-3 pt-3 border-top">
            <span class="small fw-semibold text-secondary me-2"><i class="bi bi-funnel me-1"></i>Status Rekap:</span>
            <a href="{{ request()->fullUrlWithQuery(['status_filter' => 'all']) }}" 
               class="btn btn-sm {{ $statusFilter === 'all' ? 'btn-dark' : 'btn-outline-secondary' }} rounded-pill px-3 py-1">
                Semua <span class="badge {{ $statusFilter === 'all' ? 'bg-light text-dark' : 'bg-secondary' }} ms-1">{{ $totalClasses }}</span>
            </a>
            <a href="{{ request()->fullUrlWithQuery(['status_filter' => 'pending']) }}" 
               class="btn btn-sm {{ $statusFilter === 'pending' ? 'btn-warning text-dark fw-bold' : 'btn-outline-warning text-dark' }} rounded-pill px-3 py-1">
                <i class="bi bi-clock-history me-1"></i>Hanya yang Belum Final 
                <span class="badge bg-warning text-dark ms-1">{{ $pendingClassesCount }}</span>
            </a>
            <a href="{{ request()->fullUrlWithQuery(['status_filter' => 'final']) }}" 
               class="btn btn-sm {{ $statusFilter === 'final' ? 'btn-success text-white fw-bold' : 'btn-outline-success' }} rounded-pill px-3 py-1">
                <i class="bi bi-check-circle me-1"></i>Final / Bersih 
                <span class="badge bg-success ms-1">{{ $finalClassesCount }}</span>
            </a>
        </div>
    </div>
</div>

{{-- Data Table: Rekapitulasi per Kelas --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-bold text-dark">Rekapitulasi per Kelas</span>
        <span class="text-secondary small">Total: {{ count($recapPerClass) }} Kelas</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-3 text-secondary fw-semibold">Kelas</th>
                        <th class="text-center text-secondary fw-semibold" style="width:140px;">Status Hari Ini</th>
                        <th class="text-center text-secondary fw-semibold" style="width:100px;">Hadir</th>
                        <th class="text-center text-secondary fw-semibold" style="width:100px;">Izin</th>
                        <th class="text-center text-secondary fw-semibold" style="width:100px;">Sakit</th>
                        <th class="text-center text-secondary fw-semibold" style="width:100px;">Alpha</th>
                        <th class="text-center pe-3 text-secondary fw-semibold" style="width:90px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recapPerClass as $row)
                    <tr>
                        <td class="ps-3 fw-semibold text-dark">{{ $row['class_name'] }}</td>
                        <td class="text-center">
                            @if($row['status'] === 'final')
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" title="{{ $row['status_reason'] }}">
                                    <i class="bi bi-check-circle-fill me-1"></i>Final / Bersih
                                </span>
                            @else
                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1" title="{{ $row['status_reason'] }}">
                                    <i class="bi bi-exclamation-triangle-fill me-1"></i>Belum Final
                                </span>
                            @endif
                        </td>
                        <td class="text-center fw-semibold text-dark">{{ $row['hadir'] }}</td>
                        <td class="text-center fw-semibold text-dark">{{ $row['izin'] }}</td>
                        <td class="text-center fw-semibold text-dark">{{ $row['sakit'] }}</td>
                        <td class="text-center fw-semibold text-dark">{{ $row['alpa'] }}</td>
                        <td class="text-center pe-3">
                            <button type="button" 
                                    class="btn btn-sm btn-link text-primary text-decoration-none fw-semibold p-0 btn-show-detail" 
                                    data-bs-toggle="modal"
                                    data-bs-target="#detailModal"
                                    data-class-id="{{ $row['class_id'] }}" 
                                    data-class-name="{{ $row['class_name'] }}">
                                Detail
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-secondary">
                            <i class="bi bi-inbox" style="font-size:2rem; display:block; margin-bottom:8px;"></i>
                            <span>Tidak ada data kelas yang ditemukan.</span>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Modal Detail Presensi Siswa --}}
<div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow" style="border-radius:12px;">
            <div class="modal-header border-bottom py-3">
                <div>
                    <h5 class="modal-title fw-bold text-dark mb-0" id="detailModalLabel">
                        Detail Presensi Siswa — <span id="modalClassName" class="text-primary">-</span>
                    </h5>
                    <div class="text-secondary small mt-1">
                        <i class="bi bi-calendar3 me-1"></i>Tanggal: <strong id="modalDateFormatted" class="text-dark">-</strong>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                {{-- Loading State --}}
                <div id="modalLoading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Memuat...</span>
                    </div>
                    <p class="text-secondary small mt-2">Mengambil data presensi siswa...</p>
                </div>

                {{-- Content Container --}}
                <div id="modalContent" style="display:none;">
                    {{-- Quick Filter Buttons by Status --}}
                    <div class="d-flex flex-wrap gap-2 mb-3" id="modalStatusFilters">
                        <button type="button" class="btn btn-sm btn-status-filter filter-status-btn active" data-status="all">
                            Semua (<span id="sumTotal">0</span>)
                        </button>
                        <button type="button" class="btn btn-sm btn-status-filter filter-status-btn" data-status="hadir">
                            Hadir (<span id="sumHadir">0</span>)
                        </button>
                        <button type="button" class="btn btn-sm btn-status-filter filter-status-btn" data-status="izin">
                            Izin (<span id="sumIzin">0</span>)
                        </button>
                        <button type="button" class="btn btn-sm btn-status-filter filter-status-btn" data-status="sakit">
                            Sakit (<span id="sumSakit">0</span>)
                        </button>
                        <button type="button" class="btn btn-sm btn-status-filter filter-status-btn" data-status="alpa">
                            Alpha (<span id="sumAlpa">0</span>)
                        </button>
                        <button type="button" class="btn btn-sm btn-status-filter filter-status-btn" data-status="belum_diabsen">
                            Belum Ada Data (<span id="sumBelum">0</span>)
                        </button>
                    </div>

                    {{-- Search Input in Modal --}}
                    <div class="mb-3">
                        <input type="text" id="modalStudentSearch" class="form-control form-control-sm" placeholder="Cari nama atau NIS siswa di kelas ini...">
                    </div>

                    {{-- Journal Info (if available) --}}
                    <div id="journalInfoBlock" class="mb-3 p-2 bg-light rounded small border" style="display:none;">
                        <div class="fw-semibold text-secondary"><i class="bi bi-journal-text me-1"></i>Jurnal Pelajaran pada Tanggal Ini:</div>
                        <ul class="mb-0 ps-3 mt-1" id="journalList"></ul>
                    </div>

                    {{-- Table Students --}}
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0" style="font-size:0.85rem;">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:40px;">No</th>
                                    <th>Nama Siswa</th>
                                    <th style="width:130px;">NIS / Username</th>
                                    <th class="text-center" style="width:130px;">Status Presensi</th>
                                    <th>Keterangan / Mapel</th>
                                </tr>
                            </thead>
                            <tbody id="studentTableBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top py-2">
                <button type="button" class="btn btn-sm btn-danger px-3 fw-semibold" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Auto-submit filter
    document.querySelectorAll('.filter-auto-submit').forEach(function(el) {
        el.addEventListener('change', function() {
            document.getElementById('filterForm').submit();
        });
    });

    // Modal Detail Handler
    const modalEl = document.getElementById('detailModal');
    const detailModal = bootstrap.Modal.getOrCreateInstance(modalEl);
    const modalLoading = document.getElementById('modalLoading');
    const modalContent = document.getElementById('modalContent');
    const modalClassName = document.getElementById('modalClassName');
    const modalDateFormatted = document.getElementById('modalDateFormatted');
    const studentTableBody = document.getElementById('studentTableBody');
    const modalStudentSearch = document.getElementById('modalStudentSearch');

    let currentLoadedStudents = [];
    let activeStatusFilter = 'all';

    function renderStudentRows() {
        const searchQuery = (modalStudentSearch.value || '').trim().toLowerCase();
        
        const filtered = currentLoadedStudents.filter(s => {
            // Status filter
            if (activeStatusFilter !== 'all' && s.status !== activeStatusFilter) {
                return false;
            }
            // Search filter
            if (searchQuery) {
                const nameMatch = (s.fullname || '').toLowerCase().includes(searchQuery);
                const nisnMatch = (s.nip_nisn || '').toLowerCase().includes(searchQuery);
                const userMatch = (s.username || '').toLowerCase().includes(searchQuery);
                return nameMatch || nisnMatch || userMatch;
            }
            return true;
        });

        if (filtered.length === 0) {
            if (currentLoadedStudents.length === 0) {
                studentTableBody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-secondary"><i class="bi bi-people fs-2 d-block mb-2 text-muted"></i>Belum ada siswa yang terdaftar di kelas ' + modalClassName.textContent + '.<br><span class="small text-muted">Siswa dapat ditambahkan melalui menu <strong>Data Master &gt; Pengguna</strong> atau <strong>Impor Siswa</strong>.</span></td></tr>';
            } else {
                studentTableBody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-secondary">Tidak ada siswa yang sesuai dengan filter ini.</td></tr>';
            }
            return;
        }

        let html = '';
        filtered.forEach((s, idx) => {
            let statusText = s.status_label || '-';
            let ket = '-';
            if (s.attendances && s.attendances.length > 0) {
                ket = s.attendances.map(a => a.subject + (a.teacher ? ' (' + a.teacher + ')' : '')).join(', ');
            }

            html += `<tr>
                <td class="text-secondary">${idx + 1}</td>
                <td class="fw-semibold text-dark">${s.fullname}</td>
                <td class="text-secondary">${s.nip_nisn || s.username}</td>
                <td class="text-center text-dark">${statusText}</td>
                <td class="text-secondary small">${ket}</td>
            </tr>`;
        });
        studentTableBody.innerHTML = html;
    }

    // Status tab click handlers inside modal (Pastel Lembut)
    document.querySelectorAll('.filter-status-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.filter-status-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            activeStatusFilter = this.getAttribute('data-status');
            renderStudentRows();
        });
    });

    // Search input handler inside modal
    modalStudentSearch.addEventListener('input', renderStudentRows);

    document.querySelectorAll('.btn-show-detail').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const classId = this.getAttribute('data-class-id');
            const className = this.getAttribute('data-class-name');
            
            // Ambil tanggal langsung dari input filter waka
            const selectedDate = document.querySelector('input[name="date"]')?.value || "{{ $date }}";

            modalClassName.textContent = className;
            modalDateFormatted.textContent = selectedDate;
            modalLoading.style.display = 'block';
            modalContent.style.display = 'none';
            studentTableBody.innerHTML = '';
            modalStudentSearch.value = '';
            activeStatusFilter = 'all';

            // Reset filter tab styles
            document.querySelectorAll('.filter-status-btn').forEach(b => {
                b.classList.remove('active');
                if (b.getAttribute('data-status') === 'all') {
                    b.classList.add('active');
                }
            });

            detailModal.show();

            const recapBaseUrl = "{{ route('waka.attendances.recap', [], false) }}";
            fetch(recapBaseUrl + "/class/" + classId + "?date=" + encodeURIComponent(selectedDate), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                modalDateFormatted.textContent = data.date_formatted || selectedDate;
                
                // Populate summary badges
                document.getElementById('sumTotal').textContent = data.summary.total_students;
                document.getElementById('sumHadir').textContent = data.summary.hadir;
                document.getElementById('sumIzin').textContent = data.summary.izin;
                document.getElementById('sumSakit').textContent = data.summary.sakit;
                document.getElementById('sumAlpa').textContent = data.summary.alpa;
                document.getElementById('sumBelum').textContent = data.summary.belum_diabsen;

                // Journals block
                const journalBlock = document.getElementById('journalInfoBlock');
                const journalList = document.getElementById('journalList');
                journalList.innerHTML = '';
                if (data.journals && data.journals.length > 0) {
                    journalBlock.style.display = 'block';
                    data.journals.forEach(j => {
                        const li = document.createElement('li');
                        li.textContent = j.subject + (j.teacher ? ' (' + j.teacher + ')' : '') + (j.period_text ? ' — ' + j.period_text : '');
                        journalList.appendChild(li);
                    });
                } else {
                    journalBlock.style.display = 'none';
                }

                // Simpan data siswa & render
                currentLoadedStudents = data.students || [];
                renderStudentRows();

                modalLoading.style.display = 'none';
                modalContent.style.display = 'block';
            })
            .catch(err => {
                modalLoading.style.display = 'none';
                modalContent.style.display = 'block';
                studentTableBody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-danger">Gagal memuat data presensi. Silakan coba lagi.</td></tr>';
            });
        });
    });
});
</script>
@endpush

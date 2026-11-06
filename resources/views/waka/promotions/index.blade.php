@extends('layouts.app')

@section('title', 'Kenaikan Kelas & Kelulusan')

@section('content')
<!-- Header Page -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-3">
  <div>
    <h4 class="fw-bold mb-0 text-dark">Kenaikan Kelas & Kelulusan Siswa</h4>
  </div>
  <div class="d-flex gap-2">
    <a href="{{ route('waka.classes.index') }}" class="btn btn-outline-secondary btn-sm">
      Daftar Kelas
    </a>
  </div>
</div>

<!-- Stat Cards -->
<div class="row g-2 mb-4">
  <div class="col-12 col-md-4">
    <div class="card border shadow-xs h-100 p-2 text-center" style="border-left: 3px solid #2563eb !important;">
      <span class="text-secondary small fw-medium">Total Siswa Aktif</span>
      <span class="fw-bold text-primary fs-5">{{ number_format($totalActiveStudents) }}</span>
    </div>
  </div>
  <div class="col-12 col-md-4">
    <div class="card border shadow-xs h-100 p-2 text-center" style="border-left: 3px solid #2563eb !important;">
      <span class="text-secondary small fw-medium">Kelas Kosong (Siap Diisi)</span>
      <span class="fw-bold text-primary fs-5">{{ $emptyClassesCount }} <span class="text-muted fs-6 fw-normal">/ {{ $classes->count() }}</span></span>
    </div>
  </div>
  <div class="col-12 col-md-4">
    <div class="card border shadow-xs h-100 p-2 text-center" style="border-left: 3px solid #2563eb !important;">
      <span class="text-secondary small fw-medium">Kelas Terisi Siswa</span>
      <span class="fw-bold text-primary fs-5">{{ $occupiedClassesCount }}</span>
    </div>
  </div>
</div>

<!-- Workflow Procedures Text Only -->
<div class="mb-4">
  <h6 class="fw-bold text-dark mb-2">Alur & Prosedur Kenaikan Kelas:</h6>
  <div class="small text-secondary">
    <ul class="mb-0 ps-3">
      <li class="mb-1"><strong>Langkah 1 (Tingkat XII):</strong> Luluskan siswa kelas <strong>XII</strong> terlebih dahulu. Data siswa yang lulus <strong>tetap aman tersimpan</strong> di database (status Alumni), sehingga kelas XII menjadi kosong.</li>
      <li class="mb-1"><strong>Langkah 2 (Tingkat XI &rarr; XII):</strong> Setelah kelas XII kosong, siswa kelas <strong>XI</strong> dapat dinaikkan ke kelas <strong>XII</strong>.</li>
      <li class="mb-1"><strong>Langkah 3 (Tingkat X &rarr; XI):</strong> Setelah kelas XI kosong, siswa kelas <strong>X</strong> dapat dinaikkan ke kelas <strong>XI</strong>.</li>
      <li><span class="text-danger fw-semibold">Catatan Validasi:</span> Kelas tujuan <strong>HARUS KOSONG (0 siswa aktif)</strong>. Sistem secara otomatis menolak kenaikan apabila kelas tujuan masih terisi.</li>
    </ul>
  </div>
</div>

    <!-- Source Class Selector Card -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body p-3 p-md-4">
        <form method="GET" action="{{ route('waka.promotions.index') }}" id="filterForm">
          <label class="form-label fw-bold text-dark small mb-2">
            Pilih Kelas Asal Siswa:
          </label>
          <div class="row g-2 align-items-end">
            <div class="col-12 col-sm-4 col-md-3">
              <label class="form-label small fw-semibold text-secondary mb-1">Tingkat Kelas</label>
              <select name="grade_level" id="filterGradeLevel" class="form-select form-select-sm" required>
                <option value="">Semua Tingkat</option>
                @foreach($gradeLevels as $gl)
                  <option value="{{ $gl }}" {{ ($selectedGradeLevel == $gl) ? 'selected' : '' }}>{{ $gl }}</option>
                @endforeach
              </select>
            </div>

            <div class="col-12 col-sm-4 col-md-3">
              <label class="form-label small fw-semibold text-secondary mb-1">Jurusan</label>
              <select name="major" id="filterMajor" class="form-select form-select-sm" required>
                <option value="">Semua Jurusan</option>
                @foreach($majors as $m)
                  <option value="{{ $m }}" {{ ($selectedMajor == $m) ? 'selected' : '' }}>{{ $m }}</option>
                @endforeach
              </select>
            </div>

            <div class="col-12 col-sm-4 col-md-2">
              <label class="form-label small fw-semibold text-secondary mb-1">Rombel</label>
              <select name="section" id="filterSection" class="form-select form-select-sm" required>
                <option value="">Semua</option>
                @foreach($sections as $sec)
                  <option value="{{ $sec }}" {{ ($selectedSection == (string)$sec) ? 'selected' : '' }}>{{ $sec }}</option>
                @endforeach
              </select>
            </div>

            <div class="col-12 col-md-4 d-flex gap-2">
              <button type="submit" class="btn btn-sm btn-primary w-100 py-1.5">
                <i class="bi bi-search me-1"></i> Buka Data Kelas
              </button>
              @if($selectedClass || request()->hasAny(['grade_level', 'major', 'section', 'source_class_id']))
                <a href="{{ route('waka.promotions.index') }}" class="btn btn-sm btn-outline-secondary py-1.5" title="Reset">
                  <i class="bi bi-x-lg"></i>
                </a>
              @endif
            </div>
          </div>
        </form>
      </div>
    </div>

    @if($selectedClass)
      <!-- Detail Kelas Terpilih & Action Bar -->
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
          <div>
            <h5 class="fw-bold text-dark mb-0">{{ $selectedClass->class_name }}</h5>
            <small class="text-muted">Jurusan: <strong>{{ $selectedClass->major ?? '-' }}</strong> &bull; Rombel: <strong>{{ $selectedClass->section ?? '-' }}</strong></small>
          </div>

          <div class="d-flex align-items-center gap-3">
            <span class="text-muted small" id="selectedCountText">0 siswa dipilih</span>
            @if($selectedClass->grade_level == 'XII')
              <button type="button" class="btn btn-primary btn-sm px-3" onclick="openGraduationModal()">
                Luluskan Siswa
              </button>
            @else
              <button type="button" class="btn btn-primary btn-sm px-3" onclick="openPromotionModal()">
                Naikkan Kelas
              </button>
            @endif
          </div>
        </div>

        <div class="card-body p-3 p-md-4">
          @if($students->isEmpty())
            <!-- Kondisi Kelas Kosong -->
            <div class="text-center py-5">
              <h5 class="fw-bold text-dark mb-1">Kelas Ini Berstatus KOSONG</h5>
              <p class="text-muted small mb-0">Tidak ada siswa aktif di dalam kelas <strong>{{ $selectedClass->class_name }}</strong>. Kelas ini siap menerima kenaikan siswa dari kelas tingkat sebelumnya.</p>
            </div>
          @else
            <!-- Table Siswa -->
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th style="width: 45px;" class="text-center">
                      <input type="checkbox" class="form-check-input" id="checkAll" onchange="toggleSelectAll(this)">
                    </th>
                    <th style="width: 60px;">No</th>
                    <th>NIS / Username</th>
                    <th>Nama Lengkap Siswa</th>
                    <th>Status</th>
                    <th>Tingkat Sekarang</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($students as $idx => $st)
                    <tr>
                      <td class="text-center">
                        <input type="checkbox" class="form-check-input student-checkbox" name="student_item" value="{{ $st->id }}" onchange="updateSelectedCount()">
                      </td>
                      <td>{{ $idx + 1 }}</td>
                      <td>
                        <span class="font-monospace fw-semibold text-secondary">{{ $st->nip_nisn ?? $st->username }}</span>
                      </td>
                      <td>
                        <span class="fw-bold text-dark">{{ $st->fullname }}</span>
                      </td>
                      <td>
                        <span class="text-dark">Aktif</span>
                      </td>
                      <td>
                        <span class="text-dark">{{ $selectedClass->class_name }}</span>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
            
            <div class="mt-3 d-flex justify-content-between align-items-center">
              <div class="small text-muted">
                Menampilkan <strong>{{ $students->count() }}</strong> siswa di kelas ini.
              </div>
              <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="selectAllCheckbox(true)">
                  Pilih Semua (Select All)
                </button>
                @if($selectedClass->grade_level == 'XII')
                  <button type="button" class="btn btn-sm btn-primary" onclick="openGraduationModal()">
                    Luluskan Siswa
                  </button>
                @else
                  <button type="button" class="btn btn-sm btn-primary" onclick="openPromotionModal()">
                    Naikkan Kelas
                  </button>
                @endif
              </div>
            </div>
          @endif
        </div>
      </div>
    @else
      <div class="card border-0 shadow-sm text-center py-5">
        <div class="py-3">
          <h5 class="fw-bold text-dark">Silakan Pilih Kelas Asal Terlebih Dahulu</h5>
          <p class="text-muted small mb-0">Pilih salah satu kelas melalui menu dropdown di atas untuk memulai proses kenaikan atau kelulusan siswa.</p>
        </div>
      </div>
    @endif

@if($selectedClass)
<!-- ======================================================== -->
<!-- MODAL: KELULUSAN SISWA KELAS XII -->
<!-- ======================================================== -->
<div class="modal fade" id="graduationModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-3">
      <form action="{{ route('waka.promotions.graduate') }}" method="POST" id="graduateForm">
        @csrf
        <input type="hidden" name="source_class_id" value="{{ $selectedClass->id }}">
        <div id="graduateStudentIdsContainer"></div>

        <div class="modal-header border-0 bg-warning bg-opacity-10 pt-4 px-4 pb-2">
          <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle bg-warning text-dark d-flex align-items-center justify-content-center shadow-sm" style="width: 44px; height: 44px; font-size: 1.3rem;">
              <i class="bi bi-mortarboard-fill"></i>
            </div>
            <div>
              <h5 class="modal-title fw-bold text-dark mb-0">Konfirmasi Kelulusan Siswa</h5>
              <p class="text-muted small mb-0">Kelas: <strong>{{ $selectedClass->class_name }}</strong></p>
            </div>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body p-4">
          <div class="alert alert-success d-flex align-items-start gap-2 mb-3 py-2 px-3 small border-0 bg-success bg-opacity-10 text-success">
            <i class="bi bi-shield-check fs-5 mt-n1"></i>
            <div>
              <strong>Data Tetap Tersimpan:</strong> Siswa yang diluluskan tidak dihapus dari sistem. Data tersimpan sebagai Alumni SMK.
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-bold text-dark">Jumlah Siswa yang Akan Diluluskan:</label>
            <div class="fs-5 fw-bold text-primary" id="graduatingCountDisplay">0 Siswa</div>
          </div>

          <div class="mb-3">
            <label for="graduation_year" class="form-label small fw-bold text-dark">Tahun Ajaran Kelulusan <span class="text-danger">*</span></label>
            @php
              $currentYear = (int)date('Y');
              $defaultAcademicYear = ($currentYear - 1) . '/' . $currentYear;
            @endphp
            <input type="text" class="form-control" id="graduation_year" name="graduation_year" value="{{ $defaultAcademicYear }}" placeholder="Contoh: 2025/2026" required>
            <div class="form-text small">Masukkan tahun ajaran kelulusan untuk arsip alumni.</div>
          </div>

          <div class="p-3 bg-light rounded small text-secondary">
            <i class="bi bi-info-circle text-primary me-1"></i>
            Setelah diproses, kelas <strong>{{ $selectedClass->class_name }}</strong> akan menjadi <strong>KOSONG</strong> dan siap diisi oleh siswa kelas XI yang naik ke kelas XII.
          </div>
        </div>

        <div class="modal-footer border-0 bg-light p-3 d-flex justify-content-between">
          <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-warning fw-bold px-4">
            <i class="bi bi-check2-circle me-1"></i> Proses Kelulusan Sekarang
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ======================================================== -->
<!-- MODAL: KENAIKAN KELAS (X & XI) -->
<!-- ======================================================== -->
<div class="modal fade" id="promotionModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg rounded-3">
      <form action="{{ route('waka.promotions.promote') }}" method="POST" id="promoteForm">
        @csrf
        <input type="hidden" name="source_class_id" value="{{ $selectedClass->id }}">
        <div id="promoteStudentIdsContainer"></div>

        <div class="modal-header border-0 bg-primary bg-opacity-10 pt-4 px-4 pb-2">
          <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center shadow-sm" style="width: 44px; height: 44px; font-size: 1.3rem;">
              <i class="bi bi-arrow-up-right-circle-fill"></i>
            </div>
            <div>
              <h5 class="modal-title fw-bold text-dark mb-0">Pilih Kelas Tujuan Kenaikan</h5>
              <p class="text-muted small mb-0">Dari Kelas Asal: <strong>{{ $selectedClass->class_name }}</strong></p>
            </div>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-bold text-dark">Jumlah Siswa yang Akan Dinaikkan:</label>
            <div class="fs-5 fw-bold text-primary" id="promotingCountDisplay">0 Siswa</div>
          </div>

          <div class="mb-3">
            <label for="destinationClassSelect" class="form-label small fw-bold text-dark">
              Pilih Kelas Tujuan: <span class="text-danger">*</span>
            </label>
            <select name="destination_class_id" id="destinationClassSelect" class="form-select form-select-lg" required onchange="checkDestinationClassVacancy(this)">
              <option value="">-- Pilih Kelas Tujuan --</option>
              @foreach($classes as $c)
                @if($c->id != $selectedClass->id)
                  @php
                    $isSuggested = $suggestedDestClass && $suggestedDestClass->id == $c->id;
                    $isEmpty = $c->students_count == 0;
                  @endphp
                  <option value="{{ $c->id }}" 
                          data-count="{{ $c->students_count }}" 
                          data-name="{{ $c->class_name }}"
                          {{ $isSuggested ? 'selected' : '' }}>
                    {{ $c->class_name }} 
                    @if($isEmpty)
                      — [KOSONG - SIAP MENERIMA SISWA] 🟢
                    @else
                      — [TERISI {{ $c->students_count }} SISWA - BELUM KOSONG] 🔴
                    @endif
                    @if($isSuggested) (Rekomendasi Jalur Jurusan) @endif
                  </option>
                @endif
              @endforeach
            </select>
            <div class="form-text small">
              Pilih kelas dari daftar kelas yang tersedia.
            </div>
          </div>

          <!-- Dynamic Alert Status Ruangan / Kelas Tujuan -->
          <div id="destClassAlert"></div>

        </div>

        <div class="modal-footer border-0 bg-light p-3 d-flex justify-content-between">
          <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal">Batal</button>
          <button type="submit" id="btnSubmitPromotion" class="btn btn-primary fw-bold px-4">
            <i class="bi bi-arrow-up-circle me-1"></i> Proses Kenaikan Kelas
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endif

@push('scripts')
<script>
// Toggle Pilih Semua Siswa
function toggleSelectAll(masterCheckbox) {
  const checkboxes = document.querySelectorAll('.student-checkbox');
  checkboxes.forEach(cb => {
    cb.checked = masterCheckbox.checked;
  });
  updateSelectedCount();
}

function selectAllCheckbox(check) {
  const master = document.getElementById('checkAll');
  if (master) master.checked = check;
  toggleSelectAll({ checked: check });
}

function getSelectedStudentIds() {
  const checkboxes = document.querySelectorAll('.student-checkbox:checked');
  const ids = [];
  checkboxes.forEach(cb => ids.push(cb.value));
  return ids;
}

function updateSelectedCount() {
  const ids = getSelectedStudentIds();
  const textElem = document.getElementById('selectedCountText');
  if (textElem) {
    textElem.textContent = ids.length + ' siswa dipilih';
    if (ids.length > 0) {
      textElem.classList.add('fw-bold', 'text-primary');
      textElem.classList.remove('text-muted');
    } else {
      textElem.classList.remove('fw-bold', 'text-primary');
      textElem.classList.add('text-muted');
    }
  }

  // Update master checkbox indeterminate state
  const totalCheckboxes = document.querySelectorAll('.student-checkbox');
  const masterCheckbox = document.getElementById('checkAll');
  if (masterCheckbox && totalCheckboxes.length > 0) {
    if (ids.length === 0) {
      masterCheckbox.checked = false;
      masterCheckbox.indeterminate = false;
    } else if (ids.length === totalCheckboxes.length) {
      masterCheckbox.checked = true;
      masterCheckbox.indeterminate = false;
    } else {
      masterCheckbox.checked = false;
      masterCheckbox.indeterminate = true;
    }
  }
}

// Buka Modal Kelulusan
function openGraduationModal() {
  const ids = getSelectedStudentIds();
  if (ids.length === 0) {
    alert('Pilih minimal 1 siswa untuk diluluskan!');
    return;
  }

  // Update display count
  document.getElementById('graduatingCountDisplay').textContent = ids.length + ' Siswa';

  // Populate hidden inputs
  const container = document.getElementById('graduateStudentIdsContainer');
  container.innerHTML = '';
  ids.forEach(id => {
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'student_ids[]';
    input.value = id;
    container.appendChild(input);
  });

  const modal = new bootstrap.Modal(document.getElementById('graduationModal'));
  modal.show();
}

// Buka Modal Kenaikan Kelas
function openPromotionModal() {
  const ids = getSelectedStudentIds();
  if (ids.length === 0) {
    alert('Pilih minimal 1 siswa untuk dinaikkan kelasnya!');
    return;
  }

  // Update display count
  document.getElementById('promotingCountDisplay').textContent = ids.length + ' Siswa';

  // Populate hidden inputs
  const container = document.getElementById('promoteStudentIdsContainer');
  container.innerHTML = '';
  ids.forEach(id => {
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'student_ids[]';
    input.value = id;
    container.appendChild(input);
  });

  const select = document.getElementById('destinationClassSelect');
  checkDestinationClassVacancy(select);

  const modal = new bootstrap.Modal(document.getElementById('promotionModal'));
  modal.show();
}

// Validasi realtime status keterisian kelas tujuan
function checkDestinationClassVacancy(selectElem) {
  const selectedOption = selectElem.options[selectElem.selectedIndex];
  const alertContainer = document.getElementById('destClassAlert');
  const submitBtn = document.getElementById('btnSubmitPromotion');

  if (!selectedOption || !selectedOption.value) {
    alertContainer.innerHTML = '';
    submitBtn.disabled = true;
    return;
  }

  const studentCount = parseInt(selectedOption.getAttribute('data-count') || '0', 10);
  const className = selectedOption.getAttribute('data-name') || selectedOption.text;

  if (studentCount > 0) {
    // KELAS TUJUAN BELUM KOSONG: BLOKIR DAN BERI PERINGATAN KERAS
    alertContainer.innerHTML = `
      <div class="alert alert-danger border-0 d-flex align-items-start gap-2 mb-0">
        <i class="bi bi-x-circle-fill fs-5 mt-1 flex-shrink-0"></i>
        <div>
          <strong>Kelas Tujuan Belum Kosong!</strong><br>
          Kelas <strong>${className}</strong> saat ini masih memiliki <strong>${studentCount} siswa aktif</strong>.<br>
          <span class="small">Sesuai aturan kenaikan, data siswa di kelas <strong>${className}</strong> harus dikosongkan terlebih dahulu (diluluskan jika kelas XII, atau dinaikkan ke tingkat atasnya jika kelas XI).</span>
        </div>
      </div>
    `;
    submitBtn.disabled = true;
  } else {
    // KELAS TUJUAN KOSONG: SIAP DIPAKAI
    alertContainer.innerHTML = `
      <div class="alert alert-success border-0 d-flex align-items-center gap-2 mb-0">
        <i class="bi bi-check-circle-fill fs-5 flex-shrink-0"></i>
        <div>
          <strong>Kelas Tujuan Siap & Kosong!</strong><br>
          Kelas <strong>${className}</strong> saat ini kosong (0 siswa). Siswa dapat dipindahkan ke kelas ini.
        </div>
      </div>
    `;
    submitBtn.disabled = false;
  }
}

// Inisialisasi awal saat load
document.addEventListener('DOMContentLoaded', function () {
  updateSelectedCount();
});
</script>
@endpush
@endsection

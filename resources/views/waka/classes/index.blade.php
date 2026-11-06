@extends('layouts.app')

@section('title', 'Data Kelas')

@section('content')
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-3">
<div>
<h4 class="fw-bold mb-1 text-dark">Data Master Kelas</h4>
<p class="text-secondary small mb-0">Kelola daftar rombongan belajar / kelas dan penugasan wali kelas di sekolah.</p>
</div>
<div class="d-flex align-items-center gap-2">
  <button type="button" class="btn btn-outline-primary d-inline-flex align-items-center gap-1.5 shadow-sm" data-bs-toggle="modal" data-bs-target="#importWalasModal" title="Impor Data Penugasan Wali Kelas Massal">
    <i class="bi bi-file-earmark-arrow-up"></i>
    <span>Impor Walas Massal</span>
  </button>
  <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-1.5 shadow-sm" data-bs-toggle="modal" data-bs-target="#addClassModal">
    <i class="bi bi-plus-lg"></i>
    <span>Tambah Kelas</span>
  </button>
</div>
</div>

{{-- Alert Hasil Impor Walas Jika Ada Catatan/Error --}}
@if(session('walas_import_errors') && count(session('walas_import_errors')) > 0)
  <div class="alert alert-warning alert-dismissible fade show border-0 mb-4 shadow-sm" role="alert" style="background-color: #fffbeb; border-left: 4px solid #f59e0b !important; border-radius: 8px;">
    <div class="d-flex align-items-center mb-2">
      <i class="bi bi-exclamation-triangle-fill text-warning me-2 fs-5"></i>
      <h6 class="fw-bold text-dark mb-0">Catatan Hasil Impor Wali Kelas ({{ count(session('walas_import_errors')) }} Catatan)</h6>
    </div>
    <ul class="mb-0 small text-secondary ps-3" style="max-height: 160px; overflow-y: auto;">
      @foreach(session('walas_import_errors') as $err)
        <li>{{ $err }}</li>
      @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
@endif

{{-- Filter Dropdowns: Kelas, Jurusan, Rombel --}}
<div class="card mb-3 border shadow-sm">
  <div class="card-body p-3">
    <form method="GET" action="{{ route('waka.classes.index') }}" class="row g-2 align-items-center">
      <div class="col-12 col-md-3">
        <label class="form-label small fw-semibold text-secondary mb-1">Kelas</label>
        <select name="grade_level" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="">Semua Kelas</option>
          @foreach($gradeLevels as $g)
            <option value="{{ $g }}" {{ request('grade_level') == $g ? 'selected' : '' }}>Kelas {{ $g }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-12 col-md-3">
        <label class="form-label small fw-semibold text-secondary mb-1">Jurusan</label>
        <select name="major" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="">Semua Jurusan</option>
          @foreach($majors as $m)
            <option value="{{ $m }}" {{ request('major') == $m ? 'selected' : '' }}>{{ $m }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-12 col-md-3">
        <label class="form-label small fw-semibold text-secondary mb-1">Rombel</label>
        <select name="section" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="">Semua Rombel</option>
          @foreach($sections as $s)
            <option value="{{ $s }}" {{ request('section') == $s ? 'selected' : '' }}>Rombel {{ $s }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-12 col-md-3 d-flex align-items-end gap-2 pt-md-4">
        <button type="submit" class="btn btn-sm btn-primary flex-fill">
          <i class="bi bi-funnel"></i> Filter
        </button>
        @if(request()->hasAny(['grade_level', 'major', 'section', 'search']))
          <a href="{{ route('waka.classes.index') }}" class="btn btn-sm btn-outline-secondary" title="Reset Filter">
            <i class="bi bi-x-lg"></i> Reset
          </a>
        @endif
      </div>
    </form>
  </div>
</div>

<div class="card">
<div class="card-header d-flex justify-content-between align-items-center">
<span class="fw-bold text-dark">Daftar Kelas</span>
<span class="text-secondary small">Total: {{ $classes->total() }} Kelas</span>
</div>
<div class="card-body p-0">
<div class="table-responsive">
<table class="table table-hover align-middle mb-0">
<thead>
<tr>
<th style="width: 60px;">No</th>
<th>Kelas</th>
<th>Jurusan</th>
<th>Rombel</th>
<th>Wali Kelas</th>
<th>Jumlah Siswa</th>
<th>Total Jadwal</th>
<th style="width: 175px;" class="text-center">Aksi</th>
</tr>
</thead>
<tbody>
@forelse($classes as $index => $c)
<tr>
<td>{{ $classes->firstItem() + $index }}</td>
<td class="fw-bold text-dark">{{ $c->grade_level ?? '-' }}</td>
<td class="text-dark">{{ $c->major ?? '-' }}</td>
<td class="text-dark">{{ $c->section ?? '-' }}</td>
<td>
  @if($c->homeroomTeacher)
    <div class="d-flex align-items-center gap-1.5">
      <span class="text-dark fw-medium small">{{ $c->homeroomTeacher->fullname }}</span>
      <button type="button" class="btn btn-sm btn-link p-0 text-muted" data-bs-toggle="modal" data-bs-target="#assignWalasModal{{ $c->id }}" title="Ganti Wali Kelas">
        <i class="bi bi-pencil-square" style="font-size: 0.85rem;"></i>
      </button>
    </div>
  @else
    <button type="button" class="btn btn-sm btn-outline-warning text-dark py-0.5 px-2 d-inline-flex align-items-center gap-1" style="font-size: 0.78rem; border-radius: 6px;" data-bs-toggle="modal" data-bs-target="#assignWalasModal{{ $c->id }}">
      <i class="bi bi-person-plus"></i> Plotting Walas
    </button>
  @endif
</td>
<td>
<span class="text-dark">{{ $c->students_count }} Siswa</span>
</td>
<td>
<span class="text-dark">{{ $c->schedules_count }} Sesi Jadwal</span>
</td>
<td class="text-center">
<div class="d-flex align-items-center justify-content-center gap-1">
<button type="button" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1 rounded-2 shadow-none py-1 px-2" style="height: 31px; white-space: nowrap; font-size: 0.82rem;" data-bs-toggle="modal" data-bs-target="#manageStudentsModal{{ $c->id }}" title="Kelola Siswa">
<i class="bi bi-people-fill"></i> Siswa
</button>
<button type="button" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center justify-content-center rounded-2 shadow-none" style="width: 31px; height: 31px;" data-bs-toggle="modal" data-bs-target="#editClassModal{{ $c->id }}" title="Edit Kelas">
<i class="bi bi-pencil-square"></i>
</button>
<form action="{{ route('waka.classes.destroy', $c->id) }}" method="POST" onsubmit="return confirm('Hapus kelas ini? Semua jadwal yang terhubung ke kelas ini akan terhapus.');" class="d-inline m-0 p-0">
@csrf
@method('DELETE')
<button type="submit" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center justify-content-center rounded-2 shadow-none" style="width: 31px; height: 31px;" title="Hapus Kelas">
<i class="bi bi-trash"></i>
</button>
</form>
</div>
</td>
</tr>
@empty
<tr>
<td colspan="8" class="text-center py-4 text-muted">Belum ada data kelas.</td>
</tr>
@endforelse
</tbody>
</table>
</div>
</div>
@if($classes->hasPages())
<div class="card-footer bg-white border-top py-3">
<div class="d-flex justify-content-end">
{{ $classes->links('pagination::bootstrap-5') }}
</div>
</div>
@endif
</div>

<!-- Modals per Kelas (Kelola Siswa, Edit Kelas & Plotting Walas) -->
@foreach($classes as $c)
<!-- Modal Daftar Siswa & Hapus Siswa Keluar -->
<div class="modal fade" id="manageStudentsModal{{ $c->id }}" tabindex="-1" aria-labelledby="manageStudentsLabel{{ $c->id }}" aria-hidden="true">
<div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
<div class="modal-content border shadow-sm" style="border-radius: 12px; overflow: hidden;">
<div class="modal-header bg-white border-bottom py-3 px-4">
<div>
<h5 class="modal-title fw-bold text-dark mb-1" id="manageStudentsLabel{{ $c->id }}">Daftar Siswa - {{ $c->class_name }}</h5>
<p class="text-secondary small mb-0">Hapus siswa dari kelas jika siswa keluar atau pindah sekolah</p>
</div>
<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body p-4">
@if($c->students->count() > 0)
<div class="table-responsive">
<table class="table table-sm table-hover align-middle mb-0">
<thead class="table-light">
<tr>
<th style="width: 50px;">No</th>
<th>NIS</th>
<th>Nama Lengkap Siswa</th>
<th>Status</th>
<th style="width: 100px;" class="text-center">Aksi</th>
</tr>
</thead>
<tbody>
@foreach($c->students as $sIdx => $st)
<tr>
<td>{{ $sIdx + 1 }}</td>
<td class="fw-semibold text-primary">{{ $st->nip_nisn ?? $st->username }}</td>
<td class="text-dark">{{ $st->fullname }}</td>
<td class="text-success fw-semibold">{{ ucfirst($st->student_status ?? 'Aktif') }}</td>
<td class="text-center">
<form action="{{ route('waka.classes.remove-student', $st->id) }}" method="POST" onsubmit="return confirm('Hapus siswa {{ $st->fullname }} dari kelas {{ $c->class_name }}? Akun siswa ini akan dinonaktifkan/dihapus.');" class="d-inline">
@csrf
@method('DELETE')
<button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size: 0.78rem;" title="Hapus Siswa Keluar">
<i class="bi bi-person-x me-1"></i> Keluarkan
</button>
</form>
</td>
</tr>
@endforeach
</tbody>
</table>
</div>
@else
<div class="text-center py-5">
<i class="bi bi-people text-muted opacity-50 display-6 d-block mb-2"></i>
<div class="fw-semibold text-dark">Belum Ada Siswa Terdaftar</div>
<p class="small text-secondary mb-0">Daftar siswa untuk kelas {{ $c->class_name }} masih kosong.</p>
</div>
@endif
</div>
<div class="modal-footer bg-light border-top py-3 px-4">
<button type="button" class="btn btn-secondary px-4 py-2" data-bs-dismiss="modal">Tutup</button>
</div>
</div>
</div>
</div>

<!-- Modal Edit Kelas -->
<div class="modal fade" id="editClassModal{{ $c->id }}" tabindex="-1" aria-hidden="true">
<div class="modal-dialog">
<div class="modal-content">
<div class="modal-header">
<h6 class="modal-title fw-bold">Edit Kelas</h6>
<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<form action="{{ route('waka.classes.update', $c->id) }}" method="POST">
@csrf
@method('PUT')
<div class="modal-body">
<div class="row g-2">
<div class="col-4">
<label class="form-label small fw-semibold text-dark">Tingkat</label>
<input type="text" name="grade_level" class="form-control form-control-sm" value="{{ $c->grade_level }}" placeholder="Contoh: XII" required>
</div>
<div class="col-4">
<label class="form-label small fw-semibold text-dark">Jurusan</label>
<input type="text" name="major" class="form-control form-control-sm" value="{{ $c->major }}" placeholder="Contoh: RPL" required>
</div>
<div class="col-4">
<label class="form-label small fw-semibold text-dark">Rombel</label>
<input type="text" name="section" class="form-control form-control-sm" value="{{ $c->section }}" placeholder="Contoh: 1" required>
</div>
</div>
</div>
<div class="modal-footer">
<button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
<button type="submit" class="btn btn-sm btn-primary">Simpan Perubahan</button>
</div>
</form>
</div>
</div>
</div>

<!-- Modal Plotting / Ganti Wali Kelas -->
<div class="modal fade" id="assignWalasModal{{ $c->id }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border shadow-sm" style="border-radius: 12px; overflow: hidden;">
      <div class="modal-header bg-white border-bottom py-3 px-4">
        <div>
          <h6 class="modal-title fw-bold text-dark mb-0">Plotting Wali Kelas - {{ $c->class_name }}</h6>
          <span class="text-muted small">Tentukan guru yang menjadi pembina/wali kelas untuk rombel ini.</span>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('waka.classes.assign-walas', $c->id) }}" method="POST">
        @csrf
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold text-dark mb-1">Pilih Guru sebagai Wali Kelas</label>
            <select name="teacher_id" class="form-select form-select-sm" style="border-radius: 8px;">
              <option value="">-- Kosongkan / Belum Ada Walas --</option>
              @foreach($teachers as $t)
                <option value="{{ $t->id }}" {{ ($c->homeroomTeacher && $c->homeroomTeacher->id == $t->id) ? 'selected' : '' }}>
                  {{ $t->fullname }} {{ $t->nip_nisn ? '(' . $t->nip_nisn . ')' : '' }}
                  @if($t->role_id == 6 && (!$c->homeroomTeacher || $c->homeroomTeacher->id != $t->id))
                    (Walas {{ $t->schoolClass->class_name ?? 'Lain' }})
                  @endif
                </option>
              @endforeach
            </select>
            <div class="form-text small text-secondary mt-1">
              Guru yang dipilih akan otomatis mendapatkan hak akses akun peran <strong>Wali Kelas</strong> untuk kelas ini.
            </div>
          </div>
        </div>
        <div class="modal-footer bg-light border-top py-2.5 px-4">
          <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-sm btn-primary">Simpan Wali Kelas</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endforeach

<!-- Modal Tambah Kelas -->
<div class="modal fade" id="addClassModal" tabindex="-1" aria-hidden="true">
<div class="modal-dialog">
<div class="modal-content">
<div class="modal-header">
<h6 class="modal-title fw-bold">Tambah Kelas Baru</h6>
<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<form action="{{ route('waka.classes.store') }}" method="POST">
@csrf
<div class="modal-body">
<div class="row g-2">
<div class="col-4">
<label class="form-label small fw-semibold text-dark">Tingkat</label>
<input type="text" id="add_grade_level" name="grade_level" class="form-control form-control-sm" placeholder="Contoh: XII" value="{{ old('grade_level') }}" required>
</div>
<div class="col-4">
<label class="form-label small fw-semibold text-dark">Jurusan</label>
<input type="text" id="add_major" name="major" class="form-control form-control-sm" placeholder="Contoh: RPL" value="{{ old('major') }}" required>
</div>
<div class="col-4">
<label class="form-label small fw-semibold text-dark">Rombel</label>
<input type="text" id="add_section" name="section" class="form-control form-control-sm" placeholder="Contoh: 1" value="{{ old('section') }}" required>
</div>
</div>
</div>
<div class="modal-footer">
<button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
<button type="submit" class="btn btn-sm btn-primary">Simpan Kelas</button>
</div>
</form>
</div>
</div>
</div>

<!-- Modal Impor Walas Massal -->
<div class="modal fade" id="importWalasModal" tabindex="-1" aria-labelledby="importWalasLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border shadow-sm" style="border-radius: 12px; overflow: hidden;">
      <div class="modal-header bg-white border-bottom py-3 px-4">
        <div>
          <h5 class="modal-title fw-bold text-dark mb-0" id="importWalasLabel">Impor Wali Kelas Massal</h5>
          <span class="text-muted small">Mendukung format file Excel (.xlsx, .xls, .csv), Word (.docx), atau PDF.</span>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('waka.classes.import-walas') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="modal-body p-4">
          <div class="alert alert-info border-0 mb-3 small" style="background-color: #eff6ff; color: #1e40af; border-radius: 8px;">
            <div class="fw-semibold mb-1"><i class="bi bi-info-circle me-1"></i> Format Fleksibel:</div>
            Sistem otomatis mengenali tabel yang memuat kolom <strong>Kelas</strong> (contoh: <code>X AK1</code>, <code>X TKJ 2</code>, <code>XII RPL 1</code>) dan nama <strong>Wali Kelas</strong> (baik dengan gelar maupun tanpa gelar).
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold text-dark mb-1">Pilih File Dokumen <span class="text-danger">*</span></label>
            <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv,.docx,.pdf" required>
            <div class="form-text small text-secondary">
              Format yang didukung: Excel (.xlsx, .xls, .csv), Word (.docx), PDF. Maksimal 10 MB.
            </div>
          </div>
        </div>
        <div class="modal-footer bg-light border-top py-2.5 px-4">
          <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1.5">
            <i class="bi bi-cloud-arrow-up"></i> Proses Impor
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

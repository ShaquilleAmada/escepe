@extends('layouts.app')

@section('title', 'Manajemen Pengguna')

@section('content')
<div class="d-flex flex-column flex-md-row align-items-md-start justify-content-between mb-4 gap-3">
  <div>
    <h1 class="fw-bold mb-1 text-dark heading-section" style="font-size: 1.5rem;">Manajemen Pengguna</h1>
    <p class="text-secondary small mb-0">Kelola akun seluruh pengguna sistem (Waka Kurikulum, Guru Mapel, Siswa, Wali Kelas, BK, dan Orang Tua).</p>
  </div>
  {{-- Tombol Aksi Utama --}}
  <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-2 flex-shrink-0">
    {{-- Tombol Impor Pengguna --}}
    <button type="button" class="btn btn-outline-primary d-inline-flex align-items-center justify-content-center gap-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#importUserModal" title="Impor Data Pengguna dari File Excel, Word, atau PDF">
      <i class="bi bi-file-earmark-arrow-up"></i>
      <span>Impor Pengguna</span>
    </button>

    {{-- Tombol Tambah Pengguna --}}
    <button type="button" class="btn btn-primary d-inline-flex align-items-center justify-content-center gap-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
      <i class="bi bi-person-plus-fill"></i>
      <span>Tambah Pengguna</span>
    </button>
  </div>
</div>

{{-- Alert Rincian Catatan / Error Impor --}}
@if(session('import_errors') && count(session('import_errors')) > 0)
  <div class="alert alert-warning alert-dismissible fade show border-0 mb-4 shadow-sm" role="alert" style="background-color: #fffbeb; border-left: 4px solid #f59e0b !important; border-radius: 8px;">
    <div class="d-flex align-items-center mb-2">
      <i class="bi bi-exclamation-triangle-fill text-warning me-2 fs-5"></i>
      <h6 class="fw-bold text-dark mb-0">Catatan Hasil Impor Pengguna ({{ count(session('import_errors')) }} Catatan)</h6>
    </div>
    <ul class="mb-0 small text-secondary ps-3" style="max-height: 160px; overflow-y: auto;">
      @foreach(session('import_errors') as $err)
        <li>{{ $err }}</li>
      @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
@endif

{{-- Stat Pills: Ringkasan Jumlah Pengguna per Role (Walas & BK, Siswa & Ortu) --}}
<div class="row row-cols-2 row-cols-sm-3 row-cols-md-5 g-2 mb-4">
  <div class="col">
    <div class="card border shadow-xs h-100 p-2 text-center" style="border-left: 3px solid #2563eb !important;">
      <span class="text-secondary small fw-medium">Total Akun</span>
      <span class="fw-bold text-primary fs-5">{{ $counts['total'] }}</span>
    </div>
  </div>
  <div class="col">
    <div class="card border shadow-xs h-100 p-2 text-center" style="border-left: 3px solid #2563eb !important;">
      <span class="text-secondary small fw-medium">Waka / Admin</span>
      <span class="fw-bold text-primary fs-5">{{ $counts['waka'] }}</span>
    </div>
  </div>
  <div class="col">
    <div class="card border shadow-xs h-100 p-2 text-center" style="border-left: 3px solid #2563eb !important;">
      <span class="text-secondary small fw-medium">Guru Mapel</span>
      <span class="fw-bold text-primary fs-5">{{ $counts['guru'] }}</span>
    </div>
  </div>
  <div class="col">
    <div class="card border shadow-xs h-100 p-2 text-center" style="border-left: 3px solid #2563eb !important;">
      <span class="text-secondary small fw-medium">Walas &amp; BK</span>
      <span class="fw-bold text-primary fs-5">{{ $counts['walas'] + $counts['bk'] }}</span>
    </div>
  </div>
  <div class="col">
    <div class="card border shadow-xs h-100 p-2 text-center" style="border-left: 3px solid #2563eb !important;">
      <span class="text-secondary small fw-medium">Siswa &amp; Ortu</span>
      <span class="fw-bold text-primary fs-5">{{ $counts['siswa'] + $counts['ortu'] }}</span>
    </div>
  </div>
</div>

{{-- Toolbar Filter & Pencarian --}}
<div class="card border shadow-sm mb-4">
  <div class="card-body p-3">
    <form method="GET" action="{{ route('waka.users.index') }}" class="row g-2 align-items-end">
      {{-- Input Pencarian --}}
      <div class="col-12 col-md-4">
        <label class="form-label small fw-semibold text-secondary mb-1">Cari Nama / Username / Email</label>
        <div class="input-group input-group-sm">
          <span class="input-group-text bg-white text-secondary"><i class="bi bi-search"></i></span>
          <input type="text" name="search" class="form-control" placeholder="Ketik kata kunci..." value="{{ request('search') }}">
        </div>
      </div>

      {{-- Filter Role --}}
      <div class="col-6 col-md-3">
        <label class="form-label small fw-semibold text-secondary mb-1">Filter Peran (Role)</label>
        <select name="role_id" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="">Semua Role (Semua Pengguna)</option>
          @foreach($roles as $role)
            <option value="{{ $role->id }}" {{ request('role_id') == $role->id ? 'selected' : '' }}>
              @if($role->id == 1) Waka Kurikulum (Admin)
              @elseif($role->id == 2) Guru Mapel
              @elseif($role->id == 3) Siswa
              @elseif($role->id == 4) Orang Tua
              @elseif($role->id == 5) Bimbingan Konseling (BK)
              @elseif($role->id == 6) Wali Kelas (Walas)
              @else {{ $role->role_name }}
              @endif
            </option>
          @endforeach
        </select>
      </div>

      {{-- Filter Kelas --}}
      <div class="col-6 col-md-3">
        <label class="form-label small fw-semibold text-secondary mb-1">Filter Kelas</label>
        <select name="class_id" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="">Semua Kelas</option>
          @foreach($classes as $c)
            <option value="{{ $c->id }}" {{ request('class_id') == $c->id ? 'selected' : '' }}>
              {{ $c->class_name }}
            </option>
          @endforeach
        </select>
      </div>

      {{-- Tombol Aksi Filter --}}
      <div class="col-12 col-md-2 d-flex gap-2">
        <button type="submit" class="btn btn-sm btn-primary flex-fill">
          Filter
        </button>
        @if(request()->hasAny(['search', 'role_id', 'class_id']))
          <a href="{{ route('waka.users.index') }}" class="btn btn-sm btn-outline-secondary" title="Reset Filter">
            <i class="bi bi-arrow-counterclockwise"></i>
          </a>
        @endif
      </div>
    </form>
  </div>
</div>

{{-- Tabel Daftar Pengguna --}}
<div class="card border shadow-sm mb-4">
  <div class="card-header bg-white py-3 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
    <div class="d-flex align-items-center gap-2">
      <i class="bi bi-people text-primary fs-5"></i>
      <h2 class="fw-bold text-dark mb-0" style="font-size: 1.15rem; letter-spacing: -0.01em;">Daftar Akun Pengguna</h2>
    </div>
    <span class="text-secondary small">{{ $users->total() }} Pengguna Terdaftar</span>
  </div>

  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th style="width: 45px;" class="ps-4">No</th>
            <th>Nama Lengkap</th>
            <th style="min-width: 175px;">Email</th>
            <th style="width: 140px;">Username</th>
            <th style="width: 155px;">NIP / NIS</th>
            <th style="width: 145px;">Role / Peran</th>
            <th style="width: 120px;">Kelas</th>
            <th style="width: 110px;" class="text-center pe-4">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse($users as $index => $u)
            <tr>
              <td class="ps-4 text-secondary small">{{ $users->firstItem() + $index }}</td>
              <td>
                <div class="fw-semibold text-dark" style="font-size: 0.9rem;">
                  {{ $u->fullname }}
                  @if($u->id === Auth::id())
                    <span class="text-primary small fw-semibold ms-1" style="font-size: 0.8rem;">(Akun Anda)</span>
                  @endif
                </div>
              </td>
              <td>
                @if($u->email)
                  <span class="text-dark font-mono small" style="font-size: 0.82rem;">
                    {{ $u->email }}
                  </span>
                @else
                  <span class="text-muted small">-</span>
                @endif
              </td>
              <td>
                <span class="font-mono text-dark fw-medium" style="font-size: 0.85rem;">
                  {{ $u->username }}
                </span>
              </td>
              <td>
                @if($u->nip_nisn)
                  <span class="font-mono text-dark" style="font-size: 0.85rem;">{{ $u->nip_nisn }}</span>
                @else
                  <span class="text-secondary small">-</span>
                @endif
              </td>
              <td>
                @php
                  $roleName = match($u->role_id) {
                      1 => 'Waka (Admin)',
                      2 => 'Guru Mapel',
                      3 => 'Siswa',
                      4 => 'Orang Tua',
                      5 => 'BK',
                      6 => 'Wali Kelas',
                      default => $u->role->role_name ?? 'User',
                  };
                @endphp
                <span class="fw-semibold text-dark" style="font-size: 0.875rem;">
                  {{ $roleName }}
                </span>
                @if($u->role_id == 4 && $u->parent)
                  <div class="text-secondary small mt-0.5" style="font-size: 0.75rem;">
                    Anak: {{ $u->parent->fullname }}
                  </div>
                @endif
              </td>
              <td>
                @if($u->schoolClass)
                  <span class="fw-medium text-dark" style="font-size: 0.85rem;">
                    {{ $u->schoolClass->class_name }}
                  </span>
                @else
                  <span class="text-secondary small">-</span>
                @endif
              </td>
              <td class="text-center pe-4">
                <div class="d-flex align-items-center justify-content-center gap-1">
                  {{-- Tombol Edit --}}
                  <button type="button" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center justify-content-center rounded-2 shadow-none" style="width: 32px; height: 32px;" data-bs-toggle="modal" data-bs-target="#editUserModal{{ $u->id }}" title="Edit Pengguna">
                    <i class="bi bi-pencil-square"></i>
                  </button>

                  {{-- Tombol Hapus --}}
                  @if($u->id !== Auth::id())
                    <form action="{{ route('waka.users.destroy', $u->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun pengguna \'{{ $u->fullname }}\'?')" class="d-inline m-0 p-0">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center justify-content-center rounded-2 shadow-none" style="width: 32px; height: 32px;" title="Hapus Pengguna">
                        <i class="bi bi-trash"></i>
                      </button>
                    </form>
                  @else
                    <button type="button" class="btn btn-sm btn-light border text-muted d-inline-flex align-items-center justify-content-center rounded-2" style="width: 32px; height: 32px;" disabled title="Akun Anda sedang aktif">
                      <i class="bi bi-lock-fill"></i>
                    </button>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="text-center py-5 text-muted">
                <i class="bi bi-person-x d-block fs-1 mb-2 text-secondary opacity-50"></i>
                <span>Tidak ada data pengguna yang sesuai dengan pencarian atau filter.</span>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    {{-- Pagination (Menggunakan div padding + border-top tanpa card-footer) --}}
    @if($users->hasPages())
      <div class="px-4 py-3 border-top d-flex justify-content-end bg-white">
        {{ $users->links('pagination::bootstrap-5') }}
      </div>
    @endif
  </div>
</div>

{{-- ============================================================
     MODAL IMPOR PENGGUNA (Excel, Word DOCX, PDF)
============================================================ --}}
<div class="modal fade" id="importUserModal" tabindex="-1" aria-labelledby="importUserModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden;">
      <div class="modal-header border-bottom bg-light py-3 px-4">
        <div class="d-flex align-items-center gap-2">
          <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
            <i class="bi bi-file-earmark-arrow-up fs-5"></i>
          </div>
          <div>
            <h5 class="modal-title fw-bold text-dark fs-6 mb-0" id="importUserModalLabel">
              Impor Data Akun Pengguna
            </h5>
            <span class="text-secondary small" style="font-size: 0.78rem;">Mendukung format file Excel (.xlsx, .xls, .csv), Word (.docx), dan PDF (.pdf)</span>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form action="{{ route('waka.users.import') }}" method="POST" enctype="multipart/form-data" id="formImportUser">
        @csrf
        <div class="modal-body p-4">
          {{-- Kartu Petunjuk & Ketentuan --}}
          <div class="card bg-light border-0 mb-4 p-3 rounded-3" style="background-color: #f8fafc !important; border-left: 3px solid #2563eb !important;">
            <div class="d-flex align-items-start gap-2">
              <i class="bi bi-info-circle-fill text-primary mt-0.5 fs-5"></i>
              <div class="small flex-grow-1">
                <span class="fw-bold text-dark d-block mb-1">Ketentuan Format Data &amp; Pembuatan Akun:</span>
                <ol class="ps-3 mb-2 text-secondary" style="line-height: 1.6;">
                  <li>Kolom data mencakup: <strong>No</strong>, <strong>Nama Lengkap</strong>, <strong>Email</strong>, <strong>Username</strong>, <strong>NIP / NIS</strong>, <strong>Role / Peran</strong>, <strong>Kelas</strong>, dan <strong>Keterangan Tambahan</strong>.</li>
                  <li><strong>Guru Pengajar Merangkap Walas</strong>: Role tetap diisi <em>Guru Mapel</em>, kolom Kelas diisi kelas bimbingannya (contoh: <code>XII RPL 2</code>), dan Keterangan: <code>Wali Kelas XII RPL 2</code>.</li>
                  <li><strong>Guru BK (Bimbingan Konseling)</strong>: Role tetap diisi <em>BK</em>. Jika mengajar di kelas tertentu, isi kolom Kelas dengan kelas yang diajar (contoh: <code>X RPL 1</code>) dan Keterangan: <code>Guru BK (Mengajar Kelas X RPL 1)</code>. Jika konseling murni (tidak mengajar), isi kelas dengan <code>-</code>.</li>
                  <li><strong>Siswa &amp; Orang Tua</strong>: Siswa mengisi kolom NIP/NIS dengan NIS siswa (4-6 digit). Akun Orang Tua otomatis dibuatkan terpisah dengan username <code>P[NIS]</code> dan terhubung ke siswa.</li>
                  <li>Sistem otomatis membuatkan akun baru dengan password default: <strong class="text-primary font-mono">password123</strong>. Pengguna dapat mengubahnya mandiri setelah masuk.</li>
                  <li>Batas ukuran dokumen: <strong>Maksimal 10 MB</strong> (Excel, Word, atau PDF).</li>
                </ol>
              </div>
            </div>
          </div>

          {{-- Area Dropzone / Upload File --}}
          <div class="mb-3">
            <label for="import_file_input" class="form-label small fw-semibold text-dark mb-1">
              Pilih Dokumen File <span class="text-danger">*</span>
            </label>
            <div class="border border-2 border-dashed rounded-3 p-4 text-center" id="dropzoneContainer" style="border-color: #cbd5e1 !important; background-color: #fcfdfe; transition: all 0.2s ease;">
              <i class="bi bi-cloud-arrow-up display-5 text-primary opacity-75 mb-2 d-block"></i>
              <p class="mb-1 fw-semibold text-dark" style="font-size: 0.95rem;">Seret &amp; letakkan file di sini, atau klik untuk memilih file</p>
              <p class="text-secondary small mb-3" style="font-size: 0.8rem;">Format: Excel (.xlsx, .xls, .csv), Word (.docx), PDF (.pdf) &bull; Maks 10 MB</p>
              
              <input type="file" name="file" id="import_file_input" class="d-none" accept=".xlsx,.xls,.csv,.docx,.pdf" required onchange="handleFileSelected(this)">
              <button type="button" class="btn btn-sm btn-outline-primary px-3 shadow-none" onclick="document.getElementById('import_file_input').click()">
                <i class="bi bi-folder2-open me-1"></i> Telusuri File Dokumen
              </button>
            </div>

            {{-- File Selected Feedback --}}
            <div id="fileSelectedFeedback" class="d-none mt-2 alert alert-light border d-flex align-items-center justify-content-between p-2 rounded-2 mb-0">
              <div class="d-flex align-items-center gap-2 text-truncate">
                <i class="bi bi-file-earmark-check text-success fs-5"></i>
                <div class="text-truncate">
                  <div class="fw-semibold text-dark small text-truncate" id="selectedFileName">-</div>
                  <div class="text-secondary font-mono" style="font-size: 0.75rem;" id="selectedFileSize">-</div>
                </div>
              </div>
              <button type="button" class="btn btn-sm text-danger shadow-none p-1" onclick="clearSelectedFile()" title="Hapus file terpilih">
                <i class="bi bi-x-circle-fill fs-5"></i>
              </button>
            </div>
          </div>
        </div>

        <div class="modal-footer bg-light py-2.5 px-4 border-top d-flex justify-content-between">
          <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-sm btn-primary px-4 d-inline-flex align-items-center gap-2 shadow-sm" id="btnSubmitImport">
            <i class="bi bi-upload"></i>
            <span>Mulai Impor Akun</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- ============================================================
     MODAL TAMBAH PENGGUNA
============================================================ --}}

<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow" style="border-radius: 12px; overflow: hidden;">
      <div class="modal-header border-bottom bg-light py-3 px-4">
        <h5 class="modal-title fw-bold text-dark fs-6" id="addUserModalLabel">
          <i class="bi bi-person-plus text-primary me-1"></i> Tambah Akun Pengguna Baru
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form action="{{ route('waka.users.store') }}" method="POST">
        @csrf
        <div class="modal-body p-4">
          {{-- Peran / Role --}}
          <div class="mb-3">
            <label for="create_role_id" class="form-label small fw-semibold text-dark">
              Peran / Role Pengguna <span class="text-danger">*</span>
            </label>
            <select name="role_id" id="create_role_id" class="form-select form-select-sm" required onchange="toggleRoleFields('create')">
              <option value="">-- Pilih Peran / Role --</option>
              @foreach($roles as $role)
                <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
                  @if($role->id == 1) Waka Kurikulum (Admin)
                  @elseif($role->id == 2) Guru Mapel
                  @elseif($role->id == 3) Siswa
                  @elseif($role->id == 4) Orang Tua
                  @elseif($role->id == 5) Bimbingan Konseling (BK)
                  @elseif($role->id == 6) Wali Kelas (Walas)
                  @else {{ $role->role_name }}
                  @endif
                </option>
              @endforeach
            </select>
          </div>

          {{-- Nama Lengkap --}}
          <div class="mb-3">
            <label for="create_fullname" class="form-label small fw-semibold text-dark">
              Nama Lengkap <span class="text-danger">*</span>
            </label>
            <input type="text" name="fullname" id="create_fullname" class="form-control form-control-sm" placeholder="Contoh: Budi Santoso, S.Pd." value="{{ old('fullname') }}" required>
          </div>

          {{-- NIP / NIS --}}
          <div class="mb-3">
            <label for="create_nip_nisn" class="form-label small fw-semibold text-dark" id="create_nip_nisn_label">
              NIP / NIS <span class="text-secondary small fw-normal">(Opsional)</span>
            </label>
            <input type="text" name="nip_nisn" id="create_nip_nisn" class="form-control form-control-sm font-mono" placeholder="Contoh: 198501012010011001 (NIP) atau 5412 (NIS)" value="{{ old('nip_nisn') }}">
            <div class="form-text small" style="font-size: 0.74rem;">NIP untuk Guru / Pegawai (Waka, Walas, BK). NIS untuk Siswa. Akun Orang Tua otomatis dibuat terpisah dengan format P + NIS.</div>
          </div>

          {{-- Username --}}
          <div class="mb-3">
            <label for="create_username" class="form-label small fw-semibold text-dark">
              Username <span class="text-muted fw-normal small">(Opsional - otomatis nama_pertama_nama_kedua jika dikosongkan)</span>
            </label>
            <input type="text" name="username" id="create_username" class="form-control form-control-sm font-mono" placeholder="Kosongkan untuk otomatis (nama_pertama_nama_kedua)" value="{{ old('username') }}">
            <div class="form-text small" style="font-size: 0.74rem;">Digunakan saat login ke sistem aplikasi atau web.</div>
          </div>

          {{-- Email --}}
          <div class="mb-3">
            <label for="create_email" class="form-label small fw-semibold text-dark">
              Email (Opsional)
            </label>
            <input type="email" name="email" id="create_email" class="form-control form-control-sm" placeholder="Contoh: budi@smkn1bantul.sch.id" value="{{ old('email') }}">
            <div class="form-text small" style="font-size: 0.74rem;">Digunakan untuk fitur reset password via OTP.</div>
          </div>

          {{-- Password --}}
          <div class="mb-3">
            <label for="create_password" class="form-label small fw-semibold text-dark">
              Password <span class="text-danger">*</span>
            </label>
            <input type="password" name="password" id="create_password" class="form-control form-control-sm" placeholder="Minimal 6 karakter" required minlength="6">
          </div>

          {{-- Kelas (Tampil khusus untuk Siswa / Walas) --}}
          <div class="mb-3" id="create_class_container">
            <label for="create_class_id" class="form-label small fw-semibold text-dark">
              Kelas / Rombel
            </label>
            <select name="class_id" id="create_class_id" class="form-select form-select-sm">
              <option value="">-- Pilih Kelas (Opsional/Bila Siswa &amp; Walas) --</option>
              @foreach($classes as $c)
                <option value="{{ $c->id }}" {{ old('class_id') == $c->id ? 'selected' : '' }}>
                  {{ $c->class_name }}
                </option>
              @endforeach
            </select>
            <div class="form-text small" style="font-size: 0.74rem;">Wajib dipilih untuk peran Siswa atau Wali Kelas.</div>
          </div>

          {{-- Hubungan Siswa Anak (Tampil khusus untuk Orang Tua) --}}
          <div class="mb-3 d-none" id="create_parent_container">
            <label for="create_parent_id" class="form-label small fw-semibold text-dark">
              Siswa Terhubung (Anak)
            </label>
            <select name="parent_id" id="create_parent_id" class="form-select form-select-sm">
              <option value="">-- Pilih Siswa Anak --</option>
              @foreach($students as $st)
                <option value="{{ $st->id }}" {{ old('parent_id') == $st->id ? 'selected' : '' }}>
                  {{ $st->fullname }} ({{ $st->schoolClass->class_name ?? 'Belum ada kelas' }})
                </option>
              @endforeach
            </select>
            <div class="form-text small" style="font-size: 0.74rem;">Pilih data siswa yang merupakan anak dari akun orang tua ini.</div>
          </div>
        </div>

        <div class="modal-footer bg-light py-2 px-4 border-top">
          <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-sm btn-primary px-3">
            <i class="bi bi-save me-1"></i> Simpan Pengguna
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- ============================================================
     MODALS EDIT PENGGUNA
============================================================ --}}
@foreach($users as $u)
<div class="modal fade" id="editUserModal{{ $u->id }}" tabindex="-1" aria-labelledby="editUserModalLabel{{ $u->id }}" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow" style="border-radius: 12px; overflow: hidden;">
      <div class="modal-header border-bottom bg-light py-3 px-4">
        <h5 class="modal-title fw-bold text-dark fs-6" id="editUserModalLabel{{ $u->id }}">
          <i class="bi bi-pencil-square text-primary me-1"></i> Edit Akun: {{ $u->fullname }}
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form action="{{ route('waka.users.update', $u->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="modal-body p-4">
          {{-- Peran / Role --}}
          <div class="mb-3">
            <label for="edit_role_id_{{ $u->id }}" class="form-label small fw-semibold text-dark">
              Peran / Role Pengguna <span class="text-danger">*</span>
            </label>
            <select name="role_id" id="edit_role_id_{{ $u->id }}" class="form-select form-select-sm" required onchange="toggleRoleFields('edit_{{ $u->id }}')">
              @foreach($roles as $role)
                <option value="{{ $role->id }}" {{ $u->role_id == $role->id ? 'selected' : '' }}>
                  @if($role->id == 1) Waka Kurikulum (Admin)
                  @elseif($role->id == 2) Guru Mapel
                  @elseif($role->id == 3) Siswa
                  @elseif($role->id == 4) Orang Tua
                  @elseif($role->id == 5) Bimbingan Konseling (BK)
                  @elseif($role->id == 6) Wali Kelas (Walas)
                  @else {{ $role->role_name }}
                  @endif
                </option>
              @endforeach
            </select>
          </div>

          {{-- Nama Lengkap --}}
          <div class="mb-3">
            <label for="edit_fullname_{{ $u->id }}" class="form-label small fw-semibold text-dark">
              Nama Lengkap <span class="text-danger">*</span>
            </label>
            <input type="text" name="fullname" id="edit_fullname_{{ $u->id }}" class="form-control form-control-sm" value="{{ $u->fullname }}" required>
          </div>

          {{-- NIP / NIS --}}
          <div class="mb-3">
            @php
              $editNipLabel = in_array($u->role_id, [3, 4]) ? 'NIS' : 'NIP';
              $editNipPlaceholder = in_array($u->role_id, [3, 4]) ? 'Contoh: 5412 (NIS)' : 'Contoh: 198501012010011001 (NIP)';
            @endphp
            <label for="edit_nip_nisn_{{ $u->id }}" class="form-label small fw-semibold text-dark">
              {{ $editNipLabel }} <span class="text-secondary small fw-normal">(Opsional)</span>
            </label>
            <input type="text" name="nip_nisn" id="edit_nip_nisn_{{ $u->id }}" class="form-control form-control-sm font-mono" value="{{ $u->nip_nisn }}" placeholder="{{ $editNipPlaceholder }}">
            <div class="form-text small" style="font-size: 0.74rem;">NIP untuk Guru / Pegawai (Waka, Walas, BK). NIS untuk Siswa.</div>
          </div>

          {{-- Username --}}
          <div class="mb-3">
            <label for="edit_username_{{ $u->id }}" class="form-label small fw-semibold text-dark">
              Username <span class="text-danger">*</span>
            </label>
            <input type="text" name="username" id="edit_username_{{ $u->id }}" class="form-control form-control-sm font-mono" value="{{ $u->username }}" required>
          </div>

          {{-- Email --}}
          <div class="mb-3">
            <label for="edit_email_{{ $u->id }}" class="form-label small fw-semibold text-dark">
              Email (Opsional)
            </label>
            <input type="email" name="email" id="edit_email_{{ $u->id }}" class="form-control form-control-sm" value="{{ $u->email }}" placeholder="contoh@smkn1bantul.sch.id">
          </div>

          {{-- Password Baru --}}
          <div class="mb-3">
            <label for="edit_password_{{ $u->id }}" class="form-label small fw-semibold text-dark">
              Password Baru (Opsional)
            </label>
            <input type="password" name="password" id="edit_password_{{ $u->id }}" class="form-control form-control-sm" placeholder="Kosongkan jika tidak ingin mengubah password" minlength="6">
            <div class="form-text small" style="font-size: 0.74rem;">Biarkan kosong jika tetap menggunakan password saat ini.</div>
          </div>

          {{-- Kelas --}}
          <div class="mb-3" id="class_container_edit_{{ $u->id }}">
            <label for="edit_class_id_{{ $u->id }}" class="form-label small fw-semibold text-dark">
              Kelas / Rombel
            </label>
            <select name="class_id" id="edit_class_id_{{ $u->id }}" class="form-select form-select-sm">
              <option value="">-- Pilih Kelas (Opsional/Bila Siswa &amp; Walas) --</option>
              @foreach($classes as $c)
                <option value="{{ $c->id }}" {{ $u->class_id == $c->id ? 'selected' : '' }}>
                  {{ $c->class_name }}
                </option>
              @endforeach
            </select>
          </div>

          {{-- Siswa Terhubung (Anak untuk Orang Tua) --}}
          <div class="mb-3 {{ $u->role_id == 4 ? '' : 'd-none' }}" id="parent_container_edit_{{ $u->id }}">
            <label for="edit_parent_id_{{ $u->id }}" class="form-label small fw-semibold text-dark">
              Siswa Terhubung (Anak)
            </label>
            <select name="parent_id" id="edit_parent_id_{{ $u->id }}" class="form-select form-select-sm">
              <option value="">-- Pilih Siswa Anak --</option>
              @foreach($students as $st)
                <option value="{{ $st->id }}" {{ $u->parent_id == $st->id ? 'selected' : '' }}>
                  {{ $st->fullname }} ({{ $st->schoolClass->class_name ?? 'Belum ada kelas' }})
                </option>
              @endforeach
            </select>
          </div>
        </div>

        <div class="modal-footer bg-light py-2 px-4 border-top">
          <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-sm btn-primary px-3">
            <i class="bi bi-check-lg me-1"></i> Simpan Perubahan
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endforeach

@endsection

@section('scripts')
<script>
function toggleRoleFields(prefix) {
  const roleSelect = document.getElementById(prefix === 'create' ? 'create_role_id' : 'edit_role_id_' + prefix.replace('edit_', ''));
  const parentContainer = document.getElementById(prefix === 'create' ? 'create_parent_container' : 'parent_container_' + prefix);
  
  if (!roleSelect) return;
  const roleVal = parseInt(roleSelect.value, 10);

  // Role 4 adalah Orang Tua
  if (parentContainer) {
    if (roleVal === 4) {
      parentContainer.classList.remove('d-none');
    } else {
      parentContainer.classList.add('d-none');
    }
  }
}

// Handler Pemilihan & Drag-Drop File Impor
function handleFileSelected(input) {
  const file = input.files && input.files[0];
  const feedback = document.getElementById('fileSelectedFeedback');
  const nameEl = document.getElementById('selectedFileName');
  const sizeEl = document.getElementById('selectedFileSize');
  const dropzone = document.getElementById('dropzoneContainer');

  if (file) {
    nameEl.textContent = file.name;
    const sizeKB = (file.size / 1024).toFixed(1);
    const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
    sizeEl.textContent = file.size > 1024 * 1024 ? `${sizeMB} MB` : `${sizeKB} KB`;
    feedback.classList.remove('d-none');
    if (dropzone) {
      dropzone.style.borderColor = '#2563eb';
      dropzone.style.backgroundColor = '#eff6ff';
    }
  } else {
    clearSelectedFile();
  }
}

function clearSelectedFile() {
  const input = document.getElementById('import_file_input');
  if (input) input.value = '';
  const feedback = document.getElementById('fileSelectedFeedback');
  if (feedback) feedback.classList.add('d-none');
  const dropzone = document.getElementById('dropzoneContainer');
  if (dropzone) {
    dropzone.style.borderColor = '#cbd5e1';
    dropzone.style.backgroundColor = '#fcfdfe';
  }
}

document.addEventListener('DOMContentLoaded', function() {
  const dropzone = document.getElementById('dropzoneContainer');
  const fileInput = document.getElementById('import_file_input');
  const form = document.getElementById('formImportUser');
  const btnSubmit = document.getElementById('btnSubmitImport');

  if (dropzone && fileInput) {
    ['dragenter', 'dragover'].forEach(eventName => {
      dropzone.addEventListener(eventName, (e) => {
        e.preventDefault();
        e.stopPropagation();
        dropzone.style.borderColor = '#2563eb';
        dropzone.style.backgroundColor = '#eff6ff';
      }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
      dropzone.addEventListener(eventName, (e) => {
        e.preventDefault();
        e.stopPropagation();
        if (eventName === 'dragleave' && (!fileInput.files || !fileInput.files.length)) {
          dropzone.style.borderColor = '#cbd5e1';
          dropzone.style.backgroundColor = '#fcfdfe';
        }
      }, false);
    });

    dropzone.addEventListener('drop', (e) => {
      const dt = e.dataTransfer;
      const files = dt.files;
      if (files && files.length) {
        fileInput.files = files;
        handleFileSelected(fileInput);
      }
    }, false);
  }

  if (form && btnSubmit) {
    form.addEventListener('submit', function() {
      btnSubmit.disabled = true;
      btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Sedang Mengimpor Data...';
    });
  }
});
</script>
@endsection


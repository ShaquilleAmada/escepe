@extends('layouts.app')

@section('title', 'Manajemen Jadwal Pelajaran')

@section('content')
<div class="d-flex flex-column flex-md-row align-items-md-start justify-content-between mb-4 gap-3">
  <div>
    <h1 class="fw-bold mb-1 text-dark heading-section" style="font-size: 1.5rem;">Manajemen Jadwal Pelajaran</h1>
    <p class="text-secondary small mb-0">Kelola dan atur jadwal pembelajaran blok sekolah.</p>
  </div>
  {{-- Tombol Aksi Utama --}}
  <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-2 flex-shrink-0">

    {{-- Tombol Impor Jadwal --}}
    @if($config->is_schedule_locked ?? false)
    <button type="button" class="btn btn-outline-primary d-inline-flex align-items-center justify-content-center gap-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#scheduleLockedModal" title="Jadwal Sedang Terkunci">
      <i class="bi bi-file-earmark-arrow-up"></i>
      <span>Impor Jadwal</span>
    </button>
    @else
    <button type="button" class="btn btn-outline-primary d-inline-flex align-items-center justify-content-center gap-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#importScheduleModal" title="Impor Data Jadwal Pelajaran dari File Excel, Word, atau PDF">
      <i class="bi bi-file-earmark-arrow-up"></i>
      <span>Impor Jadwal</span>
    </button>
    @endif

    {{-- Tombol Tambah Jadwal Baru --}}
    @if($config->is_schedule_locked ?? false)
    <button type="button" class="btn btn-primary d-inline-flex align-items-center justify-content-center gap-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#scheduleLockedModal">
      <i class="bi bi-plus-lg"></i>
      <span>Tambah Jadwal Baru</span>
    </button>
    @else
    <a href="{{ route('waka.schedules.create') }}" class="btn btn-primary d-inline-flex align-items-center justify-content-center gap-2 shadow-sm">
      <i class="bi bi-plus-lg"></i>
      <span>Tambah Jadwal Baru</span>
    </a>
    @endif
  </div>
</div>

{{-- Alert Rincian Catatan / Error Impor --}}
@if(session('import_errors') && count(session('import_errors')) > 0)
  <div class="alert alert-warning alert-dismissible fade show border-0 mb-4 shadow-sm" role="alert" style="background-color: #fffbeb; border-left: 4px solid #f59e0b !important; border-radius: 8px;">
    <div class="d-flex align-items-center mb-2">
      <i class="bi bi-exclamation-triangle-fill text-warning me-2 fs-5"></i>
      <h6 class="fw-bold text-dark mb-0">Catatan Hasil Impor Jadwal Pelajaran ({{ count(session('import_errors')) }} Catatan)</h6>
    </div>
    <ul class="mb-0 small text-secondary ps-3" style="max-height: 160px; overflow-y: auto;">
      @foreach(session('import_errors') as $err)
        <li>{{ $err }}</li>
      @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
@endif

@if($config->is_schedule_locked ?? false)
<div class="card mb-4 shadow-sm border-0" style="border-radius: 12px; border: 1px solid #e2e8f0 !important; background-color: #ffffff;">
    <div class="card-body p-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-3">
            <i class="bi bi-lock-fill fs-3 text-danger flex-shrink-0"></i>
            <div>
                <div class="fw-bold text-dark mb-0.5" style="font-size: 1rem;">Status Jadwal: Terkunci (Locked)</div>
                <div class="text-secondary small" style="color: #64748b; font-size: 0.875rem;">Jadwal pelajaran saat ini telah final/fix dan dikunci. Penambahan dan perubahan jadwal dinonaktifkan.</div>
            </div>
        </div>
        <a href="{{ route('waka.dashboard') }}" class="btn btn-outline-danger fw-semibold text-nowrap py-2 px-3 flex-shrink-0 d-inline-flex align-items-center" style="font-size: 0.875rem; border-radius: 8px;">
            <i class="bi bi-key-fill me-1.5"></i> Buka Kunci di Dashboard
        </a>
    </div>
</div>
@endif

<!-- Filter Toolbar -->
<div class="card mb-4">
<div class="card-body p-3">
<form method="GET" action="{{ route('waka.schedules.index') }}" class="row g-2 align-items-end">
<div class="col-6 col-md-4 col-lg-2">
<label class="form-label small fw-semibold text-secondary mb-1">Hari</label>
<select name="day" class="form-select form-select-sm">
<option value="">Semua Hari</option>
@foreach(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'] as $d)
<option value="{{ $d }}" {{ request('day') == $d ? 'selected' : '' }}>{{ $d }}</option>
@endforeach
</select>
</div>

<div class="col-6 col-md-4 col-lg-2">
<label class="form-label small fw-semibold text-secondary mb-1">Siklus</label>
<select name="cycle" class="form-select form-select-sm">
<option value="">Semua Siklus</option>
<option value="Putaran 1" {{ request('cycle') == 'Putaran 1' ? 'selected' : '' }}>Putaran 1</option>
<option value="Putaran 2" {{ request('cycle') == 'Putaran 2' ? 'selected' : '' }}>Putaran 2</option>
<option value="semua" {{ request('cycle') == 'semua' ? 'selected' : '' }}>Semua Siklus</option>
</select>
</div>

<div class="col-6 col-md-4 col-lg-2">
<label class="form-label small fw-semibold text-secondary mb-1">Tingkat Kelas</label>
<select name="grade_level" class="form-select form-select-sm">
<option value="">Semua Tingkat</option>
@foreach($gradeLevels ?? ['X', 'XI', 'XII'] as $gl)
<option value="{{ $gl }}" {{ request('grade_level') == $gl ? 'selected' : '' }}>Kelas {{ $gl }}</option>
@endforeach
</select>
</div>

<div class="col-6 col-md-4 col-lg-2">
<label class="form-label small fw-semibold text-secondary mb-1">Jurusan</label>
<select name="major" class="form-select form-select-sm">
<option value="">Semua Jurusan</option>
@foreach($majors ?? ['RPL', 'TKJ', 'DKV', 'BR', 'BD', 'LPS', 'AKL', 'MP'] as $m)
<option value="{{ $m }}" {{ strtoupper(request('major') ?? '') === $m ? 'selected' : '' }}>{{ $m }}</option>
@endforeach
</select>
</div>

<div class="col-6 col-md-4 col-lg-1">
<label class="form-label small fw-semibold text-secondary mb-1">Rombel</label>
<select name="section" class="form-select form-select-sm">
<option value="">Semua</option>
@foreach($sections ?? ['1', '2', '3', '4'] as $sec)
<option value="{{ $sec }}" {{ request('section') == (string)$sec ? 'selected' : '' }}>{{ $sec }}</option>
@endforeach
</select>
</div>

<div class="col-6 col-md-4 col-lg-2">
<label class="form-label small fw-semibold text-secondary mb-1">Guru Pengampu</label>
<select name="teacher_id" class="form-select form-select-sm">
<option value="">Semua Guru</option>
@foreach($teachers as $t)
<option value="{{ $t->id }}" {{ request('teacher_id') == $t->id ? 'selected' : '' }}>{{ $t->fullname }}</option>
@endforeach
</select>
</div>

<div class="col-12 col-md-4 col-lg-1 d-flex gap-1">
<button type="submit" class="btn btn-sm btn-primary w-100" title="Terapkan Filter">
<i class="bi bi-filter"></i> Filter
</button>
<a href="{{ route('waka.schedules.index') }}" class="btn btn-sm btn-outline-secondary" title="Reset Filter">
<i class="bi bi-arrow-counterclockwise"></i>
</a>
</div>
</form>
</div>
</div>

<!-- Tabel Jadwal -->
<div class="card">
<div class="card-header d-flex justify-content-between align-items-center">
<span class="fw-bold text-dark">Daftar Jadwal Pelajaran</span>
<span class="text-secondary small">Total: {{ $schedules->total() }} Data</span>
</div>
<div class="card-body p-0">
<div class="table-responsive">
<table class="table table-hover align-middle mb-0">
<thead>
<tr>
<th style="width: 100px;">Hari</th>
<th style="width: 130px;">Waktu</th>
<th>Mata Pelajaran</th>
<th>Guru Pengampu</th>
<th>Kelas</th>
<th>Ruangan</th>
<th style="width: 120px;">Siklus</th>
<th style="width: 110px;" class="text-center">Aksi</th>
</tr>
</thead>
<tbody>
@php 
$lastClassId = null; 
$lastCycle = null;
@endphp
@forelse($schedules as $schedule)
@if($lastClassId !== $schedule->class_id)
@php 
$lastClassId = $schedule->class_id; 
$lastCycle = null;
@endphp
<tr>
<td colspan="8" class="fw-bold py-2 px-3" style="background-color: #dbeafe; color: #0a3d91;">
{{-- #0a3d91 on #dbeafe = 5.0:1 (WCAG AA pass). Konsisten dengan badge-cycle (#0b5ed7). --}}
JADWAL KELAS: {{ strtoupper($schedule->schoolClass->class_name ?? 'Kelas ' . $schedule->class_id) }}
</td>
</tr>
@endif

@if($lastCycle !== $schedule->cycle_type)
@php $lastCycle = $schedule->cycle_type; @endphp
<tr class="bg-light border-top">
<td colspan="8" class="py-2 px-3">
<div class="d-flex align-items-center gap-2">
<span class="badge {{ $schedule->cycle_type === 'semua' ? 'bg-secondary' : 'bg-primary' }} px-2.5 py-1.5 fw-bold">
<i class="bi bi-arrow-repeat me-1"></i> {{ strtoupper($schedule->cycle_type) }}
</span>
<span class="text-secondary small fw-medium">
(Jadwal Lengkap Senin s/d Jumat)
</span>
</div>
</td>
</tr>
@endif
<tr>
<td class="fw-semibold text-dark">{{ $schedule->day_of_week }}</td>
<td class="tabular-time text-primary" style="white-space: nowrap;">
    <div class="fw-semibold">{{ substr($schedule->start_time, 0, 5) }} - {{ substr($schedule->end_time, 0, 5) }}</div>
</td>
<td class="fw-semibold text-dark">{{ $schedule->subject }}</td>
<td>{{ $schedule->teacher->fullname ?? '-' }}</td>
<td>{{ $schedule->schoolClass->class_name ?? '-' }}</td>
<td>{{ $schedule->room->room_name ?? '-' }}</td>
<td>{{ $schedule->cycle_type === 'semua' ? 'Semua Siklus' : $schedule->cycle_type }}</td>
<td class="text-center">
<div class="d-flex align-items-center justify-content-center gap-1">
@if($config->is_schedule_locked ?? false)
<button type="button" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center justify-content-center rounded-2 shadow-none" style="width: 31px; height: 31px;" data-bs-toggle="modal" data-bs-target="#scheduleLockedModal" title="Jadwal Terkunci">
<i class="bi bi-pencil-square"></i>
</button>
<button type="button" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center justify-content-center rounded-2 shadow-none" style="width: 31px; height: 31px;" data-bs-toggle="modal" data-bs-target="#scheduleLockedModal" title="Jadwal Terkunci">
<i class="bi bi-trash"></i>
</button>
@else
<a href="{{ route('waka.schedules.edit', $schedule->id) }}" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center justify-content-center rounded-2 shadow-none" style="width: 31px; height: 31px;" title="Edit Jadwal">
<i class="bi bi-pencil-square"></i>
</a>
<form action="{{ route('waka.schedules.destroy', $schedule->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus jadwal ini?');" class="d-inline m-0 p-0">
@csrf
@method('DELETE')
<button type="submit" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center justify-content-center rounded-2 shadow-none" style="width: 31px; height: 31px;" title="Hapus Jadwal">
<i class="bi bi-trash"></i>
</button>
</form>
@endif
</div>
</td>
</tr>
@empty
<tr>
<td colspan="8" class="text-center py-5 text-muted">
<i class="bi bi-calendar-x fs-3 d-block mb-2"></i>
Belum ada jadwal yang sesuai dengan kriteria pencarian.
</td>
</tr>
@endforelse
</tbody>
</table>
</div>
</div>
@if($schedules->hasPages())
<div class="card-footer bg-white border-top py-3">
<div class="d-flex justify-content-end">
{{ $schedules->links('pagination::bootstrap-5') }}
</div>
</div>
@endif
</div>

{{-- ============================================================
     MODAL IMPOR JADWAL PELAJARAN (EXCEL, WORD, PDF)
============================================================ --}}
<div class="modal fade" id="importScheduleModal" tabindex="-1" aria-labelledby="importScheduleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
      <form action="{{ route('waka.schedules.import') }}" method="POST" enctype="multipart/form-data" id="formImportSchedule">
        @csrf
        <div class="modal-header bg-light py-3 px-4 border-bottom">
          <div class="d-flex align-items-center gap-2">
            <div class="bg-primary bg-opacity-10 text-primary p-2 rounded-3 d-inline-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
              <i class="bi bi-calendar2-range-fill fs-5"></i>
            </div>
            <div>
              <h5 class="modal-title fw-bold text-dark mb-0 fs-6" id="importScheduleModalLabel">Impor Jadwal Pelajaran Massal</h5>
              <p class="text-secondary small mb-0" style="font-size: 0.8rem;">Unggah dokumen jadwal pelajaran dari format Excel, Word, atau PDF</p>
            </div>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body p-4">
          {{-- Kartu Petunjuk & Ketentuan --}}
          <div class="card bg-light border-0 mb-4 p-3 rounded-3" style="background-color: #f8fafc !important; border-left: 3px solid #2563eb !important;">
            <div class="d-flex align-items-start gap-2">
              <i class="bi bi-info-circle-fill text-primary mt-0.5 fs-5"></i>
              <div class="small flex-grow-1">
                <span class="fw-bold text-dark d-block mb-1">Ketentuan Format Kolom &amp; Proteksi Anti-Bentrok:</span>
                <ol class="ps-3 mb-2 text-secondary" style="line-height: 1.6;">
                  <li>Kolom tabel wajib berurutan: <strong>Hari</strong>, <strong>Waktu</strong>, <strong>Mata Pelajaran</strong>, <strong>Guru Pengampu</strong>, <strong>Kelas</strong>, <strong>Ruangan</strong>, dan <strong>Siklus</strong>.</li>
                  <li>Kolom <strong>Hari</strong>: diisi <em>Senin, Selasa, Rabu, Kamis</em>, atau <em>Jumat</em>.</li>
                  <li>Kolom <strong>Waktu</strong>: Cukup ketik nomor jam pelajaran (contoh: <code>Jam Ke-1 - 3</code>, <code>1-3</code>, atau <code>Jam Ke-1</code>). Sistem otomatis membaca jam mulai hingga selesai berdasarkan jadwal jam pelajaran sekolah tanpa perlu mengetik jam 08:00 secara manual.</li>
                  <li>Kolom <strong>Guru Pengampu</strong>: diisi Nama Lengkap (contoh: <code>Budi Santoso, S.Pd.</code>), NIP, atau Username guru yang terdaftar.</li>
                  <li>Kolom <strong>Kelas</strong>: diisi nama kelas terdaftar di sistem (contoh: <code>XII RPL 1</code>).</li>
                  <li>Kolom <strong>Ruangan</strong>: diisi ruang/lab (contoh: <code>Lab RPL 1</code>). Ruangan baru akan otomatis dibuatkan jika belum ada di sistem.</li>
                  <li>Kolom <strong>Siklus</strong>: diisi <code>Putaran 1</code>, <code>Putaran 2</code>, atau <code>Semua</code> (berlaku setiap minggu).</li>
                  </ol>
              </div>
            </div>
          </div>

          {{-- Pilihan Siklus / Putaran --}}
          <div class="mb-3">
            <label for="import_cycle_type" class="form-label small fw-semibold text-dark mb-1">
              Siklus / Putaran Jadwal
            </label>
            <select name="cycle_type" id="import_cycle_type" class="form-select form-select-sm">
              <option value="auto" selected>Otomatis (Deteksi dari Nama File / PDF)</option>
              <option value="Putaran 1">Putaran 1 (Blok Ganjil)</option>
              <option value="Putaran 2">Putaran 2 (Blok Genap)</option>
              <option value="semua">Semua (Berlaku Setiap Minggu)</option>
            </select>
            <div class="form-text" style="font-size: 0.75rem;">
              Pilih <strong>Putaran 1</strong> jika jadwal berlaku untuk siklus putaran 1, atau biarkan <em>Otomatis</em> agar sistem mendeteksi dari nama file.
            </div>
          </div>

          {{-- Area Dropzone / Upload File --}}
          <div class="mb-3">
            <label for="import_schedule_file_input" class="form-label small fw-semibold text-dark mb-1">
              Pilih Dokumen File Jadwal <span class="text-danger">*</span>
            </label>
            <div class="border border-2 border-dashed rounded-3 p-4 text-center" id="scheduleDropzoneContainer" style="border-color: #cbd5e1 !important; background-color: #fcfdfe; transition: all 0.2s ease;">
              <i class="bi bi-cloud-arrow-up display-5 text-primary opacity-75 mb-2 d-block"></i>
              <p class="mb-1 fw-semibold text-dark" style="font-size: 0.95rem;">Seret &amp; letakkan file di sini, atau klik untuk memilih file</p>
              <p class="text-secondary small mb-3" style="font-size: 0.8rem;">Format didukung: Excel (.xlsx, .xls, .csv), Word (.docx), PDF (.pdf) &bull; Maks 10 MB</p>
              
              <input type="file" name="file" id="import_schedule_file_input" class="d-none" accept=".xlsx,.xls,.csv,.docx,.pdf" required onchange="handleScheduleFileSelected(this)">
              <button type="button" class="btn btn-sm btn-outline-primary px-3 shadow-none" onclick="document.getElementById('import_schedule_file_input').click()">
                <i class="bi bi-folder2-open me-1"></i> Telusuri File Dokumen
              </button>
            </div>

            {{-- File Selected Feedback --}}
            <div id="scheduleFileSelectedFeedback" class="d-none mt-2 alert alert-light border d-flex align-items-center justify-content-between p-2 rounded-2 mb-0">
              <div class="d-flex align-items-center gap-2 text-truncate">
                <i class="bi bi-file-earmark-check text-success fs-5"></i>
                <div class="text-truncate">
                  <div class="fw-semibold text-dark small text-truncate" id="scheduleSelectedFileName">-</div>
                  <div class="text-secondary font-mono" style="font-size: 0.75rem;" id="scheduleSelectedFileSize">-</div>
                </div>
              </div>
              <button type="button" class="btn btn-sm text-danger shadow-none p-1" onclick="clearSelectedScheduleFile()" title="Hapus file terpilih">
                <i class="bi bi-x-circle-fill fs-5"></i>
              </button>
            </div>
          </div>
        </div>

        <div class="modal-footer bg-light py-2.5 px-4 border-top d-flex justify-content-between">
          <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-sm btn-primary px-4 d-inline-flex align-items-center gap-2 shadow-sm" id="btnSubmitImportSchedule">
            <i class="bi bi-upload"></i>
            <span>Mulai Impor Jadwal</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function handleScheduleFileSelected(input) {
  const file = input.files && input.files[0];
  const feedback = document.getElementById('scheduleFileSelectedFeedback');
  const nameEl = document.getElementById('scheduleSelectedFileName');
  const sizeEl = document.getElementById('scheduleSelectedFileSize');
  const dropzone = document.getElementById('scheduleDropzoneContainer');

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
    clearSelectedScheduleFile();
  }
}

function clearSelectedScheduleFile() {
  const input = document.getElementById('import_schedule_file_input');
  if (input) input.value = '';
  const feedback = document.getElementById('scheduleFileSelectedFeedback');
  if (feedback) feedback.classList.add('d-none');
  const dropzone = document.getElementById('scheduleDropzoneContainer');
  if (dropzone) {
    dropzone.style.borderColor = '#cbd5e1';
    dropzone.style.backgroundColor = '#fcfdfe';
  }
}

document.addEventListener('DOMContentLoaded', function() {
  const dropzone = document.getElementById('scheduleDropzoneContainer');
  const fileInput = document.getElementById('import_schedule_file_input');
  const form = document.getElementById('formImportSchedule');
  const btnSubmit = document.getElementById('btnSubmitImportSchedule');

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
        handleScheduleFileSelected(fileInput);
      }
    }, false);
  }

  if (form && btnSubmit) {
    form.addEventListener('submit', function() {
      btnSubmit.disabled = true;
      btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Sedang Memproses Jadwal...';
    });
  }
});
</script>
@endsection

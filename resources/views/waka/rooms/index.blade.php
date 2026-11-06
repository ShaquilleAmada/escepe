@extends('layouts.app')

@section('title', 'Data Ruangan')

@section('content')
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-3">
<div>
<h4 class="fw-bold mb-1 text-dark">Data Master Ruangan & Lab</h4>
<p class="text-secondary small mb-0">Kelola daftar ruang kelas, lab komputer, dan bengkel praktik sekolah.</p>
</div>
<div class="d-flex align-items-center gap-2">
<button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#importRoomMatrixModal">
<i class="bi bi-file-earmark-arrow-up me-1"></i> Impor Matriks Ruangan
</button>
<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRoomModal">
<i class="bi bi-plus-lg me-1"></i> Tambah Ruangan
</button>
</div>
</div>

<div class="card">
<div class="card-header d-flex justify-content-between align-items-center">
<span class="fw-bold text-dark">Daftar Ruangan</span>
<span class="text-secondary small">Total: {{ $rooms->total() }} Ruangan</span>
</div>
<div class="card-body p-0">
<div class="table-responsive">
<table class="table table-hover align-middle mb-0">
<thead>
<tr>
<th style="width: 70px;">No</th>
<th>Nama Ruangan / Lab</th>
<th style="text-transform: none; letter-spacing: 0;">Total Jadwal Menggunakan Ruangan Ini</th>
<th style="width: 120px;" class="text-center">Aksi</th>
</tr>
</thead>
<tbody>
@forelse($rooms as $index => $r)
<tr>
<td>{{ $rooms->firstItem() + $index }}</td>
<td class="fw-bold text-dark">{{ $r->room_name }}</td>
<td>
<span class="text-dark">{{ $r->schedules_count }} Sesi Jadwal</span>
</td>
<td class="text-center">
<div class="d-flex align-items-center justify-content-center gap-1">
<button type="button" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center justify-content-center rounded-2 shadow-none" style="width: 31px; height: 31px;" data-bs-toggle="modal" data-bs-target="#editRoomModal{{ $r->id }}" title="Edit Ruangan">
<i class="bi bi-pencil-square"></i>
</button>
<form action="{{ route('waka.rooms.destroy', $r->id) }}" method="POST" onsubmit="return confirm('Hapus ruangan ini? Semua jadwal yang terhubung ke ruangan ini akan terhapus.');" class="d-inline m-0 p-0">
@csrf
@method('DELETE')
<button type="submit" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center justify-content-center rounded-2 shadow-none" style="width: 31px; height: 31px;" title="Hapus Ruangan">
<i class="bi bi-trash"></i>
</button>
</form>
</div>
</td>
</tr>
@empty
<tr>
<td colspan="4" class="text-center py-4 text-muted">Belum ada data ruangan.</td>
</tr>
@endforelse
</tbody>
</table>
</div>
</div>
@if($rooms->hasPages())
<div class="card-footer bg-white border-top py-3">
<div class="d-flex justify-content-end">
{{ $rooms->links('pagination::bootstrap-5') }}
</div>
</div>
@endif
</div>

<!-- Modals Edit Ruangan -->
@foreach($rooms as $r)
<div class="modal fade" id="editRoomModal{{ $r->id }}" tabindex="-1" aria-hidden="true">
<div class="modal-dialog">
<div class="modal-content">
<div class="modal-header">
<h6 class="modal-title fw-bold">Edit Nama Ruangan</h6>
<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<form action="{{ route('waka.rooms.update', $r->id) }}" method="POST">
@csrf
@method('PUT')
<div class="modal-body">
<div class="mb-3">
<label class="form-label small fw-semibold text-dark">Nama Ruangan</label>
<input type="text" name="room_name" class="form-control" value="{{ $r->room_name }}" required>
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
@endforeach

<!-- Modal Tambah Ruangan -->
<div class="modal fade" id="addRoomModal" tabindex="-1" aria-hidden="true">
<div class="modal-dialog">
<div class="modal-content">
<div class="modal-header">
<h6 class="modal-title fw-bold">Tambah Ruangan Baru</h6>
<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<form action="{{ route('waka.rooms.store') }}" method="POST">
@csrf
<div class="modal-body">
<div class="mb-3">
<label class="form-label small fw-semibold text-dark">Nama Ruangan <span class="text-danger">*</span></label>
<input type="text" name="room_name" class="form-control" placeholder="Contoh: Lab RPL 1" required>
</div>
</div>
<div class="modal-footer">
<button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
<button type="submit" class="btn btn-sm btn-primary">Simpan Ruangan</button>
</div>
</form>
</div>
</div>
</div>

<!-- Modal Impor Matriks Ruangan -->
<div class="modal fade" id="importRoomMatrixModal" tabindex="-1" aria-labelledby="importRoomMatrixModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
      <div class="modal-header bg-light border-bottom px-4 py-3">
        <div>
          <h5 class="modal-title fw-bold text-dark mb-0" id="importRoomMatrixModalLabel" style="font-size: 1.05rem;">
            Impor Matriks Ruangan Massal
          </h5>
          <span class="text-muted small">Sinkronisasi otomatis ruang fisik per rombel & jam</span>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('waka.rooms.import-matrix') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="modal-body p-4">
          <div class="alert alert-info border-0 bg-info-subtle text-dark small mb-3">
            <div class="fw-semibold mb-1"><i class="bi bi-info-circle me-1 text-primary"></i> Format Dokumen Resmi:</div>
            Mendukung dokumen PDF (seperti <em>Jadwal KBM Putaran 2 SMKN 1 Bantul</em>) maupun Excel (.xlsx, .csv). Sistem membaca 48 kolom rombel, otomatis menstandarkan kode ruangan (misal: <code>L.16</code> &rarr; <code>Lab 16</code>, <code>22</code> &rarr; <code>Ruang 22</code>, <code>OR1</code>, <code>HALL</code>), serta memperbarui jadwal terkait.
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold text-dark">Siklus Putaran <span class="text-danger">*</span></label>
            <select name="cycle_type" class="form-select form-select-sm" required>
              <option value="Putaran 2" selected>Putaran 2 (Default Semester)</option>
              <option value="Putaran 1">Putaran 1</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold text-dark">Pilih File PDF / Excel <span class="text-danger">*</span></label>
            <input type="file" name="file" class="form-control" accept=".pdf,.xlsx,.xls,.csv" required>
            <div class="form-text small text-muted">Format: PDF, XLSX, XLS, CSV. Ukuran maksimal 20 MB.</div>
          </div>
        </div>
        <div class="modal-footer bg-light px-4 py-3 border-top">
          <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary btn-sm px-4">
            <i class="bi bi-cloud-arrow-up me-1"></i> Mulai Sinkronisasi
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

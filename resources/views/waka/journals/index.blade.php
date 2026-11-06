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
    <span class="text-dark fw-medium">{{ $j->attendances->count() }} Siswa</span>
</td>
<td class="text-center">
    @if($j->photo_path)
    <a href="{{ asset('storage/' . $j->photo_path) }}" target="_blank" class="btn btn-sm btn-outline-primary py-1 px-2" title="Lihat Foto">
        Lihat
    </a>
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
@endsection

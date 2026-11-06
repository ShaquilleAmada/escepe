@extends('layouts.app')

@section('title', 'Hari Libur Sekolah')

@section('content')
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1 text-dark">Data Master Hari Libur Sekolah</h4>
        <p class="text-secondary small mb-0">Kelola tanggal hari libur resmi sekolah untuk penentuan hari efektif KBM dan akurasi rekap presensi.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addHolidayModal">
            <i class="bi bi-plus-lg me-1"></i> Tambah Hari Libur
        </button>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card mb-4">
    <div class="card-body p-3">
        <form action="{{ route('waka.holidays.index') }}" method="GET" class="row g-2 align-items-center">
            <div class="col-auto">
                <label class="form-label mb-0 fw-semibold text-secondary small">Filter Tahun:</label>
            </div>
            <div class="col-auto">
                <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                    @for($y = date('Y') - 1; $y <= date('Y') + 2; $y++)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-bold text-dark">Daftar Hari Libur (Tahun {{ $year }})</span>
        <span class="text-secondary small">Total: {{ $holidays->count() }} Hari Libur</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 70px;">No</th>
                        <th>Tanggal Libur</th>
                        <th>Hari</th>
                        <th>Keterangan</th>
                        <th>Dicatat Oleh</th>
                        <th style="width: 100px;" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($holidays as $index => $h)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td class="fw-bold text-dark">{{ \Carbon\Carbon::parse($h->holiday_date)->format('d/m/Y') }}</td>
                            <td>
                                @php
                                    $dayName = match(\Carbon\Carbon::parse($h->holiday_date)->dayOfWeek) {
                                        0 => 'Minggu',
                                        1 => 'Senin',
                                        2 => 'Selasa',
                                        3 => 'Rabu',
                                        4 => 'Kamis',
                                        5 => 'Jumat',
                                        6 => 'Sabtu',
                                    };
                                @endphp
                                <span class="badge bg-light text-dark border">{{ $dayName }}</span>
                            </td>
                            <td>{{ $h->description }}</td>
                            <td class="text-secondary small">{{ $h->creator->fullname ?? 'Waka' }}</td>
                            <td class="text-center">
                                <form action="{{ route('waka.holidays.destroy', $h->id) }}" method="POST" onsubmit="return confirm('Hapus hari libur ini?');" class="d-inline m-0 p-0">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center justify-content-center rounded-2" style="width: 31px; height: 31px;" title="Hapus">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-secondary">
                                Belum ada daftar hari libur yang dicatat untuk tahun {{ $year }}.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah Hari Libur -->
<div class="modal fade" id="addHolidayModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('waka.holidays.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Tambah Hari Libur Sekolah</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tanggal Libur <span class="text-danger">*</span></label>
                        <input type="date" name="holiday_date" class="form-control" required value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Keterangan Hari Libur <span class="text-danger">*</span></label>
                        <input type="text" name="description" class="form-control" placeholder="Contoh: Hari Raya Idul Fitri / Libur Nasional" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Hari Libur</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection


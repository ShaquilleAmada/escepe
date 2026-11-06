@extends('layouts.app')

@section('title', 'Siklus Putaran & Riwayat')

@section('content')
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-3">
  <div>
    <h1 class="fw-bold mb-1 text-dark heading-section" style="font-size: 1.5rem;">Siklus Putaran Blok</h1>
    <p class="text-secondary small mb-0">Kelola putaran blok aktif mingguan serta pantau riwayat lengkap perputaran siklus akademik.</p>
  </div>
</div>

{{-- ============================================================
     PANEL KONTROL SIKLUS PUTARAN (FULL WIDTH KANAN-KIRI)
============================================================ --}}
<div class="card border shadow-sm mb-4">
  <div class="card-body p-4">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
      <div>
        <div class="mb-2">
          <span class="text-muted fw-bold" style="font-size: 0.7rem; letter-spacing: 0.08em; text-transform: uppercase;">SIKLUS PUTARAN BLOK SAAT INI</span>
        </div>
        <h2 class="fw-bold text-dark mb-1" style="font-size: 1.35rem; letter-spacing: -0.01em;">
          {{ $config->active_cycle ?? 'Putaran 1' }} Sedang Aktif
        </h2>
        <p class="text-secondary small mb-0" style="max-width: 75ch; line-height: 1.5;">
          Seluruh guru &amp; siswa saat ini mengakses jadwal mingguan <strong>{{ $config->active_cycle ?? 'Putaran 1' }}</strong>. 
          Setiap perputaran siklus akan otomatis memperbarui distribusi jadwal rombel dan dicatat ke log riwayat di bawah.
        </p>
      </div>

      <div class="flex-shrink-0">
        <form action="{{ route('waka.toggle-cycle') }}" method="POST" class="d-inline"
          @php $isLocked = $config->is_schedule_locked ?? false; $nextCycle = ($config->active_cycle ?? 'Putaran 1') === 'Putaran 1' ? 'Putaran 2' : 'Putaran 1'; @endphp
          {{ !$isLocked ? 'onsubmit="return confirm(\'Apakah Anda yakin ingin mengganti siklus blok aktif? Perubahan akan otomatis dicatat ke riwayat.\')"' : '' }}>
          @csrf
          <button type="submit" name="active_cycle" value="{{ $nextCycle }}"
            class="btn fw-semibold px-4 py-2 {{ $isLocked ? 'btn-secondary' : 'btn-primary text-white' }}"
            style="font-size: 0.92rem; border-radius: 8px;"
            {{ $isLocked ? 'disabled' : '' }}
            @if($isLocked) title="Jadwal sedang terkunci. Buka kunci jadwal untuk mengganti putaran." @endif>
            @if($isLocked)
              <i class="bi bi-lock-fill me-1 fs-5"></i> Putaran Terkunci
            @else
              <i class="bi bi-arrow-repeat me-1 fs-5"></i> Ganti ke {{ $nextCycle }}
            @endif
          </button>
        </form>
      </div>
    </div>

    {{-- Peringatan terkunci di dalam card --}}
    @if($config->is_schedule_locked ?? false)
      <div class="mt-3" style="font-size: 0.85rem; color: #dc2626;">
        <strong>Jadwal sedang terkunci.</strong>
        Sistem putaran tidak dapat diubah selama Lock Jadwal aktif. Buka kunci jadwal terlebih dahulu melalui halaman Dashboard untuk dapat mengganti putaran siklus.
      </div>
    @endif
  </div>
</div>


{{-- ============================================================
     3 STAT OVERVIEW CARDS
============================================================ --}}
<div class="row g-3 mb-4">
  <div class="col-12 col-md-4">
    <div class="card border shadow-sm h-100">
      <div class="card-body p-3">
        <div class="text-secondary small fw-medium mb-1">Status Siklus Aktif</div>
        <div class="fw-bold text-dark font-mono" style="font-size: 1.85rem; line-height: 1.1;">{{ $config->active_cycle ?? 'Putaran 1' }}</div>
      </div>
    </div>
  </div>

  <div class="col-12 col-md-4">
    <div class="card border shadow-sm h-100">
      <div class="card-body p-3">
        <div class="text-secondary small fw-medium mb-1">Total Log Perputaran</div>
        <div class="fw-bold text-dark font-mono" style="font-size: 1.85rem; line-height: 1.1;">{{ $totalSwitches }}</div>
      </div>
    </div>
  </div>

  <div class="col-12 col-md-4">
    <div class="card border shadow-sm h-100">
      <div class="card-body p-3">
        <div class="text-secondary small fw-medium mb-1">Terakhir Diubah</div>
        <div class="fw-bold text-dark" style="font-size: 1.2rem; line-height: 1.2;">
          @if($lastSwitch)
            {{ \Carbon\Carbon::parse($lastSwitch->start_date)->format('d M Y') }}
          @else
            -
          @endif
        </div>
      </div>
    </div>
  </div>
</div>

{{-- ============================================================
     TABEL RIWAYAT PERPUTARAN SIKLUS (MATCHES USER IMAGE)
============================================================ --}}
<div class="card border shadow-sm mb-4">
  <div class="card-header bg-white py-3 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
    <div class="d-flex align-items-center gap-2">
      <i class="bi bi-clock-history text-primary fs-5"></i>
      <h2 class="fw-bold text-dark mb-0" style="font-size: 1.2rem; letter-spacing: -0.01em;">Riwayat Perputaran Siklus</h2>
    </div>
    <div class="d-flex align-items-center gap-2">
      <form method="GET" action="{{ route('waka.cycles.index') }}" class="d-flex align-items-center gap-2">
        <select name="cycle_filter" class="form-select form-select-sm" onchange="this.form.submit()" style="width: auto;">
          <option value="">Semua Siklus</option>
          <option value="Putaran 1" {{ request('cycle_filter') === 'Putaran 1' ? 'selected' : '' }}>Putaran 1</option>
          <option value="Putaran 2" {{ request('cycle_filter') === 'Putaran 2' ? 'selected' : '' }}>Putaran 2</option>
        </select>
      </form>
      <span class="text-secondary small">{{ $cycleHistories->total() }} Log Terdaftar</span>
    </div>
  </div>

  <div class="card-body p-0">
    @if($cycleHistories->count() > 0)
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th style="width: 140px;" class="ps-4">Siklus</th>
              <th style="width: 160px;">Tanggal</th>
              <th>Pemetaan Kelas</th>
              <th style="width: 200px;" class="pe-4">Diubah Oleh</th>
            </tr>
          </thead>
          <tbody>
            @foreach($cycleHistories as $history)
              <tr>
                <td class="ps-4 text-dark fw-medium">
                  {{ $history->cycle_name }}
                </td>
                <td class="text-secondary font-mono" style="font-size: 0.85rem;">
                  {{ \Carbon\Carbon::parse($history->start_date)->format('d M Y') }}
                </td>
                <td class="fw-semibold text-dark small">
                  {{ $history->class_mapping_summary ?? '-' }}
                </td>
                <td class="pe-4 text-secondary small">
                  {{ $history->user->fullname ?? 'Waka Kurikulum' }}
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      @if($cycleHistories->hasPages())
        <div class="px-4 py-3 border-top d-flex justify-content-end">
          {{ $cycleHistories->links('pagination::bootstrap-5') }}
        </div>
      @endif
    @else
      <div class="text-center py-5 text-muted">
        <i class="bi bi-clock-history d-block fs-1 mb-2 text-secondary opacity-50"></i>
        <span>Belum ada riwayat perputaran siklus yang tercatat.</span>
      </div>
    @endif
  </div>
</div>

{{-- ============================================================
     MATRIKS PEMETAAN ROMBEL SIKLUS AKTIF
============================================================ --}}
<div class="card border shadow-sm">
  <div class="card-header bg-white py-3 d-flex align-items-center gap-2">
    <i class="bi bi-grid-3x3-gap-fill text-primary fs-5"></i>
    <span class="fw-bold text-dark">Matriks Rombongan Belajar (Siklus Berjalan)</span>
  </div>
  <div class="card-body p-0">
    @foreach($classStatusMatrix as $matrix)
      <div class="px-4 py-3 {{ !$loop->last ? 'border-bottom' : '' }}">
        <div class="d-flex flex-column flex-sm-row align-items-sm-start justify-content-between gap-2">

          {{-- Kiri: Nama kelas + tipe --}}
          <div>
            <div class="fw-bold text-dark mb-1" style="font-size: 1rem;">{{ $matrix['class_name'] }}</div>
            <div class="text-secondary small">{{ $matrix['status_type'] }}</div>
          </div>

          {{-- Kanan: Detail info --}}
          <div class="text-sm-end" style="min-width: 0;">
            <div class="text-secondary small">
              <strong>Ruang:</strong> {{ $matrix['location'] }}
            </div>
          </div>

        </div>
      </div>
    @endforeach
  </div>
</div>

@endsection

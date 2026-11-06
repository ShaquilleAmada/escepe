@extends('layouts.app')

@section('title', 'Dashboard Waka Kurikulum')

@section('content')
    {{-- Page Title --}}
    <div class="mb-4">
        <h1 class="fw-bold mb-1 text-dark heading-section" style="font-size: 1.65rem;">Dashboard</h1>
        <p class="text-secondary small mb-0" style="color: #475569 !important; font-size: 0.9rem;">
            Ringkasan pemantauan aktivitas akademik dan status pembelajaran hari ini.
        </p>
    </div>

    {{-- ============================================================
    4 STAT CARDS (Siklus Aktif, Kelas, Ruangan, Pengguna)
    ============================================================ --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100"
                style="background-color: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0 !important;">
                <div class="card-body p-4">
                    <span class="text-uppercase fw-bold"
                        style="color: #64748b; font-size: 0.78rem; letter-spacing: 0.05em;">Siklus Aktif</span>
                    <div class="fw-bold text-dark mt-2" style="font-size: 1.9rem; line-height: 1.1; color: #0f172a;">
                        {{ $config->active_cycle ?? 'Putaran 1' }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100"
                style="background-color: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0 !important;">
                <div class="card-body p-4">
                    <span class="text-uppercase fw-bold"
                        style="color: #64748b; font-size: 0.78rem; letter-spacing: 0.05em;">Total Kelas</span>
                    <div class="fw-bold text-dark mt-2" style="font-size: 2.25rem; line-height: 1.1; color: #0f172a;">
                        {{ $totalClasses }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100"
                style="background-color: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0 !important;">
                <div class="card-body p-4">
                    <span class="text-uppercase fw-bold"
                        style="color: #64748b; font-size: 0.78rem; letter-spacing: 0.05em;">Total Ruangan</span>
                    <div class="fw-bold text-dark mt-2" style="font-size: 2.25rem; line-height: 1.1; color: #0f172a;">
                        {{ $totalRooms }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100"
                style="background-color: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0 !important;">
                <div class="card-body p-4">
                    <span class="text-uppercase fw-bold"
                        style="color: #64748b; font-size: 0.78rem; letter-spacing: 0.05em;">Total Pengguna</span>
                    <div class="fw-bold text-dark mt-2" style="font-size: 2.25rem; line-height: 1.1; color: #0f172a;">
                        {{ $totalUsers }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
    KONTROL BUKA / KUNCI JADWAL
    ============================================================ --}}
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0"
                style="border-radius: 12px; border: 1px solid #e2e8f0 !important; overflow: hidden;">
                <div class="card-body p-4">
                    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">

                        {{-- Info Status --}}
                        <div class="d-flex align-items-center gap-3">
                            @if($config->is_schedule_locked)
                                <div class="d-flex align-items-center justify-content-center flex-shrink-0"
                                    style="width: 48px; height: 48px; border-radius: 12px; background-color: transparent; border: none;">
                                    <i class="bi bi-lock-fill" style="font-size: 1.4rem; color: #dc2626;"></i>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark mb-1" style="font-size: 0.97rem;">Status Jadwal: <span
                                            style="color: #dc2626;">DIKUNCI</span></div>
                                    <div class="small" style="color: #64748b; font-size: 0.82rem;">
                                        Jadwal sedang dikunci. Guru tidak dapat mengubah atau menambah jadwal. Klik <strong>Buka
                                            Kunci</strong> untuk mengaktifkan pengeditan.
                                    </div>
                                </div>
                            @else
                                <div class="d-flex align-items-center justify-content-center flex-shrink-0"
                                    style="width: 48px; height: 48px; border-radius: 12px; background-color: transparent; border: none;">
                                    <i class="bi bi-unlock-fill" style="font-size: 1.4rem; color: #16a34a;"></i>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark mb-1" style="font-size: 0.97rem;">Status Jadwal: <span
                                            style="color: #16a34a;">TERBUKA</span></div>
                                    <div class="small" style="color: #64748b; font-size: 0.82rem;">
                                        Jadwal sedang aktif dan dapat diedit. Klik <strong>Kunci Jadwal</strong> untuk mencegah
                                        perubahan lebih lanjut.
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- Tombol Toggle --}}
                        <div class="flex-shrink-0">
                            <form action="{{ route('waka.toggle-lock') }}" method="POST"
                                onsubmit="return confirm('{{ $config->is_schedule_locked ? 'Buka kunci jadwal? Pengeditan jadwal akan diaktifkan kembali.' : 'Kunci jadwal? Pengeditan jadwal akan dinonaktifkan.' }}')">
                                @csrf
                                @if($config->is_schedule_locked)
                                    <button type="submit" id="btn-buka-kunci"
                                        class="btn fw-semibold px-4 py-2 d-flex align-items-center gap-2"
                                        style="background-color: #16a34a; color: #ffffff; border-radius: 8px; border: none; font-size: 0.9rem; transition: background-color 0.2s;"
                                        onmouseover="this.style.backgroundColor='#15803d'"
                                        onmouseout="this.style.backgroundColor='#16a34a'">
                                        <i class="bi bi-unlock-fill"></i> Buka Kunci Jadwal
                                    </button>
                                @else
                                    <button type="submit" id="btn-kunci-jadwal"
                                        class="btn fw-semibold px-4 py-2 d-flex align-items-center gap-2"
                                        style="background-color: #dc2626; color: #ffffff; border-radius: 8px; border: none; font-size: 0.9rem; transition: background-color 0.2s;"
                                        onmouseover="this.style.backgroundColor='#b91c1c'"
                                        onmouseout="this.style.backgroundColor='#dc2626'">
                                        <i class="bi bi-lock-fill"></i> Kunci Jadwal
                                    </button>
                                @endif
                            </form>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
    ALERT: GURU BELUM ISI JURNAL (>30 Menit)
    ============================================================ --}}
    {{-- ============================================================
    MONITORING AKTIVITAS MENGAJAR & JURNAL HARI INI
    ============================================================ --}}
    @if(count($overdueJournals) > 0)
        <div class="card mb-4 shadow-sm border-0"
            style="border-radius: 12px; border: 1px solid #e2e8f0 !important; background-color: #ffffff; overflow: hidden;">
            {{-- Header Banner dengan Status Ringkasan --}}
            <div class="card-header py-3 px-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3"
                style="background-color: #ffffff; border-bottom: 1px solid #e2e8f0;">
                <div>
                    @if(($overdueCount ?? 0) > 0)
                        <h2 class="fw-bold mb-1 text-dark" style="font-size: 1.05rem;">
                            Monitoring Aktivitas Mengajar &amp; Jurnal Hari Ini
                        </h2>
                        <p class="mb-0 text-secondary" style="font-size: 0.875rem; max-width: 55ch; line-height: 1.45;">
                            Terdapat <strong class="text-danger">{{ $overdueCount }} guru</strong> yang belum mengisi jurnal
                            setelah lewat 30 menit dari jam selesai.
                        </p>
                    @else
                        <h2 class="fw-bold mb-1 text-dark" style="font-size: 1.05rem;">
                            Monitoring Aktivitas Mengajar &amp; Jurnal Hari Ini
                        </h2>
                        <p class="mb-0 text-secondary" style="font-size: 0.875rem; max-width: 55ch; line-height: 1.45;">
                            Status kegiatan mengajar dan pengisian jurnal guru pada hari {{ $todayIndo }}.
                        </p>
                    @endif
                </div>

                {{-- Ringkasan Target & Keterisian Jurnal Hari Ini (Teks Murni) --}}
                <div class="d-flex align-items-center gap-3 flex-wrap" style="font-size: 0.875rem;">
                    <span class="fw-semibold" style="color: #15803d;">
                        Total Jurnal Yang Terisi: <strong>{{ $completedCount }} / {{ count($monitoringJournals) }} Jurnal</strong>
                    </span>
                    @if(count($monitoringJournals) - $completedCount > 0)
                        <span class="text-muted">|</span>
                        <span class="fw-semibold" style="color: #dc2626;">
                            {{ count($monitoringJournals) - $completedCount }} Belum Terisi
                        </span>
                    @else
                        <span class="text-muted">|</span>
                        <span class="fw-semibold" style="color: #16a34a;">
                            Semua Jurnal Terisi
                        </span>
                    @endif
                </div>

            </div>

            {{-- Tabel Monitoring Guru --}}
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.875rem;">
                        <thead
                            style="background-color: #f8fafc; color: #334155; font-size: 0.82rem; text-transform: uppercase; letter-spacing: 0.04em; border-bottom: 2px solid #e2e8f0;">
                            <tr>
                                <th class="py-3 px-4 text-nowrap" style="min-width: 220px;">Guru Pengampu</th>
                                <th class="py-3 px-3 text-nowrap" style="min-width: 220px;">Mata Pelajaran</th>
                                <th class="py-3 px-3 text-nowrap text-center" style="width: 120px;">Kelas</th>
                                <th class="py-3 px-3 text-nowrap text-center" style="width: 120px;">Ruangan</th>
                                <th class="py-3 px-3 text-nowrap text-center" style="width: 150px;">Jam Pelajaran</th>
                                <th class="py-3 px-4 text-nowrap text-center" style="width: 175px;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($overdueJournals as $overdue)
                                <tr>
                                    <td class="py-3 px-4 text-nowrap">
                                        <span class="fw-semibold text-dark"
                                            style="font-size: 0.875rem;">{{ $overdue['teacher_name'] }}</span>
                                    </td>
                                    <td class="py-3 px-3">
                                        <span class="text-dark fw-medium"
                                            style="font-size: 0.875rem;">{{ $overdue['subject'] }}</span>
                                    </td>
                                    <td class="py-3 px-3 text-nowrap text-center">
                                        <span class="fw-semibold text-dark"
                                            style="font-size: 0.875rem;">{{ $overdue['class_name'] }}</span>
                                    </td>
                                    <td class="py-3 px-3 text-nowrap text-center">
                                        <span class="fw-medium text-dark"
                                            style="font-size: 0.875rem;">{{ $overdue['room_name'] }}</span>
                                    </td>
                                    <td class="py-3 px-3 text-nowrap text-center">
                                        <span class="tabular-time fw-semibold"
                                            style="font-size: 0.875rem; color: #0f172a; font-family: var(--font-mono, monospace);">
                                            {{ $overdue['time'] }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-nowrap text-center">
                                        <span class="fw-semibold"
                                            style="font-size: 0.875rem; color: {{ $overdue['status_color'] ?? '#dc2626' }};">
                                            {{ $overdue['status_text'] ?? 'Belum Mengisi Jurnal' }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- ============================================================
    MATRIKS ROMBONGAN BELAJAR
    ============================================================ --}}
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0" style="border-radius: 12px; border: 1px solid #e2e8f0 !important;">
                <div class="card-header bg-white py-3 px-4 d-flex align-items-center gap-2"
                    style="border-bottom: 1px solid #f1f5f9;">
                    <h2 class="fw-bold text-dark mb-0 heading-card-title" style="font-size: 1.05rem;">Matriks Rombongan
                        Belajar</h2>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        @php
                            $matrixCount = count($classStatusMatrix ?? []);
                            $colClass = $matrixCount <= 2 ? 'col-12 col-md-6' : ($matrixCount <= 3 ? 'col-12 col-md-4' : 'col-12 col-sm-6 col-md-4 col-xl-3');
                        @endphp
                        @forelse($classStatusMatrix ?? [] as $matrix)
                            <div class="{{ $colClass }}">
                                <div class="h-100 rounded-3 p-3" style="background-color: #f8fafc; border: 1px solid #e2e8f0;">
                                    <div class="mb-2">
                                        <h3 class="fw-bold text-dark mb-0 heading-sub-item" style="font-size: 0.95rem;">
                                            {{ $matrix['class_name'] }}</h3>
                                    </div>
                                    <div class="small mb-1" style="color: #475569; font-size: 0.82rem;">
                                        Pembelajaran: <strong class="text-dark">{{ $matrix['status_type'] ?? '-' }}</strong>
                                    </div>
                                    <div class="small mb-1" style="color: #475569; font-size: 0.82rem;">
                                        Ruang Kelas: <strong class="text-dark">{{ $matrix['location'] ?? '-' }}</strong>
                                    </div>
                                    <div class="small" style="color: #64748b; font-size: 0.82rem;">
                                        Mata Pelajaran: <span
                                            class="text-dark fw-medium">{{ $matrix['current_focus'] ?? '-' }}</span>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12 text-center py-4 text-muted">
                                <span class="small">Belum ada data rombongan belajar.</span>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
    JADWAL PELAJARAN HARI INI
    ============================================================ --}}
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0" style="border-radius: 12px; border: 1px solid #e2e8f0 !important;">
                <div class="card-header bg-white py-3 px-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2"
                    style="border-bottom: 1px solid #f1f5f9;">
                    <div class="d-flex align-items-center gap-2">
                        <h2 class="fw-bold text-dark mb-0 heading-card-title" style="font-size: 1.05rem;">Jadwal Pelajaran
                            Hari Ini ({{ $todayIndo }})</h2>
                    </div>
                    <div class="small fw-medium" style="color: #475569;">
                        Siklus: <strong class="text-primary">{{ $config->active_cycle ?? 'Putaran 1' }}</strong>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead
                                style="background-color: #f8fafc; color: #334155; font-size: 0.82rem; text-transform: uppercase; letter-spacing: 0.04em;">
                                <tr>
                                    <th style="width: 170px;" class="py-3 px-4">Jam</th>
                                    <th class="py-3 px-3">Mata Pelajaran</th>
                                    <th class="py-3 px-3">Guru Pengampu</th>
                                    <th style="width: 140px;" class="py-3 px-3">Kelas</th>
                                    <th style="width: 140px;" class="py-3 px-4">Ruangan</th>
                                </tr>
                            </thead>
                            <tbody style="font-size: 0.88rem;">
                                @php $lastClassId = null; @endphp
                                @forelse($todaySchedules as $schedule)
                                    @if($lastClassId !== $schedule->class_id)
                                        @php $lastClassId = $schedule->class_id; @endphp
                                        <tr>
                                            <td colspan="5" class="fw-bold py-2.5 px-4"
                                                style="background-color: #eff6ff; color: #1e40af; font-size: 0.84rem; letter-spacing: 0.03em;">
                                                JADWAL KELAS:
                                                {{ strtoupper($schedule->schoolClass->class_name ?? 'Kelas ' . $schedule->class_id) }}
                                            </td>
                                        </tr>
                                    @endif
                                    <tr>
                                        <td class="tabular-time py-3 px-4 text-primary fw-medium" style="white-space: nowrap;">
                                            {{ substr($schedule->start_time, 0, 5) }} - {{ substr($schedule->end_time, 0, 5) }}
                                        </td>
                                        <td class="fw-semibold text-dark py-3 px-3">{{ $schedule->subject }}</td>
                                        <td class="py-3 px-3 text-secondary" style="color: #334155 !important;">
                                            {{ $schedule->teacher->fullname ?? '-' }}</td>
                                        <td class="py-3 px-3"><span class="fw-semibold text-dark"
                                                style="font-size: 0.875rem;">{{ $schedule->schoolClass->class_name ?? '-' }}</span>
                                        </td>
                                        <td class="py-3 px-4"><span class="fw-medium text-dark"
                                                style="font-size: 0.875rem;">{{ $schedule->room->room_name ?? '-' }}</span></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            <i class="bi bi-calendar-x d-block fs-2 mb-2 text-secondary opacity-50"></i>
                                            <div class="fw-medium text-dark">Belum ada jadwal mengajar pada hari
                                                {{ $todayIndo }} ({{ $config->active_cycle ?? 'Putaran 1' }}).</div>
                                            <div class="small text-secondary mt-1">Gunakan menu <strong>Jadwal
                                                    Pelajaran</strong> untuk menambahkan jadwal baru.</div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
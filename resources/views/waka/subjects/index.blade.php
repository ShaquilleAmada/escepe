@extends('layouts.app')

@section('title', 'Data Master Mata Pelajaran')

@section('content')
  <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-3">
    <div>
      <h1 class="fw-bold mb-1 text-dark heading-section" style="font-size: 1.5rem;">Data Master Mata Pelajaran</h1>
      <p class="text-secondary small mb-0">Daftar mata pelajaran beserta guru pengampu dan rombel yang terdata pada
        database jadwal mengajar sekolah.</p>
    </div>
  </div>

  {{-- ============================================================
  3 STAT OVERVIEW CARDS
  ============================================================ --}}
  <div class="row g-2 mb-4">
    <div class="col-12 col-md-4">
      <div class="card border shadow-xs h-100 p-2 text-center" style="border-left: 3px solid #2563eb !important;">
        <span class="text-secondary small fw-medium">Total Mata Pelajaran</span>
        <span class="fw-bold text-primary fs-5">{{ number_format($totalSubjects) }}</span>
      </div>
    </div>

    <div class="col-12 col-md-4">
      <div class="card border shadow-xs h-100 p-2 text-center" style="border-left: 3px solid #2563eb !important;">
        <span class="text-secondary small fw-medium">Guru Pengampu Aktif</span>
        <span class="fw-bold text-primary fs-5">{{ number_format($totalTeachers) }}</span>
      </div>
    </div>

    <div class="col-12 col-md-4">
      <div class="card border shadow-xs h-100 p-2 text-center" style="border-left: 3px solid #2563eb !important;">
        <span class="text-secondary small fw-medium">Total Sesi Pertemuan</span>
        <span class="fw-bold text-primary fs-5">{{ number_format($totalSessions) }}</span>
      </div>
    </div>
  </div>

  {{-- ============================================================
  FILTER & PENCARIAN
  ============================================================ --}}
  <div class="card border shadow-sm mb-4">
    <div class="card-body p-3">
      <form method="GET" action="{{ route('waka.subjects.index') }}" class="row g-2 align-items-center">
        <div class="col-12 col-md-5">
          <div class="input-group input-group-sm">
            <span class="input-group-text bg-light border-end-0 text-muted">
              <i class="bi bi-search"></i>
            </span>
            <input type="text" name="search" class="form-control border-start-0"
              placeholder="Cari nama mata pelajaran / guru..." value="{{ request('search') }}">
          </div>
        </div>

        <div class="col-12 col-sm-6 col-md-4">
          <select name="teacher_id" class="form-select form-select-sm">
            <option value="">Semua Guru Pengampu</option>
            @foreach($teachers as $t)
              <option value="{{ $t->id }}" {{ request('teacher_id') == $t->id ? 'selected' : '' }}>
                {{ $t->fullname }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="col-12 col-sm-6 col-md-3 d-flex gap-2">
          <button type="submit" class="btn btn-sm btn-primary flex-grow-1">
            <i class="bi bi-filter me-1"></i> Filter
          </button>
          @if(request()->filled('search') || request()->filled('teacher_id'))
            <a href="{{ route('waka.subjects.index') }}" class="btn btn-sm btn-outline-secondary" title="Reset Filter">
              <i class="bi bi-arrow-counterclockwise"></i>
            </a>
          @endif
        </div>
      </form>
    </div>
  </div>

  {{-- ============================================================
  TABEL DAFTAR MATA PELAJARAN
  ============================================================ --}}
  <div class="card border shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
      <div>
        <span class="fw-bold text-dark">Daftar Mata Pelajaran &amp; Guru Pengampu</span>
      </div>
      <span class="text-secondary small">Total: {{ $subjects->count() }} Mata Pelajaran</span>
    </div>

    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="bg-light">
            <tr>
              <th style="width: 50px;" class="text-center">No</th>
              <th style="min-width: 220px;">Mata Pelajaran</th>
              <th style="min-width: 220px;">Guru Pengampu</th>
              <th style="min-width: 190px;">Rombel / Kelas</th>
              <th style="min-width: 130px;">Ruang Belajar</th>
              <th style="min-width: 110px;" class="text-center">Beban Sesi</th>
              <th style="width: 100px;" class="text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            @forelse($subjects as $index => $item)
              @php
                $modalId = 'detailModal_' . md5($item->subject);
                $editModalId = 'editModal_' . md5($item->subject);
                $teachersModalId = 'teachersModal_' . md5($item->subject);
                $classesModalId = 'classesModal_' . md5($item->subject);
                $teachersCount = $item->teachers->count();
                $classesCount = $item->classes->count();
                $roomsCount = $item->rooms->count();
              @endphp
              <tr>
                <td class="text-center text-muted small fw-semibold">{{ $index + 1 }}</td>
                <td>
                  <div class="fw-bold text-dark" style="font-size: 0.88rem; line-height: 1.35;">
                    {{ $item->subject }}
                  </div>
                  @if(str_contains(strtoupper($item->subject), 'KK') || str_contains(strtoupper($item->subject), 'RPL') || str_contains(strtoupper($item->subject), 'TKJ') || str_contains(strtoupper($item->subject), 'DKV') || str_contains(strtoupper($item->subject), 'AK') || str_contains(strtoupper($item->subject), 'BD') || str_contains(strtoupper($item->subject), 'BR') || str_contains(strtoupper($item->subject), 'MP') || str_contains(strtoupper($item->subject), 'LPS') || str_contains(strtoupper($item->subject), 'DDPK'))
                    <div class="text-secondary small mt-0.5" style="font-size: 0.75rem;">Kejuruan</div>
                  @else
                    <div class="text-secondary small mt-0.5" style="font-size: 0.75rem;">Normatif / Adaptif</div>
                  @endif
                </td>
                <td>
                  @if($teachersCount > 0)
                    <a href="javascript:void(0)" class="text-primary fw-semibold text-decoration-none" data-bs-toggle="modal"
                      data-bs-target="#{{ $teachersModalId }}" title="Lihat {{ $teachersCount }} guru pengampu">
                      Lihat Guru
                    </a>
                  @else
                    <span class="text-muted small fst-italic">Belum ada</span>
                  @endif
                </td>
                <td>
                  @if($classesCount > 0)
                    <a href="javascript:void(0)" class="text-primary fw-semibold text-decoration-none" data-bs-toggle="modal"
                      data-bs-target="#{{ $classesModalId }}" title="Lihat {{ $classesCount }} kelas / rombel">
                      Lihat Kelas
                    </a>
                  @else
                    <span class="text-muted small fst-italic">-</span>
                  @endif
                </td>
                <td>
                  @if($roomsCount === 1)
                    <span class="text-dark small">{{ $item->rooms->first()->room_name }}</span>
                  @elseif($roomsCount > 1)
                    <a href="javascript:void(0)" class="text-dark small text-decoration-none" data-bs-toggle="modal"
                      data-bs-target="#{{ $modalId }}" title="Klik untuk lihat jadwal & ruangan">{{ $roomsCount }} Ruangan</a>
                  @else
                    <span class="text-muted small">-</span>
                  @endif
                </td>
                <td class="text-center">
                  <span class="text-dark small fw-medium text-nowrap">
                    {{ $item->total_sessions }} Sesi/Mg
                  </span>
                </td>
                <td class="text-center">
                  <div class="d-flex align-items-center justify-content-center gap-1">
                    {{-- Tombol Rincian Jadwal --}}
                    <button type="button"
                      class="btn btn-sm btn-outline-primary d-inline-flex align-items-center justify-content-center rounded-2 shadow-none"
                      style="width: 32px; height: 32px;" data-bs-toggle="modal" data-bs-target="#{{ $classesModalId }}"
                      title="Lihat Rincian Kelas">
                      <i class="bi bi-eye"></i>
                    </button>

                    {{-- Tombol Edit Nama Mapel --}}
                    <button type="button"
                      class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center justify-content-center rounded-2 shadow-none"
                      style="width: 32px; height: 32px;" data-bs-toggle="modal" data-bs-target="#{{ $editModalId }}"
                      title="Ubah Nama Mata Pelajaran">
                      <i class="bi bi-pencil-square"></i>
                    </button>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center py-5 text-muted">
                  <i class="bi bi-journal-x fs-1 d-block mb-2 text-secondary opacity-50"></i>
                  Tidak ada data mata pelajaran yang sesuai dengan pencarian.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- ============================================================
  MODALS: RINCIAN JADWAL & EDIT MAPEL
  ============================================================ --}}
  @foreach($subjects as $item)
    @php
      $modalId = 'detailModal_' . md5($item->subject);
      $editModalId = 'editModal_' . md5($item->subject);
      $teachersModalId = 'teachersModal_' . md5($item->subject);
      $classesModalId = 'classesModal_' . md5($item->subject);
    @endphp

    {{-- Modal 0: Daftar Guru Pengampu --}}
    <div class="modal fade" id="{{ $teachersModalId }}" tabindex="-1" aria-labelledby="{{ $teachersModalId }}Label"
      aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
          <div class="modal-header bg-light border-bottom px-4 py-3">
            <div>
              <h5 class="modal-title fw-bold text-dark mb-0" id="{{ $teachersModalId }}Label" style="font-size: 1.05rem;">
                Daftar Guru Pengampu: {{ $item->subject }}
              </h5>
              <span class="text-muted small">Total: {{ $item->teachers->count() }} guru pengampu aktif</span>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-0" style="max-height: 450px; overflow-y: auto;">
            <div class="table-responsive">
              <table class="table table-hover table-sm align-middle mb-0">
                <thead class="bg-light">
                  <tr>
                    <th class="ps-4 py-2" style="width: 50px;">No</th>
                    <th class="py-2">Nama Lengkap Guru</th>
                    <th class="py-2">NIP / Identitas</th>
                    <th class="pe-4 py-2">Kelas yang Diajar</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($item->teachers as $tIdx => $teacher)
                    @php
                      $tSchedules = $item->schedules->where('teacher_id', $teacher->id);
                      $tClasses = $tSchedules->pluck('schoolClass.class_name')->filter()->unique()->values();
                    @endphp
                    <tr>
                      <td class="ps-4 text-muted small fw-semibold">{{ $tIdx + 1 }}</td>
                      <td>
                        <span class="fw-semibold text-dark">{{ $teacher->fullname }}</span>
                      </td>
                      <td class="text-secondary small">{{ $teacher->nip_nisn ?? $teacher->username }}</td>
                      <td class="pe-4">
                        <span class="text-dark small">{{ $tClasses->isNotEmpty() ? $tClasses->implode(', ') : '-' }}</span>
                      </td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="4" class="text-center py-4 text-muted small">Belum ada guru pengampu terdaftar.</td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>
          <div class="modal-footer bg-light px-4 py-2.5 border-top">
            <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Tutup</button>
          </div>
        </div>
      </div>
    </div>

    {{-- Modal 0B: Daftar Rombel / Kelas (Hanya Kolom: Kelas, Ruangan, Siklus, Guru Pengampu) --}}
    <div class="modal fade" id="{{ $classesModalId }}" tabindex="-1" aria-labelledby="{{ $classesModalId }}Label"
      aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
          <div class="modal-header bg-light border-bottom px-4 py-3">
            <div>
              <h5 class="modal-title fw-bold text-dark mb-0" id="{{ $classesModalId }}Label" style="font-size: 1.05rem;">
                Daftar Rombel / Kelas: {{ $item->subject }}
              </h5>
              <span class="text-muted small">Total: {{ $item->classes->count() }} rombel &bull; {{ $item->teachers->count() }} guru pengampu</span>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-0" style="max-height: 480px; overflow-y: auto;">
            <div class="table-responsive">
              <table class="table table-hover table-sm align-middle mb-0">
                <thead class="bg-light">
                  <tr>
                    <th class="ps-4 py-2">Kelas</th>
                    <th class="py-2">Ruangan</th>
                    <th class="py-2">Siklus</th>
                    <th class="pe-4 py-2">Guru Pengampu</th>
                  </tr>
                </thead>
                <tbody>
                  @php
                    $classSchedules = $item->schedules->unique(function ($s) {
                        return ($s->schoolClass->class_name ?? '') . '|' . ($s->room->room_name ?? '') . '|' . ($s->cycle_type ?? '') . '|' . ($s->teacher->fullname ?? '');
                    })->sortBy(function ($s) {
                        return $s->schoolClass->class_name ?? '';
                    });
                  @endphp
                  @forelse($classSchedules as $sch)
                    <tr>
                      <td class="ps-4 fw-semibold text-dark">{{ $sch->schoolClass->class_name ?? '-' }}</td>
                      <td class="text-secondary small">{{ $sch->room->room_name ?? '-' }}</td>
                      <td><span class="text-dark small">{{ $sch->cycle_type ?? '-' }}</span></td>
                      <td class="pe-4 fw-medium text-dark small">{{ $sch->teacher->fullname ?? '-' }}</td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="4" class="text-center py-4 text-muted small">Belum ada data kelas untuk mata pelajaran ini.</td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>
          <div class="modal-footer bg-light px-4 py-2.5 border-top">
            <a href="{{ route('waka.schedules.index') }}" class="btn btn-outline-primary btn-sm px-3" style="font-size: 0.8rem;">
              <i class="bi bi-box-arrow-up-right me-1"></i> Buka Manajemen Jadwal
            </a>
            <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Tutup</button>
          </div>
        </div>
      </div>
    </div>

    {{-- Modal 2: Edit Nama Mapel --}}
    <div class="modal fade" id="{{ $editModalId }}" tabindex="-1" aria-labelledby="{{ $editModalId }}Label"
      aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
          <form action="{{ route('waka.subjects.update') }}" method="POST">
            @csrf
            @method('PUT')
            <input type="hidden" name="old_subject" value="{{ $item->subject }}">

            <div class="modal-header bg-light border-bottom px-4 py-3">
              <h5 class="modal-title fw-bold text-dark mb-0" id="{{ $editModalId }}Label" style="font-size: 1rem;">
                Ubah Nama Mata Pelajaran
              </h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
              <p class="text-secondary small mb-3" style="font-size: 0.82rem; line-height: 1.45;">
                Perubahan nama mapel ini akan otomatis diperbarui pada <strong class="text-dark">{{ $item->total_sessions }}
                  jadwal mengajar</strong> yang menggunakan nama mata pelajaran ini.
              </p>

              <div class="mb-3">
                <label class="form-label small fw-semibold text-secondary">Nama Mata Pelajaran Saat Ini</label>
                <input type="text" class="form-control form-control-sm bg-light text-muted" value="{{ $item->subject }}"
                  readonly>
              </div>

              <div class="mb-2">
                <label class="form-label small fw-semibold text-dark">Nama Mata Pelajaran Baru</label>
                <input type="text" name="new_subject" class="form-control" value="{{ $item->subject }}" required
                  maxlength="100">
              </div>
            </div>

            <div class="modal-footer bg-light px-4 py-3 border-top">
              <button type="button" class="btn btn-secondary px-3 py-2 fw-medium" style="font-size: 0.875rem;"
                data-bs-dismiss="modal">Batal</button>
              <button type="submit" class="btn btn-primary text-white px-3 py-2 fw-medium" style="font-size: 0.875rem;">
                <i class="bi bi-check2 me-1"></i> Simpan Perubahan
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  @endforeach

@endsection
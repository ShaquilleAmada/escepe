@extends('layouts.app')

@section('title', 'Edit Jadwal Pelajaran')

@section('content')
<!-- Header Page -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 pb-2 border-bottom gap-2">
    <div>
        <a href="{{ route('waka.schedules.index') }}" class="text-decoration-none text-secondary small d-inline-flex align-items-center gap-1 mb-2 fw-medium">
            <i class="bi bi-arrow-left"></i> Kembali ke Daftar Jadwal
        </a>
        <h1 class="h4 fw-bold mb-1 text-dark">Edit Jadwal Pelajaran</h1>
        <p class="text-secondary small mb-0">Perbarui data jadwal mengajar kelas.</p>
    </div>
</div>

<div class="row g-4 align-items-start">
    <!-- Kolom Kiri: Formulir Jadwal -->
    <div class="col-12 col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom">
                <h2 class="h6 fw-bold text-dark mb-0">Formulir Edit Jadwal</h2>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('waka.schedules.update', $schedule->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <!-- Mata Pelajaran -->
                    <div class="mb-3">
                        <label for="subject" class="form-label small fw-semibold text-dark">
                            Mata Pelajaran <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control form-control-lg fs-6 @error('subject') is-invalid @enderror" 
                               id="subject" 
                               name="subject" 
                               value="{{ old('subject', $schedule->subject) }}" 
                               placeholder="Contoh: Pemrograman Web dan Perangkat Bergerak" 
                               required>
                        @error('subject')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Guru Pengampu & Kelas -->
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label for="teacher_id" class="form-label small fw-semibold text-dark">
                                Guru Pengampu <span class="text-danger">*</span>
                            </label>
                            <select name="teacher_id" id="teacher_id" class="form-select @error('teacher_id') is-invalid @enderror" required>
                                <option value="">-- Pilih Guru --</option>
                                @foreach($teachers as $t)
                                <option value="{{ $t->id }}" {{ old('teacher_id', $schedule->teacher_id) == $t->id ? 'selected' : '' }}>
                                    {{ $t->fullname }} ({{ $t->username }})
                                </option>
                                @endforeach
                            </select>
                            @error('teacher_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="class_id" class="form-label small fw-semibold text-dark">
                                Kelas <span class="text-danger">*</span>
                            </label>
                            <select name="class_id" id="class_id" class="form-select @error('class_id') is-invalid @enderror" required>
                                <option value="">-- Pilih Kelas --</option>
                                @foreach($classes as $c)
                                <option value="{{ $c->id }}" {{ old('class_id', $schedule->class_id) == $c->id ? 'selected' : '' }}>
                                    {{ $c->class_name }}
                                </option>
                                @endforeach
                            </select>
                            @error('class_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Ruangan & Siklus Putaran -->
                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label for="room_id" class="form-label small fw-semibold text-dark">
                                Ruangan / Lab <span class="text-danger">*</span>
                            </label>
                            <select name="room_id" id="room_id" class="form-select @error('room_id') is-invalid @enderror" required>
                                <option value="">-- Pilih Ruangan --</option>
                                @foreach($rooms as $r)
                                <option value="{{ $r->id }}" {{ old('room_id', $schedule->room_id) == $r->id ? 'selected' : '' }}>
                                    {{ $r->room_name }}
                                </option>
                                @endforeach
                            </select>
                            @error('room_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="cycle_type" class="form-label small fw-semibold text-dark">
                                Siklus Putaran <span class="text-danger">*</span>
                            </label>
                            <select name="cycle_type" id="cycle_type" class="form-select @error('cycle_type') is-invalid @enderror" required>
                                <option value="Putaran 1" {{ old('cycle_type', $schedule->cycle_type) == 'Putaran 1' ? 'selected' : '' }}>Putaran 1</option>
                                <option value="Putaran 2" {{ old('cycle_type', $schedule->cycle_type) == 'Putaran 2' ? 'selected' : '' }}>Putaran 2</option>
                                <option value="semua" {{ old('cycle_type', $schedule->cycle_type) == 'semua' ? 'selected' : '' }}>Semua Siklus (Setiap Minggu)</option>
                            </select>
                            @error('cycle_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Hari -->
                    <div class="mb-3">
                        <label for="day_of_week" class="form-label small fw-semibold text-dark">
                            Hari <span class="text-danger">*</span>
                        </label>
                        <select name="day_of_week" id="day_of_week" class="form-select @error('day_of_week') is-invalid @enderror" required>
                            @foreach(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'] as $d)
                            <option value="{{ $d }}" {{ old('day_of_week', $schedule->day_of_week) == $d ? 'selected' : '' }}>{{ $d }}</option>
                            @endforeach
                        </select>
                        @error('day_of_week')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Sesi Jam Pelajaran: Dari Jam Ke- s/d Sampai Jam Ke- -->
                    @php
                        $initStartPeriod = old('start_period', $schedule->start_period);
                        $initEndPeriod = old('end_period', $schedule->end_period);
                        $initStartTime = old('start_time', substr($schedule->start_time, 0, 5));
                        $initEndTime = old('end_time', substr($schedule->end_time, 0, 5));
                    @endphp
                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-6">
                            <label for="start_period" class="form-label small fw-semibold text-dark">
                                Dari Jam Ke- <span class="text-danger">*</span>
                            </label>
                            <select name="start_period" id="start_period" class="form-select @error('start_period') is-invalid @enderror" required>
                                <option value="">-- Pilih Jam Mulai --</option>
                            </select>
                            @error('start_period')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="end_period" class="form-label small fw-semibold text-dark">
                                Sampai Jam Ke- <span class="text-danger">*</span>
                            </label>
                            <select name="end_period" id="end_period" class="form-select @error('end_period') is-invalid @enderror" required>
                                <option value="">-- Pilih Jam Selesai --</option>
                            </select>
                            @error('end_period')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Hidden start_time & end_time values sent to server -->
                    <input type="hidden" name="start_time" id="start_time" value="{{ $initStartTime }}">
                    <input type="hidden" name="end_time" id="end_time" value="{{ $initEndTime }}">

                    <!-- Tombol Aksi -->
                    <div class="d-flex align-items-center justify-content-end gap-2 border-top pt-3">
                        <a href="{{ route('waka.schedules.index') }}" class="btn btn-outline-secondary px-4">
                            Batal
                        </a>
                        <button type="submit" class="btn btn-primary px-4 fw-semibold shadow-sm d-inline-flex align-items-center gap-2">
                            <i class="bi bi-save"></i>
                            <span>Perbarui Jadwal</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Kolom Kanan: Aturan Anti-Bentrok & Jadwal Bell Sekolah -->
    <div class="col-12 col-lg-4">
        <!-- Card Aturan Anti-Bentrok -->
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-white py-3 border-bottom">
                <h2 class="h6 fw-bold text-dark mb-0">Aturan Anti-Bentrok Sistem</h2>
            </div>
            <div class="card-body p-3">
                <p class="small text-secondary mb-2">
                    Sistem memeriksa tabrakan waktu sebelum menyimpan perubahan:
                </p>

                <ul class="small text-secondary ps-3 mb-0 d-flex flex-column gap-1.5">
                    <li>
                        <strong class="text-dark">Ruangan / Lab:</strong> Tidak boleh digunakan 2 kelas berbeda di jam &amp; hari yang sama.
                    </li>
                    <li>
                        <strong class="text-dark">Rombel:</strong> Kelas tidak boleh memiliki 2 jadwal bersamaan.
                    </li>
                    <li>
                        <strong class="text-dark">Guru Pengampu:</strong> Guru tidak dapat mengajar di 2 tempat di jam yang sama.
                    </li>
                </ul>
            </div>
        </div>

        <!-- Card Alokasi Waktu Hari Terpilih -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-2.5 px-3 border-bottom d-flex justify-content-between align-items-center">
                <h2 class="h6 fw-bold text-dark mb-0">Alokasi Waktu: <span id="timetable_day_title" class="text-primary">{{ $schedule->day_of_week }}</span></h2>
            </div>
            <div class="card-body p-0" style="max-height: 270px; overflow-y: auto;">
                <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.8rem;">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th class="ps-3" style="width: 85px;">Sesi</th>
                            <th>Pukul</th>
                            <th class="pe-3 text-end">Durasi</th>
                        </tr>
                    </thead>
                    <tbody id="timetable_tbody">
                        <!-- Populated by JS -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const dailyPeriods = @json($dailyPeriods ?? []);
    const daySelect = document.getElementById('day_of_week');
    const startPeriodSelect = document.getElementById('start_period');
    const endPeriodSelect = document.getElementById('end_period');
    const startTimeInput = document.getElementById('start_time');
    const endTimeInput = document.getElementById('end_time');
    const timetableTitle = document.getElementById('timetable_day_title');
    const timetableTbody = document.getElementById('timetable_tbody');

    let initialStartPeriod = "{{ $initStartPeriod ?? '' }}";
    let initialEndPeriod = "{{ $initEndPeriod ?? '' }}";
    const initialStartTime = "{{ $initStartTime }}";
    const initialEndTime = "{{ $initEndTime }}";

    function updatePeriodsForDay(selectedDay) {
        const periods = dailyPeriods[selectedDay] || {};
        const periodKeys = Object.keys(periods).map(Number).sort((a,b) => a - b);

        if (timetableTitle) timetableTitle.textContent = selectedDay;

        // Update Timetable Right Column
        if (timetableTbody) {
            let html = '';
            periodKeys.forEach(p => {
                const info = periods[p];
                const durText = info.label && info.label.includes('35') ? '35 menit' : '40 menit';
                html += `<tr>
                    <td class="ps-3 fw-semibold text-dark">Jam Ke-${p}</td>
                    <td>${info.start} - ${info.end}</td>
                    <td class="pe-3 text-end text-secondary">${durText}</td>
                </tr>`;
            });
            timetableTbody.innerHTML = html;
        }

        // Jika belum ada period, cari dari jam start/end
        if (!initialStartPeriod && initialStartTime) {
            periodKeys.forEach(p => {
                if (periods[p].start === initialStartTime) initialStartPeriod = String(p);
                if (periods[p].end === initialEndTime) initialEndPeriod = String(p);
            });
        }

        const currentStart = startPeriodSelect.value || initialStartPeriod;
        const currentEnd = endPeriodSelect.value || initialEndPeriod;

        startPeriodSelect.innerHTML = '<option value="">-- Pilih Jam Mulai --</option>';
        endPeriodSelect.innerHTML = '<option value="">-- Pilih Jam Selesai --</option>';

        periodKeys.forEach(p => {
            const info = periods[p];
            const startOpt = document.createElement('option');
            startOpt.value = p;
            startOpt.textContent = `Jam Ke-${p} (${info.start} - ${info.end})`;
            if (String(p) === String(currentStart)) startOpt.selected = true;
            startPeriodSelect.appendChild(startOpt);

            const endOpt = document.createElement('option');
            endOpt.value = p;
            endOpt.textContent = `Jam Ke-${p} (s/d ${info.end})`;
            if (String(p) === String(currentEnd)) endOpt.selected = true;
            endPeriodSelect.appendChild(endOpt);
        });

        filterEndPeriods();
        calculateTimes();
    }

    function filterEndPeriods() {
        const startVal = parseInt(startPeriodSelect.value);
        Array.from(endPeriodSelect.options).forEach(opt => {
            if (!opt.value) return;
            const endVal = parseInt(opt.value);
            if (startVal && endVal < startVal) {
                opt.disabled = true;
                opt.classList.add('text-muted');
            } else {
                opt.disabled = false;
                opt.classList.remove('text-muted');
            }
        });

        if (startVal && (!endPeriodSelect.value || parseInt(endPeriodSelect.value) < startVal)) {
            endPeriodSelect.value = startVal;
        }
    }

    function calculateTimes() {
        const selectedDay = daySelect.value;
        const periods = dailyPeriods[selectedDay] || {};
        const sp = parseInt(startPeriodSelect.value);
        const ep = parseInt(endPeriodSelect.value);

        if (sp && ep && periods[sp] && periods[ep]) {
            startTimeInput.value = periods[sp].start;
            endTimeInput.value = periods[ep].end;
        }
    }

    daySelect.addEventListener('change', function () {
        initialStartPeriod = '';
        initialEndPeriod = '';
        updatePeriodsForDay(this.value);
    });

    startPeriodSelect.addEventListener('change', function () {
        filterEndPeriods();
        calculateTimes();
    });

    endPeriodSelect.addEventListener('change', function () {
        calculateTimes();
    });

    updatePeriodsForDay(daySelect.value);
});
</script>
@endpush

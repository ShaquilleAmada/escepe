<?php

namespace App\Http\Controllers\Web\Waka;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Classes;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\Journal;
use App\Models\SystemConfiguration;
use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Ambil atau Buat Konfigurasi Sistem
        $config = SystemConfiguration::first();
        if (!$config) {
            $config = SystemConfiguration::create([
                'active_cycle' => 'Putaran 1',
                'is_schedule_locked' => false,
                'updated_by' => Auth::id() ?? 1,
            ]);
        }

        // 2. Data Statistik (Guru Pengampu mencakup Guru Mapel, BK, dan Wali Kelas)
        $totalUsers = User::count();
        $totalTeachers = User::whereIn('role_id', [Role::GURU, Role::BK, Role::WALI_KELAS])->count();
        $totalStudents = User::where('role_id', Role::SISWA)->count();
        $totalClasses = Classes::count();
        $totalRooms = Room::count();

        // 3. Mapping Hari Bahasa Indonesia
        $dayMap = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ];
        $now = Carbon::now('Asia/Jakarta');
        $currentDayNumber = (int) $now->format('N');
        $todayIndo = $dayMap[$currentDayNumber] ?? 'Senin';
        $todayDate = $now->format('Y-m-d');
        $currentTime = $now->format('H:i:s');
        $timeThreshold = $now->copy()->subMinutes(30)->format('H:i:s');

        // 4. Jadwal Hari Ini Berdasarkan Siklus Aktif
        $todaySchedules = Schedule::with(['teacher', 'schoolClass', 'room'])
            ->where('day_of_week', $todayIndo)
            ->where(function ($query) use ($config) {
                $query->where('cycle_type', $config->active_cycle)
                      ->orWhere('cycle_type', 'semua');
            })
            ->orderBy('class_id', 'asc')
            ->orderBy('start_time', 'asc')
            ->get();

        // 5. Logika Monitoring Aktivitas Mengajar & Jurnal Hari Ini
        $monitoringJournals = [];
        $todayJournalsCount = 0;

        foreach ($todaySchedules as $sch) {
            $hasJournal = Journal::where('schedule_id', $sch->id)
                ->whereDate('date', $todayDate)
                ->exists();

            $startCarbon = Carbon::parse($todayDate . ' ' . $sch->start_time, 'Asia/Jakarta');
            $endCarbon = Carbon::parse($todayDate . ' ' . $sch->end_time, 'Asia/Jakarta');
            $graceEnd = $endCarbon->copy()->addMinutes(30);

            if ($hasJournal) {
                $todayJournalsCount++;
                $sch->current_status = 'Selesai';
                $sch->status_badge = 'dot-badge-success';

                $statusKey = 'completed';
                $statusText = 'Sudah Mengisi Jurnal';
                $statusColor = '#16a34a'; // green
                $statusBadge = 'bg-success-subtle text-success border border-success-subtle';
                $statusIcon = 'bi-check-circle-fill';
                $sortOrder = 4;
            } else {
                if ($now->lt($startCarbon)) {
                    $sch->current_status = 'Menunggu';
                    $sch->status_badge = 'dot-badge-neutral';

                    $statusKey = 'upcoming';
                    $statusText = 'Belum Dimulai';
                    $statusColor = '#64748b'; // slate
                    $statusBadge = 'bg-light text-secondary border';
                    $statusIcon = 'bi-hourglass-split';
                    $sortOrder = 3;
                } elseif ($now->lte($graceEnd)) {
                    // Sedang jam mengajar atau baru selesai dalam batas toleransi 30 menit
                    $sch->current_status = 'Berlangsung';
                    $sch->status_badge = 'dot-badge-primary';

                    $statusKey = 'teaching';
                    $statusText = 'Sedang Mengajar';
                    $statusColor = '#2563eb'; // blue
                    $statusBadge = 'bg-primary-subtle text-primary border border-primary-subtle';
                    $statusIcon = 'bi-person-video3';
                    $sortOrder = 2;
                } else {
                    // Jam selesai sudah lewat lebih dari 30 menit dan belum ada catatan jurnal
                    $sch->current_status = 'Belum Diisi';
                    $sch->status_badge = 'dot-badge-danger';

                    $statusKey = 'overdue';
                    $statusText = 'Belum Mengisi Jurnal';
                    $statusColor = '#dc2626'; // red
                    $statusBadge = 'bg-danger-subtle text-danger border border-danger-subtle';
                    $statusIcon = 'bi-exclamation-circle-fill';
                    $sortOrder = 1;
                }
            }

            $lateMinutes = $now->gt($endCarbon) ? (int) $endCarbon->diffInMinutes($now) : 0;

            $monitoringJournals[] = [
                'schedule' => $sch,
                'teacher_name' => $sch->teacher->fullname ?? 'Guru',
                'subject' => $sch->subject,
                'class_name' => $sch->schoolClass->class_name ?? '-',
                'room_name' => $sch->room->room_name ?? '-',
                'time' => substr($sch->start_time, 0, 5) . ' - ' . substr($sch->end_time, 0, 5),
                'end_time' => substr($sch->end_time, 0, 5),
                'late_minutes' => $lateMinutes,
                'status_key' => $statusKey,
                'status_text' => $statusText,
                'status_color' => $statusColor,
                'status_badge' => $statusBadge,
                'status_icon' => $statusIcon,
                'sort_order' => $sortOrder,
            ];
        }

        // Urutkan prioritas: Belum Mengisi Jurnal (Overdue) paling atas, lalu Sedang Mengajar, Belum Dimulai, dan Selesai
        usort($monitoringJournals, function ($a, $b) {
            if ($a['sort_order'] === $b['sort_order']) {
                return strcmp($a['time'], $b['time']);
            }
            return $a['sort_order'] <=> $b['sort_order'];
        });

        $overdueJournals = $monitoringJournals;
        $teachingCount = collect($monitoringJournals)->where('status_key', 'teaching')->count();
        $overdueCount = collect($monitoringJournals)->where('status_key', 'overdue')->count();
        $completedCount = collect($monitoringJournals)->where('status_key', 'completed')->count();

        // 6. Riwayat Siklus Blok (Cycle Histories)
        $cycleHistories = \App\Models\CycleHistory::with('user')
            ->orderBy('id', 'desc')
            ->take(6)
            ->get();

        // 7. Matriks Pemetaan Status Kelas Berdasarkan Siklus Aktif (Dinamis untuk seluruh rombel)
        $activeCycle = $config->active_cycle ?? 'Putaran 1';
        
        $matrixClasses = Classes::whereHas('schedules', function ($q) use ($activeCycle) {
            $q->where('cycle_type', $activeCycle)->orWhere('cycle_type', 'semua');
        })
        ->orderByRaw("FIELD(grade_level, 'X', 'XI', 'XII')")
        ->orderBy('major')
        ->orderBy('section')
        ->orderBy('class_name')
        ->get();

        $classStatusMatrix = [];
        foreach ($matrixClasses as $c) {
            $schedules = Schedule::where('class_id', $c->id)
                ->where(function ($q) use ($activeCycle) {
                    $q->where('cycle_type', $activeCycle)->orWhere('cycle_type', 'semua');
                })
                ->with('room')
                ->get();

            $hasLabOrKK = $schedules->first(function ($s) {
                $sub = strtolower($s->subject);
                $rm = strtolower($s->room->room_name ?? '');
                return str_contains($sub, 'kk ') || str_contains($sub, 'ddpk') || str_contains($sub, 'praktik') || str_contains($rm, 'lab');
            });

            $statusType = $hasLabOrKK ? 'Praktik (Kejuruan)' : 'Teori (Normada)';
            $rooms = $schedules->pluck('room.room_name')->filter()->unique()->values();
            $roomStr = $rooms->isNotEmpty() ? $rooms->take(2)->implode(', ') : 'Ruang Kelas';

            $subjects = $schedules->pluck('subject')->filter()->unique()->values();
            $focusStr = $subjects->isNotEmpty() ? $subjects->take(3)->implode(', ') : 'KBM Pembelajaran';

            $classStatusMatrix[] = [
                'class_name' => $c->class_name,
                'status_type' => $statusType,
                'badge_class' => 'bg-light text-dark border',
                'location' => $roomStr,
                'current_focus' => $focusStr,
            ];
        }

        $classMatrix = $classStatusMatrix;

        return view('waka.dashboard', compact(
            'config',
            'totalUsers',
            'totalTeachers',
            'totalStudents',
            'totalClasses',
            'totalRooms',
            'todayIndo',
            'todayDate',
            'todaySchedules',
            'todayJournalsCount',
            'overdueJournals',
            'monitoringJournals',
            'teachingCount',
            'overdueCount',
            'completedCount',
            'cycleHistories',
            'classStatusMatrix',
            'classMatrix'
        ));
    }

    public function toggleCycle(Request $request)
    {
        $request->validate([
            'active_cycle' => 'required|in:Putaran 1,Putaran 2',
        ]);

        $config = SystemConfiguration::first();
        if (!$config) {
            $config = new SystemConfiguration();
        }

        // ✅ REVISI GURU: Blokir perubahan putaran jika jadwal sedang terkunci
        if ($config->is_schedule_locked) {
            return redirect()->back()->with('error', 'Sistem putaran tidak dapat diubah karena jadwal sedang terkunci (Lock Jadwal Aktif). Buka kunci jadwal terlebih dahulu untuk mengganti putaran siklus.');
        }

        $oldCycle = $config->active_cycle;
        $newCycle = $request->active_cycle;

        if ($oldCycle !== $newCycle) {
            $config->active_cycle = $newCycle;
            $config->updated_by = Auth::id();
            $config->save();

            // Catat Riwayat Perputaran Siklus Baru
            \App\Models\CycleHistory::create([
                'cycle_name' => $newCycle,
                'start_date' => Carbon::now()->toDateString(),
                'end_date' => null,
                'class_mapping_summary' => $newCycle === 'Putaran 1' ? 'XII RPL 1: Teori | XII RPL 2: Praktik' : 'XII RPL 1: Praktik | XII RPL 2: Teori',
                'changed_by' => Auth::id(),
            ]);
        }

        return redirect()->back()->with('success', 'Siklus blok berhasil diubah ke ' . $request->active_cycle . ' dan dicatat ke riwayat.');
    }

    public function toggleLock(Request $request)
    {
        $config = SystemConfiguration::first();
        if (!$config) {
            $config = new SystemConfiguration([
                'active_cycle' => 'Putaran 1',
                'is_schedule_locked' => false,
            ]);
        }

        $config->is_schedule_locked = !$config->is_schedule_locked;
        $config->updated_by = Auth::id();
        $config->save();

        $statusText = $config->is_schedule_locked ? 'DIKUNCI (Lock Schedule Aktif)' : 'DIBUKA (Siap Diinput)';
        return redirect()->back()->with('success', 'Status pengisian jadwal berhasil diubah: ' . $statusText);
    }
}

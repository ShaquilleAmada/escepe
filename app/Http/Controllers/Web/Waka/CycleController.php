<?php

namespace App\Http\Controllers\Web\Waka;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SystemConfiguration;
use App\Models\CycleHistory;
use App\Models\Schedule;
use App\Models\Classes;
use Illuminate\Support\Facades\Auth;

class CycleController extends Controller
{
    /**
     * Menampilkan halaman Siklus Putaran & Riwayat Perputaran Siklus.
     */
    public function index(Request $request)
    {
        $config = SystemConfiguration::first();
        if (!$config) {
            $config = SystemConfiguration::create([
                'active_cycle' => 'Putaran 1',
                'is_schedule_locked' => false,
                'updated_by' => Auth::id() ?? 1,
            ]);
        }

        $activeCycle = $config->active_cycle ?? 'Putaran 1';

        // Query riwayat perputaran siklus
        $query = CycleHistory::with('user')->orderBy('id', 'desc');

        if ($request->filled('cycle_filter')) {
            $query->where('cycle_name', $request->cycle_filter);
        }

        $cycleHistories = $query->paginate(10)->withQueryString();

        // Statistik
        $totalSwitches = CycleHistory::count();
        $lastSwitch = CycleHistory::with('user')->orderBy('id', 'desc')->first();

        // Jumlah jadwal per siklus
        $schedulesPutaran1 = Schedule::where('cycle_type', 'Putaran 1')->count();
        $schedulesPutaran2 = Schedule::where('cycle_type', 'Putaran 2')->count();
        $schedulesAll = Schedule::where('cycle_type', 'semua')->count();

        // Matriks Pemetaan Rombel Sesuai Siklus Aktif (Dinamis seluruh rombel kelas)
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

            $classStatusMatrix[] = [
                'class_name' => $c->class_name,
                'status_type' => $statusType,
                'badge_color' => $hasLabOrKK ? 'bg-success' : 'bg-primary',
                'location' => $roomStr,
            ];
        }

        return view('waka.cycles.index', compact(
            'config',
            'activeCycle',
            'cycleHistories',
            'totalSwitches',
            'lastSwitch',
            'schedulesPutaran1',
            'schedulesPutaran2',
            'schedulesAll',
            'classStatusMatrix'
        ));
    }
}

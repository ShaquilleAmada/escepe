<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Classes;
use App\Models\Room;
use App\Models\SystemConfiguration;

class MasterDataController extends Controller
{
    public function getClasses()
    {
        $classes = Classes::orderBy('class_name')->get();
        return response()->json([
            'success' => true,
            'data' => $classes
        ], 200);
    }

    public function getRooms()
    {
        $rooms = Room::orderBy('room_name')->get();
        return response()->json([
            'success' => true,
            'data' => $rooms
        ], 200);
    }

    public function getTeachers()
    {
        $teachers = \App\Models\User::whereIn('role_id', [1, 2, 5, 6])
            ->select('id', 'fullname', 'username', 'role_id')
            ->orderBy('fullname')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $teachers
        ], 200);
    }

    public function getRoles()
    {
        $roles = \App\Models\Role::orderBy('id')->get();
        return response()->json([
            'success' => true,
            'data' => $roles
        ], 200);
    }

    public function getConfig()
    {
        $config = SystemConfiguration::first();
        return response()->json([
            'success' => true,
            'data' => [
                'active_cycle' => $config->active_cycle ?? 'Putaran 1',
                'is_schedule_locked' => (bool) ($config->is_schedule_locked ?? false),
            ]
        ], 200);
    }

    public function getSubjects(Request $request)
    {
        $subjects = \App\Models\Schedule::select('subject')
            ->distinct()
            ->orderBy('subject')
            ->pluck('subject');

        return response()->json([
            'success' => true,
            'total'   => $subjects->count(),
            'data'    => $subjects
        ], 200);
    }

    public function getCycles(Request $request)
    {
        $config = SystemConfiguration::first();
        $activeCycle = $config->active_cycle ?? 'Putaran 1';

        $histories = \App\Models\CycleHistory::with('user:id,fullname')
            ->orderBy('id', 'desc')
            ->paginate(10);

        return response()->json([
            'success'      => true,
            'active_cycle' => $activeCycle,
            'is_locked'    => (bool) ($config->is_schedule_locked ?? false),
            'statistics'   => [
                'putaran_1_schedules_count' => \App\Models\Schedule::where('cycle_type', 'Putaran 1')->count(),
                'putaran_2_schedules_count' => \App\Models\Schedule::where('cycle_type', 'Putaran 2')->count(),
                'semua_schedules_count'     => \App\Models\Schedule::where('cycle_type', 'semua')->count(),
                'total_switches'            => \App\Models\CycleHistory::count(),
            ],
            'histories'    => $histories
        ], 200);
    }

    public function toggleCycle(Request $request)
    {
        $request->validate([
            'cycle' => 'required|in:Putaran 1,Putaran 2'
        ]);

        $config = SystemConfiguration::first();
        if (!$config) {
            $config = new SystemConfiguration();
        }

        // Blokir jika jadwal sedang terkunci
        if ($config->is_schedule_locked) {
            return response()->json([
                'success' => false,
                'message' => 'Siklus tidak dapat diubah karena jadwal sedang terkunci. Buka kunci jadwal terlebih dahulu.'
            ], 422);
        }

        $oldCycle = $config->active_cycle;
        $newCycle = $request->cycle;

        if ($oldCycle !== $newCycle) {
            $config->active_cycle = $newCycle;
            $config->updated_by = $request->user()->id ?? 1;
            $config->save();

            // Catat Riwayat Perputaran Siklus
            \App\Models\CycleHistory::create([
                'cycle_name' => $newCycle,
                'start_date' => \Carbon\Carbon::now()->toDateString(),
                'user_id'    => $request->user()->id ?? 1,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Siklus aktif berhasil diubah ke ' . $config->active_cycle,
            'data'    => $config
        ], 200);
    }

    public function toggleLock(Request $request)
    {
        $config = SystemConfiguration::first();
        $config->is_schedule_locked = !$config->is_schedule_locked;
        $config->updated_by = $request->user()->id ?? 1;
        $config->save();

        $statusText = $config->is_schedule_locked ? 'Terkunci' : 'Terbuka';
        return response()->json([
            'success' => true,
            'message' => 'Status pengisian jadwal berhasil diubah menjadi ' . $statusText,
            'data' => $config
        ], 200);
    }
}

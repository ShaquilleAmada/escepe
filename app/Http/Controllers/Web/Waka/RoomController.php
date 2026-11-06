<?php

namespace App\Http\Controllers\Web\Waka;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Room;
use App\Services\RoomMatrixImportService;

class RoomController extends Controller
{
    public function index()
    {
        $rooms = Room::withCount('schedules')->orderBy('room_name')->paginate(10);
        return view('waka.rooms.index', compact('rooms'));
    }

    public function importMatrix(Request $request, RoomMatrixImportService $service)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,xlsx,xls,csv|max:20480',
            'cycle_type' => 'nullable|string',
        ]);

        try {
            $cycleType = $request->input('cycle_type') ?: 'Putaran 2';
            $result = $service->import($request->file('file'), $cycleType);

            return redirect()->route('waka.rooms.index')->with(
                'success',
                "Berhasil sinkronisasi matriks ruangan: {$result['unique_rooms']} ruangan terdata ({$result['new_rooms']} ruangan baru), {$result['updated_schedules']} sesi jadwal diperbarui ke ruang fisik sebenarnya."
            );
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Gagal memproses file matriks ruangan: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'room_name' => 'required|string|max:50|unique:rooms,room_name',
        ]);

        Room::create([
            'room_name' => $request->room_name,
        ]);

        return redirect()->route('waka.rooms.index')->with('success', 'Ruangan baru berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $room = Room::findOrFail($id);

        $request->validate([
            'room_name' => 'required|string|max:50|unique:rooms,room_name,' . $room->id,
        ]);

        $room->update([
            'room_name' => $request->room_name,
        ]);

        return redirect()->route('waka.rooms.index')->with('success', 'Nama ruangan berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $room = Room::findOrFail($id);

        $schedulesCount = $room->schedules()->count();
        if ($schedulesCount > 0) {
            return redirect()->route('waka.rooms.index')
                ->with('error', "Gagal menghapus ruangan '{$room->room_name}'. Ruangan ini sedang aktif digunakan oleh {$schedulesCount} sesi jadwal pelajaran.");
        }

        $room->delete();

        return redirect()->route('waka.rooms.index')->with('success', "Data ruangan '{$room->room_name}' berhasil dihapus.");
    }
}

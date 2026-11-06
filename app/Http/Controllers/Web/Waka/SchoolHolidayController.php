<?php

namespace App\Http\Controllers\Web\Waka;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SchoolHoliday;
use Illuminate\Support\Facades\Auth;

class SchoolHolidayController extends Controller
{
    public function index(Request $request)
    {
        $year = $request->input('year', date('Y'));

        $holidays = SchoolHoliday::whereYear('holiday_date', $year)
            ->with('creator')
            ->orderBy('holiday_date', 'asc')
            ->get();

        return view('waka.holidays.index', compact('holidays', 'year'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'holiday_date' => 'required|date|unique:school_holidays,holiday_date',
            'description'  => 'required|string|max:255',
        ], [
            'holiday_date.required' => 'Tanggal libur wajib diisi.',
            'holiday_date.unique'   => 'Tanggal libur ini sudah ada di dalam daftar.',
            'description.required'  => 'Keterangan hari libur wajib diisi.',
        ]);

        SchoolHoliday::create([
            'holiday_date' => $validated['holiday_date'],
            'description'  => $validated['description'],
            'created_by'   => Auth::id(),
        ]);

        return redirect()->route('waka.holidays.index')
            ->with('success', 'Hari libur sekolah berhasil ditambahkan.');
    }

    public function destroy($id)
    {
        $holiday = SchoolHoliday::findOrFail($id);
        $holiday->delete();

        return redirect()->route('waka.holidays.index')
            ->with('success', 'Hari libur berhasil dihapus.');
    }
}


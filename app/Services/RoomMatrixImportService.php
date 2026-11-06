<?php

namespace App\Services;

use App\Models\Classes;
use App\Models\Room;
use App\Models\Schedule;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Smalot\PdfParser\Parser as PdfParser;

class RoomMatrixImportService
{
    /**
     * Daftar 48 Rombel SMKN 1 Bantul sesuai urutan kolom pada dokumen resmi.
     */
    protected array $standardClassNames = [
        'X RPL 1', 'X RPL 2', 'X TKJ 1', 'X TKJ 2', 'X DKV 1', 'X DKV 2', 'X AKL 1', 'X AKL 2', 'X AKL 3', 'X LPS 1', 'X LPS 2', 'X MP 1', 'X MP 2', 'X BR 1', 'X BR 2', 'X BD 1',
        'XI RPL 1', 'XI RPL 2', 'XI TKJ 1', 'XI TKJ 2', 'XI DKV 1', 'XI DKV 2', 'XI AKL 1', 'XI AKL 2', 'XI AKL 3', 'XI LPS 1', 'XI LPS 2', 'XI MP 1', 'XI MP 2', 'XI BR 1', 'XI BR 2', 'XI BD 1',
        'XII RPL 1', 'XII RPL 2', 'XII TKJ 1', 'XII TKJ 2', 'XII DKV 1', 'XII DKV 2', 'XII AKL 1', 'XII AKL 2', 'XII AKL 3', 'XII AKL 4', 'XII LPS 1', 'XII MP 1', 'XII MP 2', 'XII BR 1', 'XII BR 2', 'XII BD 1'
    ];

    /**
     * Memproses file upload matriks ruangan (PDF atau Excel).
     */
    public function import(UploadedFile $file, string $cycleType = 'Putaran 2'): array
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');

        $ext = strtolower($file->getClientOriginalExtension());
        $path = $file->getRealPath();

        return match ($ext) {
            'pdf' => $this->importPdf($path, $cycleType),
            'xlsx', 'xls', 'csv' => $this->importSpreadsheet($path, $cycleType),
            default => throw new \InvalidArgumentException("Format file .{$ext} tidak didukung. Harap unggah file PDF (.pdf) atau Excel (.xlsx, .xls, .csv)."),
        };
    }

    /**
     * Mengurai file PDF matriks ruangan (3 halaman: Senin-Selasa, Rabu-Kamis, Jumat).
     */
    protected function importPdf(string $filePath, string $cycleType): array
    {
        $parser = new PdfParser();
        $pdf = $parser->parseFile($filePath);
        $pages = $pdf->getPages();

        if (empty($pages)) {
            return [
                'success' => false,
                'message' => 'File PDF tidak terbaca atau kosong.',
                'rooms_count' => 0,
                'updated_schedules' => 0,
            ];
        }

        $pageDays = [
            0 => ['Senin', 'Selasa'],
            1 => ['Rabu', 'Kamis'],
            2 => ['Jumat']
        ];

        // Siapkan mapping model kelas berdasarkan 48 kolom
        $classModels = $this->resolveClassModels();
        $allRoomsCache = Room::all()->keyBy(fn($r) => strtolower($r->room_name));

        $totalUpdatedSchedules = 0;
        $totalSlotsProcessed = 0;
        $uniqueRoomsFound = [];
        $newRoomsCreated = 0;

        DB::beginTransaction();
        try {
            foreach ($pages as $pIdx => $page) {
                $days = $pageDays[$pIdx] ?? [];
                if (empty($days)) continue;

                $arr = $page->getTextArray();
                $rows = $this->extractPeriodRowsFromPdfArray($arr);

                if (count($days) === 2) {
                    $day1Rows = array_slice($rows, 0, 10);
                    $day2Rows = array_slice($rows, 10);
                    $dayRowMap = [
                        $days[0] => $day1Rows,
                        $days[1] => $day2Rows,
                    ];
                } else {
                    $dayRowMap = [
                        $days[0] => $rows
                    ];
                }

                foreach ($dayRowMap as $dayName => $periodRows) {
                    foreach ($periodRows as $pr) {
                        $jam = $pr['jam'];
                        foreach ($pr['rooms'] as $colIdx => $rawRoom) {
                            $roomName = $this->normalizeRoomName($rawRoom);
                            if (!$roomName) continue;

                            $classObj = $classModels[$colIdx] ?? null;
                            if (!$classObj) continue;

                            $uniqueRoomsFound[$roomName] = true;

                            // Pastikan ruangan ada di database
                            $roomLower = strtolower($roomName);
                            if (!isset($allRoomsCache[$roomLower])) {
                                $newRoom = Room::create(['room_name' => $roomName]);
                                $allRoomsCache[$roomLower] = $newRoom;
                                $newRoomsCreated++;
                            }
                            $roomObj = $allRoomsCache[$roomLower];

                            // Update jadwal yang sesuai di database
                            $schedules = Schedule::where('class_id', $classObj->id)
                                ->where('day_of_week', $dayName)
                                ->where('start_period', '<=', $jam)
                                ->where('end_period', '>=', $jam)
                                ->when($cycleType, fn($q) => $q->where('cycle_type', $cycleType))
                                ->get();

                            foreach ($schedules as $sch) {
                                if ($sch->room_id !== $roomObj->id) {
                                    $sch->room_id = $roomObj->id;
                                    $sch->save();
                                    $totalUpdatedSchedules++;
                                }
                            }

                            $totalSlotsProcessed++;
                        }
                    }
                }
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Sinkronisasi matriks ruangan berhasil.',
                'unique_rooms' => count($uniqueRoomsFound),
                'new_rooms' => $newRoomsCreated,
                'updated_schedules' => $totalUpdatedSchedules,
                'slots_processed' => $totalSlotsProcessed,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Mengurai file Excel jika diunggah dalam format spreadsheet.
     */
    protected function importSpreadsheet(string $filePath, string $cycleType): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $classModels = $this->resolveClassModels();
        $allRoomsCache = Room::all()->keyBy(fn($r) => strtolower($r->room_name));

        $totalUpdatedSchedules = 0;
        $totalSlotsProcessed = 0;
        $uniqueRoomsFound = [];
        $newRoomsCreated = 0;

        DB::beginTransaction();
        try {
            foreach ($spreadsheet->getAllSheets() as $sheet) {
                $rows = $sheet->toArray(null, true, true, false);
                $currentDay = null;

                foreach ($rows as $row) {
                    $firstCell = trim((string)($row[0] ?? ''));
                    if (in_array(strtolower($firstCell), ['senin', 'selasa', 'rabu', 'kamis', 'jumat'])) {
                        $currentDay = ucfirst(strtolower($firstCell));
                    }

                    $jamCell = trim((string)($row[1] ?? ''));
                    if (!is_numeric($jamCell) || !$currentDay) {
                        continue;
                    }

                    $jam = (int)$jamCell;
                    // Kolom ruangan dimulai dari indeks 3 (setelah Hari, Jam, Waktu)
                    for ($c = 0; $c < 48; $c++) {
                        $rawRoom = trim((string)($row[$c + 3] ?? ''));
                        $roomName = $this->normalizeRoomName($rawRoom);
                        if (!$roomName) continue;

                        $classObj = $classModels[$c] ?? null;
                        if (!$classObj) continue;

                        $uniqueRoomsFound[$roomName] = true;

                        $roomLower = strtolower($roomName);
                        if (!isset($allRoomsCache[$roomLower])) {
                            $newRoom = Room::create(['room_name' => $roomName]);
                            $allRoomsCache[$roomLower] = $newRoom;
                            $newRoomsCreated++;
                        }
                        $roomObj = $allRoomsCache[$roomLower];

                        $schedules = Schedule::where('class_id', $classObj->id)
                            ->where('day_of_week', $currentDay)
                            ->where('start_period', '<=', $jam)
                            ->where('end_period', '>=', $jam)
                            ->when($cycleType, fn($q) => $q->where('cycle_type', $cycleType))
                            ->get();

                        foreach ($schedules as $sch) {
                            if ($sch->room_id !== $roomObj->id) {
                                $sch->room_id = $roomObj->id;
                                $sch->save();
                                $totalUpdatedSchedules++;
                            }
                        }

                        $totalSlotsProcessed++;
                    }
                }
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Sinkronisasi matriks ruangan dari spreadsheet berhasil.',
                'unique_rooms' => count($uniqueRoomsFound),
                'new_rooms' => $newRoomsCreated,
                'updated_schedules' => $totalUpdatedSchedules,
                'slots_processed' => $totalSlotsProcessed,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Ekstraksi baris jam dan 48 kolom ruangan dari array teks halaman PDF.
     */
    protected function extractPeriodRowsFromPdfArray(array $arr): array
    {
        $rows = [];
        $count = count($arr);

        for ($i = 0; $i < $count; $i++) {
            $val = trim($arr[$i]);
            // Format waktu: 08.00 - 08.35 atau 08.10 - 0850
            if (preg_match('/\b\d{2}[\.:]?\d{2}\s*-\s*\d{2}[\.:]?\d{2}\b/', $val)) {
                $jam = isset($arr[$i - 1]) ? trim($arr[$i - 1]) : '';
                if (is_numeric($jam)) {
                    $cellRooms = [];
                    for ($c = 1; $c <= 48; $c++) {
                        $cellRooms[] = $arr[$i + $c] ?? '';
                    }
                    $rows[] = [
                        'jam' => (int)$jam,
                        'waktu' => $val,
                        'rooms' => $cellRooms
                    ];
                }
            }
        }

        return $rows;
    }

    /**
     * Menyiapkan daftar objek Classes sesuai indeks 48 kolom dokumen.
     */
    protected function resolveClassModels(): array
    {
        $models = [];
        foreach ($this->standardClassNames as $cIdx => $cName) {
            $c = Classes::where('class_name', $cName)->first();
            if (!$c && str_ends_with($cName, ' 1')) {
                // Fallback kelas tanpa angka 1 (misal: XI BD)
                $alt = substr($cName, 0, -2);
                $c = Classes::where('class_name', $alt)->first();
            }
            $models[$cIdx] = $c;
        }
        return $models;
    }

    /**
     * Standardisasi nama ruangan dari singkatan dokumen ke nama resmi:
     * - L.16 -> Lab 16
     * - 22 -> Ruang 22
     * - OR1, OR2, OR3 -> OR1, OR2, OR3 (Lapangan Olahraga)
     * - HALL -> Hall
     * - L.IPA -> Lab IPA
     * - SINIAR -> Ruang Siniar
     */
    public function normalizeRoomName(?string $raw): ?string
    {
        if ($raw === null) return null;
        $trim = trim($raw);
        if (empty($trim) || in_array(strtoupper($trim), ['-', 'IST', 'ISTIRAHAT', 'LITERASI', 'UPACARA'])) {
            return null;
        }

        // L.16 -> Lab 16
        if (preg_match('/^L\.(\d+)$/i', $trim, $m)) {
            return 'Lab ' . $m[1];
        }

        // L.IPA -> Lab IPA
        if (preg_match('/^L\.(.+)$/i', $trim, $m)) {
            return 'Lab ' . strtoupper($m[1]);
        }

        // Angka murni: 12 -> Ruang 12
        if (is_numeric($trim)) {
            return 'Ruang ' . $trim;
        }

        // Hall
        if (strcasecmp($trim, 'hall') === 0) {
            return 'Hall';
        }

        // Siniar
        if (strcasecmp($trim, 'siniar') === 0) {
            return 'Ruang Siniar';
        }

        // OR1, OR2, OR3
        if (preg_match('/^OR\d$/i', $trim)) {
            return strtoupper($trim);
        }

        return $trim;
    }
}


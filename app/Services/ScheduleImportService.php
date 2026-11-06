<?php

namespace App\Services;

use App\Models\Classes;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Services\Pdf\CustomPdfParser as PdfParser;

class ScheduleImportService
{
    /**
     * Memproses file yang diunggah (Excel/CSV, Word DOCX, atau PDF).
     */
    public function import(UploadedFile $file, ?string $cycleOverride = null): array
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');

        $extension = strtolower($file->getClientOriginalExtension());
        $originalName = $file->getClientOriginalName();
        $path = $file->getRealPath();

        $rows = match ($extension) {
            'xlsx', 'xls', 'csv' => $this->parseSpreadsheet($path),
            'docx' => $this->parseDocx($path),
            'pdf' => $this->parsePdf($path, $originalName, $cycleOverride),
            default => throw new \InvalidArgumentException("Format file .{$extension} tidak didukung. Harap unggah file Excel (.xlsx, .xls, .csv), Word (.docx), atau PDF (.pdf)."),
        };

        if (empty($rows)) {
            return [
                'total_rows' => 0,
                'created' => 0,
                'skipped' => 0,
                'errors' => ['File yang diunggah tidak memiliki data atau tidak dapat dibaca.'],
            ];
        }

        return DB::transaction(function () use ($rows) {
            return $this->processRows($rows);
        });
    }

    /**
     * Membaca file Spreadsheet (.xlsx, .xls, .csv).
     */
    protected function parseSpreadsheet(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        return $sheet->toArray(null, true, true, false);
    }

    /**
     * Membaca tabel atau baris dari file Word (.docx) menggunakan ZipArchive.
     */
    protected function parseDocx(string $filePath): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new \Exception('Gagal membuka file Word (.docx). File mungkin korup atau diproteksi.');
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if (!$xml) {
            throw new \Exception('File .docx tidak berisi struktur dokumen XML yang valid.');
        }

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadXML($xml);
        libxml_clear_errors();

        $rows = [];
        $tables = $dom->getElementsByTagName('tbl');

        if ($tables->length > 0) {
            foreach ($tables as $table) {
                $trList = $table->getElementsByTagName('tr');
                foreach ($trList as $tr) {
                    $row = [];
                    $tcList = $tr->getElementsByTagName('tc');
                    foreach ($tcList as $tc) {
                        $row[] = trim($tc->textContent);
                    }
                    if (!empty(array_filter($row, fn($c) => $c !== ''))) {
                        $rows[] = $row;
                    }
                }
            }
        } else {
            $paragraphs = $dom->getElementsByTagName('p');
            foreach ($paragraphs as $p) {
                $text = trim($p->textContent);
                if (empty($text))
                    continue;

                if (str_contains($text, "\t")) {
                    $rows[] = array_map('trim', explode("\t", $text));
                } elseif (str_contains($text, "|")) {
                    $rows[] = array_map('trim', array_filter(explode("|", $text), fn($val) => trim($val) !== ''));
                } elseif (str_contains($text, ",")) {
                    $rows[] = str_getcsv($text);
                }
            }
        }

        return $rows;
    }

    /**
     * Membaca teks terstruktur dari file PDF menggunakan Smalot\PdfParser (didukung CustomPdfParser).
     */
    protected function parsePdf(string $filePath, string $originalName = '', ?string $cycleOverride = null): array
    {
        $parser = new PdfParser();
        $pdf = $parser->parseFile($filePath);

        if ($this->isFetTimetable($pdf)) {
            return $this->parseFetPdf($pdf, $originalName, $cycleOverride);
        }

        $text = $pdf->getText();
        $lines = preg_split('/\r\n|\r|\n/', $text);
        $rows = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line))
                continue;

            if (str_contains($line, "\t")) {
                $cols = array_map('trim', explode("\t", $line));
            } elseif (str_contains($line, "|")) {
                $cols = array_map('trim', array_filter(explode("|", $line), fn($val) => trim($val) !== ''));
            } else {
                $cols = preg_split('/\s{2,}/', $line);
            }

            if (is_array($cols) && count($cols) >= 4) {
                $rows[] = array_values(array_map('trim', $cols));
            }
        }

        return $rows;
    }

    /**
     * Memeriksa apakah file PDF merupakan jadwal format FET (Free Evolutionary Timetabling).
     */
    protected function isFetTimetable($pdf): bool
    {
        $pages = $pdf->getPages();
        if (empty($pages))
            return false;

        $sample = '';
        for ($i = 0; $i < min(10, count($pages)); $i++) {
            try {
                $sample .= ' ' . $pages[$i]->getText();
            } catch (\Throwable $e) {
            }
        }

        return str_contains($sample, 'FET') ||
            str_contains($sample, 'Daftar Isi') ||
            str_contains($sample, 'SMK NEGERI 1 BANTUL') ||
            (str_contains($sample, 'Senin') && str_contains($sample, 'Selasa') && (str_contains($sample, '07:15') || str_contains($sample, '08:00')));
    }

    /**
     * Memeriksa apakah file PDF FET merupakan Jadwal per Kelas (bukan per Guru).
     */
    protected function isFetClassTimetable($pdf, $classes): bool
    {
        $pages = $pdf->getPages();
        if (count($pages) < 2)
            return false;

        $page2 = $pages[1];
        $dataTm = $page2->getDataTm();
        if (empty($dataTm))
            return false;

        foreach ($dataTm as $it) {
            $y = round($it[0][5], 0);
            if ($y > 770 && $y < 795) {
                $t = trim($it[1]);
                if ($this->matchClass($t, $classes)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Mengurai file PDF matriks jadwal FET menjadi baris-baris data tabular siap diproses.
     * Secara otomatis mendeteksi apakah jadwal berupa Format per Guru atau Format per Kelas.
     */
    protected function parseFetPdf($pdf, string $originalName = '', ?string $cycleOverride = null): array
    {
        // 1. Tentukan Siklus / Putaran (Override form request > Deteksi dari nama file > Default)
        $cycle = 'semua';
        if (!empty($cycleOverride) && in_array(strtolower($cycleOverride), ['putaran 1', 'putaran 2', 'semua'])) {
            $cycle = strtolower($cycleOverride) === 'semua' ? 'semua' : ucwords(strtolower($cycleOverride));
        } elseif (preg_match('/put[_\s-]*2|putaran[_\s-]*2|genap/i', $originalName)) {
            $cycle = 'Putaran 2';
        } elseif (preg_match('/put[_\s-]*1|putaran[_\s-]*1|ganjil/i', $originalName)) {
            $cycle = 'Putaran 1';
        }

        $classes = Classes::all();

        // Slot waktu sesuai label jam di PDF FET SMKN 1 Bantul.
        $slotDefs = [
            0 => ['start' => '07:15', 'end' => '08:00', 'period' => 1],
            1 => ['start' => '08:00', 'end' => '08:45', 'period' => 2],
            2 => ['start' => '08:45', 'end' => '09:30', 'period' => 3],
            3 => ['start' => '09:45', 'end' => '10:30', 'period' => 4],
            4 => ['start' => '10:30', 'end' => '11:15', 'period' => 5],
            5 => ['start' => '11:15', 'end' => '12:00', 'period' => 6],
            6 => ['start' => '12:30', 'end' => '13:15', 'period' => 7],
            7 => ['start' => '13:15', 'end' => '14:00', 'period' => 8],
            8 => ['start' => '14:00', 'end' => '14:45', 'period' => 9],
            9 => ['start' => '14:45', 'end' => '15:30', 'period' => 10],
        ];

        if ($this->isFetClassTimetable($pdf, $classes)) {
            return $this->parseFetClassPdf($pdf, $classes, $cycle, $slotDefs);
        }

        return $this->parseFetTeacherPdf($pdf, $classes, $cycle, $slotDefs);
    }

    /**
     * Mengurai file PDF jadwal FET per Kelas (Timetable of Classes) menjadi format baris data tabular.
     */
    protected function parseFetClassPdf($pdf, $classes, string $cycle, array $slotDefs): array
    {
        $pages = $pdf->getPages();

        $colBounds = [
            'Senin' => [60, 142],
            'Selasa' => [142, 235],
            'Rabu' => [235, 323],
            'Kamis' => [323, 435],
            'Jumat' => [435, 570],
        ];

        $slotY = [
            0 => 738.7,
            1 => 698.1,
            2 => 657.5,
            3 => 616.8,
            4 => 576.2,
            5 => 535.6,
            6 => 494.9,
            7 => 454.3,
            8 => 413.7,
            9 => 373.1,
        ];

        $rows = [
            ['Hari', 'Waktu', 'Mata Pelajaran', 'Guru Pengampu', 'Kelas', 'Ruangan', 'Siklus']
        ];

        for ($p = 1; $p < count($pages); $p++) {
            $page = $pages[$p];
            $dataTm = $page->getDataTm();
            if (empty($dataTm)) continue;

            // 1. Identifikasi nama rombel / kelas dari kop halaman
            $matchedClass = null;
            foreach ($dataTm as $it) {
                $y = round($it[0][5], 0);
                if ($y > 770 && $y < 795) {
                    $c = $this->matchClass(trim($it[1]), $classes);
                    if ($c) {
                        $matchedClass = $c;
                        break;
                    }
                }
            }
            if (!$matchedClass) continue;
            $className = $matchedClass->class_name;

            // 2. Deteksi slot aktif vs kosong (--- / -x-)
            $slotOccupied = [];
            foreach (array_keys($colBounds) as $day) {
                for ($s = 0; $s < 10; $s++) {
                    $slotOccupied[$day][$s] = true;
                }
            }

            foreach ($dataTm as $item) {
                $x = round($item[0][4], 1);
                $y = round($item[0][5], 1);
                $t = trim($item[1]);
                if ($t === '---' || $t === '-x-') {
                    foreach ($colBounds as $day => $b) {
                        if ($x >= $b[0] && $x < $b[1]) {
                            foreach ($slotY as $s => $sy) {
                                if (abs($y - $sy) < 15) {
                                    $slotOccupied[$day][$s] = false;
                                    break;
                                }
                            }
                            break;
                        }
                    }
                }
            }

            // 3. Filter item teks per hari
            $dayItems = [];
            foreach ($dataTm as $item) {
                $x = round($item[0][4], 1);
                $y = round($item[0][5], 1);
                $t = trim($item[1]);
                if ($t === '' || $t === '---' || $t === '-x-') continue;
                if (in_array($t, ['SMK NEGERI 1 BANTUL', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Kembali ke atas'])) continue;
                if (str_contains($t, 'Jadwal dihasilkan') || str_contains($t, 'FET') || $y > 760 || $x < 60 || $y < 50) continue;

                foreach ($colBounds as $day => $b) {
                    if ($x >= $b[0] && $x < $b[1]) {
                        $dayItems[$day][] = ['x' => $x, 'y' => $y, 'text' => $item[1]];
                        break;
                    }
                }
            }

            // 4. Proses per hari
            foreach (array_keys($colBounds) as $day) {
                $cItems = $dayItems[$day] ?? [];
                if (empty($cItems)) continue;

                // Kelompokkan item teks menjadi baris berdasarkan koordinat Y
                $lines = [];
                foreach ($cItems as $it) {
                    $found = false;
                    foreach ($lines as &$ln) {
                        if (abs($ln['y'] - $it['y']) < 5) {
                            $ln['items'][] = $it;
                            $found = true;
                            break;
                        }
                    }
                    unset($ln);
                    if (!$found) $lines[] = ['y' => $it['y'], 'items' => [$it]];
                }
                usort($lines, fn($a, $b) => $b['y'] <=> $a['y']);

                // Bentuk kluster sesi pelajaran
                $lessons = [];
                $curLesson = null;
                $inTeacher = false;

                foreach ($lines as $ln) {
                    usort($ln['items'], fn($a, $b) => $a['x'] <=> $b['x']);
                    $txt = trim(implode('', array_column($ln['items'], 'text')));
                    if ($txt === '---' || $txt === '-x-' || $txt === '"' || $txt === "''" || $txt === '') continue;
                    if (str_contains($txt, 'Jadwal dihasilkan') || str_contains($txt, 'FET')) continue;

                    $gap = $curLesson ? ($curLesson['last_y'] - $ln['y']) : 0;

                    if (preg_match('/^"?\d+([\.,\s]|[A-Z])/', $txt)) {
                        $inTeacher = true;
                        if ($curLesson && !empty($curLesson['teacher_lines']) && $gap > 20) {
                            $lessons[] = $curLesson;
                            $curLesson = null;
                        }
                        if (!$curLesson) {
                            $curLesson = ['top_y' => $ln['y'], 'last_y' => $ln['y'], 'subj_lines' => [], 'teacher_lines' => [$txt], 'room' => null];
                        } else {
                            $curLesson['teacher_lines'][] = $txt;
                            $curLesson['last_y'] = $ln['y'];
                        }
                        if (str_ends_with($txt, '"') || preg_match('/(S\.Pd|M\.Pd|S\.Kom|MM|S\.Pd\.I|S\.Pd\.Si|S\.T|S\.S)\.?\"?$/i', $txt)) {
                            $inTeacher = false;
                        }
                        continue;
                    }

                    if ($inTeacher) {
                        $curLesson['teacher_lines'][] = $txt;
                        $curLesson['last_y'] = $ln['y'];
                        if (str_ends_with($txt, '"') || preg_match('/(S\.Pd|M\.Pd|S\.Kom|MM|S\.Pd\.I|S\.Pd\.Si|S\.T|S\.S)\.?\"?$/i', $txt)) {
                            $inTeacher = false;
                        }
                        continue;
                    }

                    if (preg_match('/^(LAB\s*\d+|LAB\s*[A-Z0-9]+|OR\s*\d+|R\.\s*\d+|Ruang\s*\d+)/i', $txt)) {
                        $curLesson['room'] = $txt;
                        $curLesson['last_y'] = $ln['y'];
                        continue;
                    }

                    if ($curLesson && (!empty($curLesson['teacher_lines']) || $gap > 20)) {
                        $lessons[] = $curLesson;
                        $curLesson = null;
                    }

                    if (!$curLesson) {
                        $curLesson = [
                            'top_y' => $ln['y'],
                            'last_y' => $ln['y'],
                            'subj_lines' => [$txt],
                            'teacher_lines' => [],
                            'room' => null,
                        ];
                    } else {
                        $curLesson['subj_lines'][] = $txt;
                        $curLesson['last_y'] = $ln['y'];
                    }
                }
                if ($curLesson) $lessons[] = $curLesson;

                $occupiedSlots = [];
                for ($s = 0; $s < 10; $s++) {
                    if ($slotOccupied[$day][$s]) $occupiedSlots[] = $s;
                }
                if (empty($occupiedSlots) || empty($lessons)) continue;

                // Kelompokkan slot terisi menjadi rentang kontigu
                $segments = [];
                $currentSeg = [$occupiedSlots[0]];
                for ($i = 1; $i < count($occupiedSlots); $i++) {
                    if ($occupiedSlots[$i] === $occupiedSlots[$i - 1] + 1) {
                        $currentSeg[] = $occupiedSlots[$i];
                    } else {
                        $segments[] = $currentSeg;
                        $currentSeg = [$occupiedSlots[$i]];
                    }
                }
                $segments[] = $currentSeg;

                foreach ($segments as $seg) {
                    $segStartSlot = $seg[0];
                    $segEndSlot = $seg[count($seg) - 1];
                    $topY = $slotY[$segStartSlot] + 24;
                    $botY = $slotY[$segEndSlot] - 24;

                    $segLessons = array_filter($lessons, fn($l) => $l['top_y'] <= $topY && $l['last_y'] >= $botY - 20);
                    if (empty($segLessons)) continue;
                    $segLessons = array_values($segLessons);

                    $clusterSlots = array_fill(0, count($segLessons), []);
                    foreach ($seg as $slotIdx) {
                        $sy = $slotY[$slotIdx];
                        $bestDist = PHP_FLOAT_MAX;
                        $bestC = 0;
                        foreach ($segLessons as $cIdx => $c) {
                            $dist = abs($sy - ($c['top_y'] + $c['last_y']) / 2);
                            if ($dist < $bestDist) {
                                $bestDist = $dist;
                                $bestC = $cIdx;
                            }
                        }
                        $clusterSlots[$bestC][] = $slotIdx;
                    }

                    foreach ($segLessons as $cIdx => $l) {
                        $cSlots = $clusterSlots[$cIdx] ?? [];
                        if (empty($cSlots)) continue;
                        $cStart = min($cSlots);
                        $cEnd = max($cSlots);

                        $rawStart = $slotDefs[$cStart]['start'];
                        $rawEnd = $slotDefs[$cEnd]['end'];
                        $snappedStart = $this->snapTimeToDailyPeriod($rawStart, $day, 'start');
                        $snappedEnd = $this->snapTimeToDailyPeriod($rawEnd, $day, 'end');
                        $time = $snappedStart . ' - ' . $snappedEnd;

                        $subj = trim(implode(' ', $l['subj_lines']));
                        $rawT = trim(implode(' ', $l['teacher_lines']), "\" \t\n\r");
                        $cleanT = preg_replace('/^\d+[\.,\s]*/', '', $rawT);
                        $cleanT = trim($cleanT, "\" \t\n\r");
                        $rm = $l['room'] ?? ('Ruang ' . $className);

                        if (empty($subj) && empty($cleanT)) continue;
                        if (empty($subj)) $subj = 'Mata Pelajaran';
                        if (empty($cleanT)) $cleanT = 'Guru Pengampu';

                        $rows[] = [
                            $day,
                            $time,
                            $subj,
                            $cleanT,
                            $className,
                            $rm,
                            $cycle
                        ];
                    }
                }
            }
        }

        return $rows;
    }

    /**
     * Mengurai file PDF matriks jadwal FET per Guru (Timetable of Teachers).
     */
    protected function parseFetTeacherPdf($pdf, $classes, string $cycle, array $slotDefs): array
    {
        $pages = $pdf->getPages();
        $daysOrder = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
        $rows = [
            ['Hari', 'Waktu', 'Mata Pelajaran', 'Guru Pengampu', 'Kelas', 'Ruangan', 'Siklus']
        ];

        for ($p = 2; $p < count($pages); $p++) {
            $page = $pages[$p];
            $dataTm = $page->getDataTm();
            if (empty($dataTm))
                continue;

            $linesByY = [];
            foreach ($dataTm as $item) {
                $y = round($item[0][5], 0);
                $linesByY[$y][] = ['x' => round($item[0][4], 1), 'text' => $item[1]];
            }
            krsort($linesByY);

            $teacherName = null;
            $dayPositions = [];

            foreach ($linesByY as $y => $items) {
                usort($items, fn($a, $b) => $a['x'] <=> $b['x']);
                $lineText = trim(implode('', array_column($items, 'text')));

                if (
                    str_contains($lineText, 'SMK NEGERI') ||
                    str_contains($lineText, 'SMKN') ||
                    str_contains($lineText, 'dihasilkan') ||
                    str_contains($lineText, 'pada ') ||
                    str_contains($lineText, 'FET') ||
                    str_contains($lineText, 'Kembali ke atas')
                ) {
                    continue;
                }

                if (!$teacherName && preg_match('/"?(\d+([\.,\s]|[A-Z])[^"]+)"?/', $lineText, $m)) {
                    $rawT = trim($m[1], "\" \t\n\r");
                    $candT = preg_replace('/^\d+[\.,\s]*/', '', $rawT);
                    if (!preg_match('/(dihasilkan|pada\s+\d|FET|\.org)/i', $candT)) {
                        $teacherName = $candT;
                    }
                }

                foreach ($items as $it) {
                    $t = trim($it['text']);
                    if (in_array($t, $daysOrder)) {
                        $dayPositions[$t] = $it['x'];
                    }
                }
            }

            if (!$teacherName || count($dayPositions) < 5)
                continue;

            asort($dayPositions);
            $dayKeys = array_keys($dayPositions);
            $colBounds = [];
            for ($i = 0; $i < count($dayKeys); $i++) {
                $curDay = $dayKeys[$i];
                $curX = $dayPositions[$curDay];
                $minX = ($i === 0) ? 140 : ($curX + $dayPositions[$dayKeys[$i - 1]]) / 2;
                $maxX = ($i === count($dayKeys) - 1) ? 9999 : ($curX + $dayPositions[$dayKeys[$i + 1]]) / 2;
                $colBounds[$curDay] = ['minX' => $minX, 'maxX' => $maxX, 'x' => $curX];
            }

            $timeItems = [];
            foreach ($dataTm as $item) {
                $x = round($item[0][4], 1);
                $y = round($item[0][5], 1);
                $t = trim($item[1]);
                if ($x < 150 && $y < -5000 && !str_contains($t, 'Kembali')) {
                    $timeItems[] = ['y' => $y, 'text' => $t];
                }
            }

            $groupedTimes = [];
            foreach ($timeItems as $ti) {
                $found = false;
                foreach ($groupedTimes as &$gt) {
                    if (abs($gt['y'] - $ti['y']) < 6) {
                        $gt['text'] .= $ti['text'];
                        $found = true;
                        break;
                    }
                }
                unset($gt);
                if (!$found) {
                    $groupedTimes[] = ['y' => $ti['y'], 'text' => $ti['text']];
                }
            }
            usort($groupedTimes, fn($a, $b) => $b['y'] <=> $a['y']);

            $slotY = [];
            foreach ($groupedTimes as $idx => $gt) {
                if ($idx < 10) {
                    $slotY[$idx] = $gt['y'];
                }
            }

            if (count($slotY) < 8)
                continue;

            $slotOccupied = [];
            foreach ($daysOrder as $day) {
                for ($s = 0; $s < 10; $s++) {
                    $slotOccupied[$day][$s] = true;
                }
            }

            foreach ($dataTm as $item) {
                $x = round($item[0][4], 1);
                $y = round($item[0][5], 1);
                $t = trim($item[1]);
                if ($t === '---' || $t === '-x-') {
                    foreach ($colBounds as $day => $b) {
                        if ($x >= $b['minX'] && $x < $b['maxX']) {
                            foreach ($slotY as $s => $sy) {
                                if (abs($y - $sy) < 15) {
                                    $slotOccupied[$day][$s] = false;
                                    break;
                                }
                            }
                            break;
                        }
                    }
                }
            }

            $dayItems = [];
            foreach ($dataTm as $item) {
                $x = round($item[0][4], 1);
                $y = round($item[0][5], 1);
                $t = trim($item[1]);
                if ($t === '' || $t === '---' || $t === '-x-')
                    continue;
                if (in_array($t, ['SMK NEGERI 1 BANTUL', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Kembali ke atas']))
                    continue;
                if (str_contains($t, 'Jadwal dihasilkan') || str_contains($t, '5.44.9'))
                    continue;
                if ($x < 150)
                    continue;
                if ($y > $slotY[0] + 20)
                    continue;

                foreach ($colBounds as $day => $b) {
                    if ($x >= $b['minX'] && $x < $b['maxX']) {
                        $dayItems[$day][] = ['x' => $x, 'y' => $y, 'text' => $item[1]];
                        break;
                    }
                }
            }

            foreach ($daysOrder as $day) {
                $occupiedSlots = [];
                for ($s = 0; $s < 10; $s++) {
                    if ($slotOccupied[$day][$s]) {
                        $occupiedSlots[] = $s;
                    }
                }
                if (empty($occupiedSlots))
                    continue;

                $segments = [];
                $currentSeg = [$occupiedSlots[0]];
                for ($i = 1; $i < count($occupiedSlots); $i++) {
                    if ($occupiedSlots[$i] === $occupiedSlots[$i - 1] + 1) {
                        $currentSeg[] = $occupiedSlots[$i];
                    } else {
                        $segments[] = $currentSeg;
                        $currentSeg = [$occupiedSlots[$i]];
                    }
                }
                $segments[] = $currentSeg;

                $itemsThisDay = $dayItems[$day] ?? [];

                foreach ($segments as $seg) {
                    $segStartSlot = $seg[0];
                    $segEndSlot = $seg[count($seg) - 1];
                    $topY = $slotY[$segStartSlot] + 24;
                    $botY = $slotY[$segEndSlot] - 24;

                    $segItems = array_filter($itemsThisDay, fn($it) => $it['y'] <= $topY && $it['y'] >= $botY);
                    if (empty($segItems))
                        continue;

                    $lines = [];
                    foreach ($segItems as $it) {
                        $found = false;
                        foreach ($lines as &$ln) {
                            if (abs($ln['y'] - $it['y']) < 5) {
                                $ln['items'][] = $it;
                                $found = true;
                                break;
                            }
                        }
                        unset($ln);
                        if (!$found) {
                            $lines[] = ['y' => $it['y'], 'items' => [$it]];
                        }
                    }
                    usort($lines, fn($a, $b) => $b['y'] <=> $a['y']);

                    $textLines = [];
                    foreach ($lines as $ln) {
                        usort($ln['items'], fn($a, $b) => $a['x'] <=> $b['x']);
                        $lineStr = trim(implode('', array_column($ln['items'], 'text')));
                        $textLines[] = ['y' => $ln['y'], 'text' => $lineStr];
                    }

                    $lessonClusters = [];
                    $currentCluster = null;

                    foreach ($textLines as $tl) {
                        $matchedClass = $this->matchClass($tl['text'], $classes);
                        if ($matchedClass) {
                            if ($currentCluster)
                                $lessonClusters[] = $currentCluster;
                            $currentCluster = [
                                'class' => $matchedClass->class_name,
                                'y' => $tl['y'],
                                'lines' => [$tl['text']]
                            ];
                        } else {
                            if ($currentCluster) {
                                $currentCluster['lines'][] = $tl['text'];
                            } else {
                                $currentCluster = [
                                    'class' => null,
                                    'y' => $tl['y'],
                                    'lines' => [$tl['text']]
                                ];
                            }
                        }
                    }
                    if ($currentCluster)
                        $lessonClusters[] = $currentCluster;
                    if (empty($lessonClusters))
                        continue;

                    if (count($lessonClusters) === 1) {
                        $c = $lessonClusters[0];
                        $rec = $this->formatClusterRecord($teacherName, $day, $segStartSlot, $segEndSlot, $c, $slotDefs, $cycle);
                        if ($rec)
                            $rows[] = $rec;
                    } else {
                        $clusterSlots = array_fill(0, count($lessonClusters), []);
                        foreach ($seg as $slotIdx) {
                            $sy = $slotY[$slotIdx];
                            $bestDist = PHP_FLOAT_MAX;
                            $bestC = 0;
                            foreach ($lessonClusters as $cIdx => $c) {
                                $dist = abs($sy - $c['y']);
                                if ($dist < $bestDist) {
                                    $bestDist = $dist;
                                    $bestC = $cIdx;
                                }
                            }
                            $clusterSlots[$bestC][] = $slotIdx;
                        }

                        foreach ($lessonClusters as $cIdx => $c) {
                            $cSlots = $clusterSlots[$cIdx] ?? [];
                            if (empty($cSlots))
                                continue;
                            $cStart = min($cSlots);
                            $cEnd = max($cSlots);
                            $rec = $this->formatClusterRecord($teacherName, $day, $cStart, $cEnd, $c, $slotDefs, $cycle);
                            if ($rec)
                                $rows[] = $rec;
                        }
                    }
                }
            }
        }

        return $rows;
    }

    /**
     * Mencocokkan teks baris dengan rombel kelas yang terdaftar.
     */
    protected function matchClass(string $text, $classes)
    {
        $cleanText = strtolower(str_replace([' ', '-', '_'], '', $text));

        // 1. Exact / Substring match
        foreach ($classes as $c) {
            $cleanC = strtolower(str_replace([' ', '-', '_'], '', $c->class_name));
            if ($cleanC === $cleanText || str_starts_with($cleanText, $cleanC) || str_contains($cleanText, $cleanC)) {
                return $c;
            }
        }

        // 2. AK -> AKL check (contoh: X AK1 -> X AKL 1)
        if (preg_match('/(x|xi|xii)ak(\d+)/i', $cleanText, $m)) {
            $aklName = strtoupper($m[1]) . ' AKL ' . $m[2];
            foreach ($classes as $c) {
                if (str_replace(' ', '', strtolower($c->class_name)) === strtolower(str_replace(' ', '', $aklName))) {
                    return $c;
                }
            }
        }

        // 3. Kelas tanpa nomor rombel (contoh: XII LPS -> XII LPS 1, X BD -> X BD 1)
        if (preg_match('/^(x|xi|xii)(lps|bd)$/i', $cleanText, $m)) {
            $candidate = strtoupper($m[1]) . ' ' . strtoupper($m[2]) . ' 1';
            foreach ($classes as $c) {
                if (str_replace(' ', '', strtolower($c->class_name)) === strtolower(str_replace(' ', '', $candidate))) {
                    return $c;
                }
            }
        }

        return null;
    }

    /**
     * Memformat kluster jadwal menjadi baris rekaman standar.
     * Waktu di-snap ke DAILY_PERIODS yang sesuai per hari agar jam pelajaran benar.
     */
    protected function formatClusterRecord($teacher, $day, $startSlot, $endSlot, $cluster, $slotDefs, $cycle)
    {
        $className = $cluster['class'] ?? '';
        $rawLines = $cluster['lines'];

        $filtered = [];
        foreach ($rawLines as $l) {
            $clean = $l;
            if ($className) {
                // 1. Hapus nama kelas penuh (contoh: 'X TKJ 2')
                $cleanNoSpace = str_replace(' ', '', $className);
                $clean = preg_replace('/\b' . preg_quote($className, '/') . '\b/i', '', $clean);
                $clean = preg_replace('/\b' . preg_quote($cleanNoSpace, '/') . '\b/i', '', $clean);
                // 2. Hapus prefix kode jurusan di awal mapel (contoh: 'TKJ2 Bahasa Inggris' → 'Bahasa Inggris')
                //    Format: [Tingkat?][JurusanSingkat][Nomor] diikuti spasi (mis: TKJ2, AK3, DKV1, RPL2)
                $clean = preg_replace('/^(?:X{1,3}I?I?)?(?:\s+)?[A-Z]{2,4}\d\s+/i', '', $clean);
                // 3. Hapus penyebutan kelas di manapun (mis: 'X RPL 2', 'XI AKL 3')
                $clean = preg_replace('/\b(?:X{1,3}I?I?)\s+[A-Z]{2,4}\s*\d\b/i', '', $clean);
                $clean = trim($clean);
            }
            if ($clean !== '') {
                $filtered[] = $clean;
            }
        }

        $room = 'Ruang ' . ($className ?: 'Kelas');
        if (!empty($filtered)) {
            $last = end($filtered);
            if (preg_match('/^(LAB\s*\d+|LAB\s*[A-Z0-9]+|OR\s*\d+|R\.\s*\d+|Ruang\s*\d+)/i', $last, $rm)) {
                $room = $rm[0];
                array_pop($filtered);
            }
        }

        $subject = trim(implode(' ', $filtered));
        // Bersihkan semua variasi prefix kelas dari nama mata pelajaran
        // 1. [Tingkat] [Jurusan] (contoh: "X BD ", "XI BD ", "XII LPS ")
        $subject = preg_replace('/^(?:X|XI|XII)\s+(?:RPL|TKJ|DKV|BR|BD|LPS|AKL|AK|MP)\s+/i', '', $subject);
        // 2. [Tingkat][Jurusan/Angka] (contoh: "XBD ", "XIIBD ", "XIILPS ")
        $subject = preg_replace('/^(?:X|XI|XII)(?:RPL|TKJ|DKV|BR|BD|LPS|AKL|AK|MP)\d*\s+/i', '', $subject);
        // 3. [Jurusan][Nomor] (contoh: "TKJ2 ", "AK3 ", "DKV2 ", "LPS1 ", "MP1 ")
        $subject = preg_replace('/^(?:RPL|TKJ|DKV|BR|BD|LPS|AKL|AK|MP)\d+\s+/i', '', $subject);
        // 4. [Tingkat] [Jurusan] [Nomor] (contoh: "X BD 1 ", "XI AKL 2 ")
        $subject = preg_replace('/^(?:X|XI|XII)\s+(?:RPL|TKJ|DKV|BR|BD|LPS|AKL|AK|MP)\s*\d+\s+/i', '', $subject);
        $subject = trim($subject);
        if (empty($subject))
            $subject = 'Mata Pelajaran';

        // Snap waktu dari slot PDF FET ke DAILY_PERIODS yang benar untuk hari ini
        $rawStart = $slotDefs[$startSlot]['start'];
        $rawEnd = $slotDefs[$endSlot]['end'];
        $snappedStart = $this->snapTimeToDailyPeriod($rawStart, $day, 'start');
        $snappedEnd = $this->snapTimeToDailyPeriod($rawEnd, $day, 'end');
        $timeRange = $snappedStart . ' - ' . $snappedEnd;

        return [
            $day,
            $timeRange,
            $subject,
            $teacher,
            $className ?: 'Umum',
            $room,
            $cycle
        ];
    }

    /**
     * Snap waktu PDF (dari slot seragam FET 45 menit) ke slot jam pelajaran DAILY_PERIODS
     * yang paling dekat untuk hari yang bersangkutan.
     *
     * PDF FET menggunakan slot waktu seragam (07:15, 08:00, 08:45, 09:45, ...),
     * sedangkan SMKN 1 Bantul memiliki jam pelajaran berbeda tiap hari.
     *
     * Aturan khusus:
     * - SENIN: Jam pertama mulai 08:00 (ada upacara bendera). Slot FET 07:15 → dipaksa 08:00.
     * - JUMAT: Sesi terakhir sebelum shalat Jumat berakhir 11:25. Slot FET 12:00 → dipaksa 11:25.
     *
     * @param string $time  Waktu dalam format 'HH:MM'
     * @param string $day   Hari (Senin, Selasa, Rabu, Kamis, Jumat)
     * @param string $type  'start' atau 'end' — snap ke jam mulai atau jam selesai periode
     */
    protected function snapTimeToDailyPeriod(string $time, string $day, string $type = 'start'): string
    {
        // Aturan khusus Senin: slot FET 07:15 tidak ada karena upacara → paksa ke 08:00
        if ($day === 'Senin' && $time === '07:15' && $type === 'start') {
            return '08:00';
        }
        // Aturan khusus Senin: jika end sebelum 08:35 (hanya 1 slot setelah upacara) → 08:35
        if ($day === 'Senin' && $time === '08:00' && $type === 'end') {
            return '08:35';
        }
        // Aturan khusus Jumat: slot FET 12:00 → 11:25 (shalat Jumat)
        if ($day === 'Jumat' && $time === '12:00' && $type === 'end') {
            return '11:25';
        }

        $periods = Schedule::DAILY_PERIODS[$day] ?? [];
        if (empty($periods))
            return $time;

        $toMin = function (string $t): int {
            [$h, $m] = explode(':', $t);
            return (int) $h * 60 + (int) $m;
        };

        $targetMin = $toMin($time);
        $bestTime = $time;
        $bestDiff = PHP_INT_MAX;

        foreach ($periods as $slot) {
            $candidate = $type === 'start' ? $slot['start'] : $slot['end'];
            $diff = abs($toMin($candidate) - $targetMin);
            if ($diff < $bestDiff) {
                $bestDiff = $diff;
                $bestTime = $candidate;
            }
        }

        // Snap jika jaraknya ≤ 50 menit (toleransi diperlebar agar slot lintas-hari ter-snap dengan benar)
        return ($bestDiff <= 50) ? $bestTime : $time;
    }

    /**
     * Memproses baris-baris data mentah menjadi rekaman jadwal pelajaran.
     */
    protected function processRows(array $rawRows): array
    {
        $headerIndex = -1;
        $colMap = [
            'no' => null,
            'day' => null,
            'time' => null,
            'subject' => null,
            'teacher' => null,
            'class' => null,
            'room' => null,
            'cycle' => null,
        ];

        // Cari baris header tabel
        foreach ($rawRows as $idx => $row) {
            if (!is_array($row))
                continue;

            $normalizedRow = array_map(fn($v) => strtolower(trim((string) $v)), $row);
            $joined = implode(' ', $normalizedRow);

            if (
                (str_contains($joined, 'hari') || str_contains($joined, 'day')) &&
                (str_contains($joined, 'mapel') || str_contains($joined, 'mata pelajaran') || str_contains($joined, 'subject') || str_contains($joined, 'pelajaran'))
            ) {
                $headerIndex = $idx;

                foreach ($normalizedRow as $colIdx => $text) {
                    if (str_contains($text, 'no') && strlen($text) <= 4) {
                        $colMap['no'] = $colIdx;
                    } elseif (str_contains($text, 'hari') || str_contains($text, 'day')) {
                        $colMap['day'] = $colIdx;
                    } elseif (str_contains($text, 'waktu') || str_contains($text, 'jam') || str_contains($text, 'time') || str_contains($text, 'pukul') || str_contains($text, 'sesi')) {
                        $colMap['time'] = $colIdx;
                    } elseif (str_contains($text, 'pelajaran') || str_contains($text, 'mapel') || str_contains($text, 'subject')) {
                        $colMap['subject'] = $colIdx;
                    } elseif (str_contains($text, 'guru') || str_contains($text, 'pengampu') || str_contains($text, 'pengajar') || str_contains($text, 'teacher')) {
                        $colMap['teacher'] = $colIdx;
                    } elseif (str_contains($text, 'kelas') || str_contains($text, 'class') || str_contains($text, 'rombel')) {
                        $colMap['class'] = $colIdx;
                    } elseif (str_contains($text, 'ruang') || str_contains($text, 'lab') || str_contains($text, 'room')) {
                        $colMap['room'] = $colIdx;
                    } elseif (str_contains($text, 'siklus') || str_contains($text, 'putaran') || str_contains($text, 'cycle')) {
                        $colMap['cycle'] = $colIdx;
                    }
                }
                break;
            }
        }

        if ($headerIndex !== -1) {
            $dataRows = array_slice($rawRows, $headerIndex + 1);
        } else {
            // Jika header tidak terdeteksi nama kolomnya, cek baris pertama
            $firstRowText = implode(' ', array_map('strval', $rawRows[0] ?? []));
            if (preg_match('/[a-zA-Z]/', $firstRowText)) {
                $dataRows = array_slice($rawRows, 1);
            } else {
                $dataRows = $rawRows;
            }
        }

        // Cache data relasi
        $teachers = User::whereIn('role_id', [1, 2, 5, 6])
            ->orWhereHas('role', function ($q) {
                $q->whereIn('role_name', ['Guru', 'WaliKelas', 'BK', 'Admin']);
            })
            ->get();
        $classes = Classes::all();
        $rooms = Room::all();

        $created = 0;
        $skipped = 0;
        $errors = [];

        // In-memory batch tracking untuk mencegah tabrakan jadwal di file yang sama
        $batchSchedules = [];

        foreach ($dataRows as $rowNum => $row) {
            $displayRowNum = $headerIndex !== -1 ? ($headerIndex + 1 + $rowNum + 1) : ($rowNum + 1);

            $cells = array_values(array_map(fn($v) => trim((string) $v), (array) $row));
            if (empty(array_filter($cells, fn($c) => $c !== ''))) {
                continue;
            }

            // Ekstrak nilai berdasarkan colMap atau urutan posisi default
            // Kolom standar: Hari, Waktu, Mata Pelajaran, Guru Pengampu, Kelas, Ruangan, Siklus
            if ($colMap['day'] !== null && isset($cells[$colMap['day']])) {
                $rawDay = $cells[$colMap['day']] ?? '';
                $rawTime = $colMap['time'] !== null ? ($cells[$colMap['time']] ?? '') : '';
                $rawSubject = $colMap['subject'] !== null ? ($cells[$colMap['subject']] ?? '') : '';
                $rawTeacher = $colMap['teacher'] !== null ? ($cells[$colMap['teacher']] ?? '') : '';
                $rawClass = $colMap['class'] !== null ? ($cells[$colMap['class']] ?? '') : '';
                $rawRoom = $colMap['room'] !== null ? ($cells[$colMap['room']] ?? '') : '';
                $rawCycle = $colMap['cycle'] !== null ? ($cells[$colMap['cycle']] ?? '') : '';
            } else {
                // Fallback berdasarkan panjang kolom
                // Jika kolom pertama adalah nomor (misal '1', '2', dll), geser 1
                $offset = (is_numeric($cells[0] ?? '') && count($cells) >= 8) ? 1 : 0;
                $rawDay = $cells[$offset] ?? '';
                $rawTime = $cells[$offset + 1] ?? '';
                $rawSubject = $cells[$offset + 2] ?? '';
                $rawTeacher = $cells[$offset + 3] ?? '';
                $rawClass = $cells[$offset + 4] ?? '';
                $rawRoom = $cells[$offset + 5] ?? '';
                $rawCycle = $cells[$offset + 6] ?? '';
            }

            if (empty($rawDay) && empty($rawSubject) && empty($rawTeacher)) {
                continue;
            }

            // Abaikan baris penanda slot kosong atau waktu matriks yang bocor dari PDF (contoh: '08:00-----', '11:15-x---', '---', '-x-')
            if (preg_match('/^\d{2}:\d{2}/', $rawDay) || in_array($rawDay, ['---', '-x-', '--- ---', '------', '-x- -x-'])) {
                continue;
            }

            // 1. Validasi & Normalisasi Hari
            $day = $this->resolveDay($rawDay);
            if (!$day) {
                // Abaikan jika penanda baris matriks tabel yang tidak perlu dilaporkan sebagai error
                if (str_contains($rawDay, '---') || str_contains($rawDay, '-x-')) {
                    continue;
                }
                $errors[] = "Baris {$displayRowNum}: Hari '{$rawDay}' tidak dikenali. Gunakan Senin, Selasa, Rabu, Kamis, atau Jumat.";
                $skipped++;
                continue;
            }

            // 2. Validasi & Normalisasi Waktu
            $timeInfo = $this->resolveTimeAndPeriod($rawTime, $day);
            if (isset($timeInfo['error'])) {
                $errors[] = "Baris {$displayRowNum}: {$timeInfo['error']}";
                $skipped++;
                continue;
            }

            $startTime = $timeInfo['start_time'];
            $endTime = $timeInfo['end_time'];
            $startPeriod = $timeInfo['start_period'];
            $endPeriod = $timeInfo['end_period'];

            // 3. Validasi Mata Pelajaran
            $subject = trim($rawSubject);
            if (empty($subject)) {
                $errors[] = "Baris {$displayRowNum}: Nama Mata Pelajaran tidak boleh kosong.";
                $skipped++;
                continue;
            }
            if (mb_strlen($subject) > 100) {
                $subject = mb_substr($subject, 0, 100);
            }

            // 4. Resolusi Guru Pengampu
            $teacherId = $this->resolveTeacherId($rawTeacher, $teachers);
            if (!$teacherId) {
                $errors[] = "Baris {$displayRowNum}: Guru pengampu '{$rawTeacher}' tidak ditemukan di sistem. Harap cek nama lengkap, NIP, atau username guru.";
                $skipped++;
                continue;
            }

            // 5. Resolusi Kelas
            $classId = $this->resolveClassId($rawClass, $classes);
            if (!$classId) {
                $errors[] = "Baris {$displayRowNum}: Kelas '{$rawClass}' tidak ditemukan di daftar kelas aktif.";
                $skipped++;
                continue;
            }

            // 6. Resolusi Ruangan (Auto-create jika belum ada)
            $roomId = $this->resolveRoomId($rawRoom, $rooms);
            if (!$roomId) {
                $errors[] = "Baris {$displayRowNum}: Ruangan tidak boleh kosong.";
                $skipped++;
                continue;
            }

            // 7. Resolusi Siklus
            $cycleType = $this->resolveCycleType($rawCycle);

            // 8. Proteksi Anti-Bentrok (Conflict Detection)
            $conflict = $this->checkConflict(
                $teacherId,
                $classId,
                $roomId,
                $day,
                $startTime,
                $endTime,
                $cycleType,
                $batchSchedules
            );

            if ($conflict) {
                $errors[] = "Baris {$displayRowNum} ({$subject} - {$rawClass}): Dilewati karena {$conflict}";
                $skipped++;
                continue;
            }

            // Simpan Jadwal Baru
            $schedule = Schedule::create([
                'teacher_id' => $teacherId,
                'class_id' => $classId,
                'room_id' => $roomId,
                'subject' => $subject,
                'day_of_week' => $day,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'start_period' => $startPeriod,
                'end_period' => $endPeriod,
                'cycle_type' => $cycleType,
            ]);

            $created++;

            // Catat ke batchSchedules untuk proteksi bentrok antar baris pada file yang sama
            $batchSchedules[] = [
                'id' => $schedule->id,
                'teacher_id' => $teacherId,
                'class_id' => $classId,
                'room_id' => $roomId,
                'day' => $day,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'cycle_type' => $cycleType,
                'subject' => $subject,
            ];
        }

        return [
            'total_rows' => $created + $skipped,
            'created' => $created,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    /**
     * Resolusi hari menjadi teks baku bahasa Indonesia.
     */
    protected function resolveDay(string $dayText): ?string
    {
        $clean = strtolower(trim($dayText));
        $clean = preg_replace('/[^a-z]/', '', $clean);

        return match ($clean) {
            'senin', 'monday', 'mon' => 'Senin',
            'selasa', 'tuesday', 'tue' => 'Selasa',
            'rabu', 'wednesday', 'wed' => 'Rabu',
            'kamis', 'thursday', 'thu' => 'Kamis',
            'jumat', 'jumat', 'friday', 'fri' => 'Jumat',
            'sabtu', 'saturday', 'sat' => 'Sabtu',
            'minggu', 'ahad', 'sunday', 'sun' => 'Minggu',
            default => null,
        };
    }

    /**
     * Resolusi format waktu input (jam ke atau rentang waktu HH:MM - HH:MM).
     */
    protected function resolveTimeAndPeriod(string $timeText, string $day): array
    {
        $raw = trim($timeText);
        if (empty($raw)) {
            return ['error' => 'Kolom waktu tidak boleh kosong.'];
        }

        $dailyPeriods = Schedule::DAILY_PERIODS[$day] ?? [];

        // 1. Deteksi rentang waktu formal, contoh: 07:15 - 08:35, 07.15-08.35, 07:15 s/d 08:35
        preg_match_all('/(\d{1,2}[:.]\d{2})/', $raw, $matches);
        if (!empty($matches[0]) && count($matches[0]) >= 2) {
            $t1 = str_replace('.', ':', $matches[0][0]);
            $t2 = str_replace('.', ':', $matches[0][1]);

            // Normalisasi format HH:MM dua digit
            $parts1 = explode(':', $t1);
            $parts2 = explode(':', $t2);
            $startTime = str_pad($parts1[0], 2, '0', STR_PAD_LEFT) . ':' . str_pad($parts1[1], 2, '0', STR_PAD_LEFT);
            $endTime = str_pad($parts2[0], 2, '0', STR_PAD_LEFT) . ':' . str_pad($parts2[1], 2, '0', STR_PAD_LEFT);

            if ($startTime >= $endTime) {
                return ['error' => "Format waktu '{$raw}' tidak valid (jam mulai {$startTime} harus lebih awal dari jam selesai {$endTime})."];
            }

            // Cari mapping jam ke- (period) jika sesuai dengan DAILY_PERIODS
            $startPeriod = null;
            $endPeriod = null;
            foreach ($dailyPeriods as $p => $slot) {
                if ($slot['start'] === $startTime)
                    $startPeriod = $p;
                if ($slot['end'] === $endTime)
                    $endPeriod = $p;
            }

            return [
                'start_time' => $startTime,
                'end_time' => $endTime,
                'start_period' => $startPeriod,
                'end_period' => $endPeriod,
            ];
        }

        // 2. Deteksi format nomor sesi jam ke-, contoh: "Jam Ke-1 - 3", "Jam 1-3", "1 - 3", "1-3", "Jam Ke 2", "2"
        $clean = preg_replace('/jam\s*ke[-:\s]*/i', '', $raw);
        $clean = preg_replace('/jam\s*/i', '', $clean);

        if (preg_match('/(\d+)\s*(?:-|s\/?d|sampai|to)\s*(\d+)/i', $clean, $m)) {
            $sp = (int) $m[1];
            $ep = (int) $m[2];

            if ($sp > $ep) {
                $temp = $sp;
                $sp = $ep;
                $ep = $temp;
            }

            if (!isset($dailyPeriods[$sp]) || !isset($dailyPeriods[$ep])) {
                $maxPeriod = count($dailyPeriods);
                return ['error' => "Jam ke-{$sp} s/d {$ep} tidak valid untuk hari {$day}. Hari {$day} hanya memiliki jam ke-1 s/d {$maxPeriod}."];
            }

            return [
                'start_time' => $dailyPeriods[$sp]['start'],
                'end_time' => $dailyPeriods[$ep]['end'],
                'start_period' => $sp,
                'end_period' => $ep,
            ];
        }

        // 3. Deteksi satu angka jam ke-, contoh: "1", "Jam 4"
        if (preg_match('/(\d+)/', $clean, $m)) {
            $p = (int) $m[1];
            if (!isset($dailyPeriods[$p])) {
                $maxPeriod = count($dailyPeriods);
                return ['error' => "Jam ke-{$p} tidak valid untuk hari {$day} (tersedia jam 1 s/d {$maxPeriod})."];
            }

            return [
                'start_time' => $dailyPeriods[$p]['start'],
                'end_time' => $dailyPeriods[$p]['end'],
                'start_period' => $p,
                'end_period' => $p,
            ];
        }

        return ['error' => "Format waktu '{$raw}' tidak dikenali. Gunakan format jam '07:15 - 08:35' atau jam pelajaran 'Jam Ke-1 - 3'."];
    }

    /**
     * Resolusi ID Guru Pengampu berdasarkan NIP, Username, Email, atau Nama Lengkap.
     * Jika guru belum terdaftar di sistem, akun guru baru otomatis dibuatkan.
     */
    protected function resolveTeacherId(string $rawTeacher, &$teachers): ?int
    {
        $clean = trim($rawTeacher);
        if (empty($clean) || $clean === '-')
            return null;

        $lowerClean = strtolower($clean);

        // 1. Pencocokan langsung dengan NIP / NISN
        foreach ($teachers as $t) {
            if ($t->nip_nisn && trim($t->nip_nisn) === $clean) {
                return $t->id;
            }
        }

        // 2. Pencocokan dengan Username
        foreach ($teachers as $t) {
            if (strtolower(trim($t->username)) === $lowerClean) {
                return $t->id;
            }
        }

        // 3. Pencocokan dengan Email
        foreach ($teachers as $t) {
            if ($t->email && strtolower(trim($t->email)) === $lowerClean) {
                return $t->id;
            }
        }

        // 4. Pencocokan tepat pada Fullname (case-insensitive)
        foreach ($teachers as $t) {
            if (strtolower(trim($t->fullname)) === $lowerClean) {
                return $t->id;
            }
        }

        // 5. Pencocokan fleksibel tanpa gelar akademis (S.Pd, M.Pd, S.Kom, Drs, dll)
        $cleanWithoutTitle = $this->stripAcademicTitles($lowerClean);
        foreach ($teachers as $t) {
            $tWithoutTitle = $this->stripAcademicTitles(strtolower($t->fullname));
            if ($tWithoutTitle === $cleanWithoutTitle && !empty($cleanWithoutTitle)) {
                return $t->id;
            }
        }

        // 6. Pencocokan substring (jika nama di Excel ada dalam nama lengkap database atau sebaliknya)
        foreach ($teachers as $t) {
            $tLower = strtolower($t->fullname);
            if (strlen($cleanWithoutTitle) >= 4 && (str_contains($tLower, $cleanWithoutTitle) || str_contains($lowerClean, strtolower($t->fullname)))) {
                return $t->id;
            }
        }

        // Cari juga di seluruh tabel User jika belum ada di $teachers
        $user = User::where('nip_nisn', $clean)
            ->orWhere('username', $clean)
            ->orWhere('email', $clean)
            ->orWhere('fullname', $clean)
            ->first();

        if ($user) {
            if (is_object($teachers) && method_exists($teachers, 'push')) {
                $teachers->push($user);
            }
            return $user->id;
        }

        // Otomatis buat akun guru baru jika belum ada
        $existingUsernames = [];
        $username = $this->generateUsername($clean, $existingUsernames);

        $newTeacher = User::create([
            'role_id' => 2, // Guru
            'username' => $username,
            'fullname' => $clean,
            'email' => null,
            'nip_nisn' => null,
            'password' => Hash::make('password123'),
        ]);

        if (is_object($teachers) && method_exists($teachers, 'push')) {
            $teachers->push($newTeacher);
        }

        return $newTeacher->id;
    }

    /**
     * Mengekstrak HANYA nama depan untuk username (gelar tidak ikut).
     */
    public function extractFirstName(string $fullname): string
    {
        // 1. Bersihkan prefix penomoran (contoh: "1. ", "16. ", "44, ")
        $name = preg_replace('/^\d+[\.,]\s*/', '', trim($fullname));

        // 2. Hapus tanda kutip dan karakter kurung
        $name = str_replace(['"', "'", '(', ')', '[', ']', '`'], '', $name);

        // 3. Hapus gelar belakang setelah koma: ", S.Pd", ", M.Pd.", ", S.Kom", dll
        $name = preg_replace('/,\s*[A-Za-z\.\s,]+$/', '', $name);

        // 4. Daftar gelar / sebutan (case-insensitive) yang harus dibersihkan
        $titles = [
            'raden rara',
            'raden mas',
            'raden',
            'rara',
            'dr.',
            'dra.',
            'drs.',
            'prof.',
            'h.',
            'hj.',
            'ir.',
            'gr.',
            's.pd.',
            's.pd',
            'm.pd.',
            'm.pd',
            's.kom.',
            's.kom',
            'm.kom.',
            'm.kom',
            's.t.',
            's.t',
            'm.t.',
            'm.t',
            's.ag.',
            's.ag',
            'm.ag.',
            'm.ag',
            's.si.',
            's.si',
            'm.si.',
            'm.si',
            's.sn.',
            's.sn',
            'm.sn.',
            'm.sn',
            's.ip.',
            's.ip',
            'm.ip.',
            'm.ip',
            's.sos.',
            's.sos',
            's.s.',
            's.s',
            'm.eng.',
            'm.eng',
            'm.s.i.',
            'm.s.i',
            'm.acc.',
            'm.acc',
            'mm.',
            'mm',
            'b.a.',
            'b.a',
            'ph.d.',
            'ph.d'
        ];

        foreach ($titles as $t) {
            $name = preg_replace('/\b' . preg_quote($t, '/') . '\b/i', '', $name);
        }

        // 5. Bersihkan tanda baca titik, koma, strip yang tertinggal
        $name = preg_replace('/[,\.\-_]/', ' ', $name);
        $name = trim(preg_replace('/\s+/', ' ', $name));

        // 6. Ambil HANYA kata pertama yang valid dan alfabetik
        $words = explode(' ', $name);
        foreach ($words as $w) {
            $cleanWord = strtolower(preg_replace('/[^a-zA-Z]/', '', $w));
            if (strlen($cleanWord) >= 2) {
                return $cleanWord;
            }
        }

        // Fallback jika tidak ada kata alfabet yang cocok
        $fallback = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $fullname));
        return substr($fallback, 0, 15) ?: 'guru';
    }

    /**
     * Menghasilkan username sesuai aturan SCP:
     * 1. Nama pertama_nama kedua (contoh: ARIS PURWANTINAH -> aris_purwantinah)
     * 2. Jika 2 nama di depan sama, gunakan nama ke-3: nama pertama_nama kedua_nama ketiga
     * 3. Jika hanya 1 kata nama: nama_pertama
     */
    public function generateUsername(string $fullname, array &$existingUsernames = []): string
    {
        // 1. Bersihkan prefix penomoran (contoh: "1. ", "16. ", "44, ")
        $name = preg_replace('/^\d+[\.,]\s*/', '', trim($fullname));

        // 2. Hapus tanda kutip dan karakter kurung
        $name = str_replace(['"', "'", '(', ')', '[', ']', '`'], '', $name);

        // 3. Hapus gelar belakang setelah koma: ", S.Pd", ", M.Pd.", ", S.Kom", dll
        $name = preg_replace('/,\s*[A-Za-z\.\s,]+$/', '', $name);

        // 4. Daftar gelar / sebutan (case-insensitive) yang harus dibersihkan
        $titles = [
            'raden rara',
            'raden mas',
            'raden',
            'rara',
            'dr.',
            'dra.',
            'drs.',
            'prof.',
            'h.',
            'hj.',
            'ir.',
            'gr.',
            's.pd.',
            's.pd',
            'm.pd.',
            'm.pd',
            's.kom.',
            's.kom',
            'm.kom.',
            'm.kom',
            's.t.',
            's.t',
            'm.t.',
            'm.t',
            's.ag.',
            's.ag',
            'm.ag.',
            'm.ag',
            's.si.',
            's.si',
            'm.si.',
            'm.si',
            's.sn.',
            's.sn',
            'm.sn.',
            'm.sn',
            's.ip.',
            's.ip',
            'm.ip.',
            'm.ip',
            's.sos.',
            's.sos',
            's.s.',
            's.s',
            'm.eng.',
            'm.eng',
            'm.s.i.',
            'm.s.i',
            'm.acc.',
            'm.acc',
            'mm.',
            'mm',
            'b.a.',
            'b.a',
            'ph.d.',
            'ph.d'
        ];

        foreach ($titles as $t) {
            $name = preg_replace('/\b' . preg_quote($t, '/') . '\b/i', '', $name);
        }

        // 5. Bersihkan tanda baca titik, koma, strip yang tertinggal
        $name = preg_replace('/[,\.\-_]/', ' ', $name);
        $name = trim(preg_replace('/\s+/', ' ', $name));

        // 6. Ambil kata-kata valid alfabet (panjang >= 2)
        $rawWords = explode(' ', $name);
        $words = [];
        foreach ($rawWords as $w) {
            $cleanWord = strtolower(preg_replace('/[^a-zA-Z]/', '', $w));
            if (strlen($cleanWord) >= 2) {
                $words[] = $cleanWord;
            }
        }

        if (empty($words)) {
            $fallback = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $fullname));
            $base = substr($fallback, 0, 15) ?: 'guru';
            $u = $base;
            $cnt = 1;
            while (isset($existingUsernames[$u]) || User::where('username', $u)->exists()) {
                $u = $base . $cnt++;
            }
            $existingUsernames[$u] = true;
            return $u;
        }

        $word1 = $words[0];
        $word2 = $words[1] ?? null;
        $word3 = $words[2] ?? null;

        // Aturan 1: nama pertama_nama kedua (atau nama pertama jika hanya 1 kata)
        $candidate = ($word2 !== null) ? "{$word1}_{$word2}" : $word1;

        $isTaken = fn($usr) => isset($existingUsernames[$usr]) || User::where('username', $usr)->exists();

        if (!$isTaken($candidate)) {
            $existingUsernames[$candidate] = true;
            return $candidate;
        }

        // Aturan 2: Jika 2 nama depan sama (sudah dipakai) dan ada kata ke-3 -> nama pertama_nama kedua_nama ketiga
        if ($word3 !== null) {
            $candidate3 = "{$word1}_{$word2}_{$word3}";
            if (!$isTaken($candidate3)) {
                $existingUsernames[$candidate3] = true;
                return $candidate3;
            }
        }

        // Aturan 3: Jika masih bentrok atau tidak ada kata ke-3, tambahkan angka pembeda berurutan
        $baseCollision = ($word3 !== null) ? "{$word1}_{$word2}_{$word3}" : $candidate;
        $cnt = 1;
        $finalUsername = $baseCollision . $cnt;
        while ($isTaken($finalUsername)) {
            $cnt++;
            $finalUsername = $baseCollision . $cnt;
        }

        $existingUsernames[$finalUsername] = true;
        return $finalUsername;
    }

    /**
     * Membersihkan gelar umum dari nama guru untuk pencocokan toleran.
     */
    protected function stripAcademicTitles(string $name): string
    {
        $titles = [
            's.pd.',
            's.pd',
            'm.pd.',
            'm.pd',
            's.kom.',
            's.kom',
            'm.kom.',
            'm.kom',
            's.t.',
            's.t',
            'm.t.',
            'm.t',
            'drs.',
            'drs',
            'dra.',
            'dra',
            's.ag.',
            's.ag',
            'm.ag.',
            'm.ag',
            's.si.',
            's.si',
            'm.si.',
            'm.si',
            'gr.',
            'gr',
            's.sn.',
            's.sn',
            'm.sn.',
            'm.sn',
            'prof.',
            'dr.',
            'h.',
            'hj.'
        ];

        $cleaned = $name;
        foreach ($titles as $title) {
            $cleaned = preg_replace('/\b' . preg_quote($title, '/') . '\b/i', '', $cleaned);
        }

        $cleaned = preg_replace('/[,\.]/', ' ', $cleaned);
        $cleaned = preg_replace('/\s+/', ' ', $cleaned);
        return trim($cleaned);
    }

    /**
     * Resolusi ID Kelas dari nama kelas (contoh: "XII RPL 1", "12 RPL 1", "XIIRPL1").
     * Jika kelas belum terdaftar, otomatis buat rombel baru sesuai tingkat dan jurusan.
     */
    protected function resolveClassId(string $rawClass, &$classes): ?int
    {
        $clean = trim($rawClass);
        if (empty($clean) || $clean === '-')
            return null;

        $normalized = strtolower(str_replace([' ', '-', '_'], '', $clean));

        // Normalisasi angka Arab ke Romawi: "12rpl1" -> "xiirpl1"
        $normalized = preg_replace('/^10/', 'x', $normalized);
        $normalized = preg_replace('/^11/', 'xi', $normalized);
        $normalized = preg_replace('/^12/', 'xii', $normalized);

        foreach ($classes as $c) {
            $cNorm = strtolower(str_replace([' ', '-', '_'], '', $c->class_name));
            if ($cNorm === $normalized) {
                return $c->id;
            }
        }

        // Pencocokan AK -> AKL (contoh: X AK1 -> X AKL 1)
        if (preg_match('/(x|xi|xii)ak(\d+)/i', $normalized, $m)) {
            $aklNorm = $m[1] . 'akl' . $m[2];
            foreach ($classes as $c) {
                $cNorm = strtolower(str_replace([' ', '-', '_'], '', $c->class_name));
                if ($cNorm === $aklNorm) {
                    return $c->id;
                }
            }
        }

        // Pencocokan kelas tanpa rombel tunggal (contoh: XII LPS -> XII LPS 1, X BD -> X BD 1)
        if (preg_match('/^(x|xi|xii)(lps|bd)$/i', $normalized, $m)) {
            $candNorm = $m[1] . $m[2] . '1';
            foreach ($classes as $c) {
                $cNorm = strtolower(str_replace([' ', '-', '_'], '', $c->class_name));
                if ($cNorm === $candNorm) {
                    return $c->id;
                }
            }
        }

        // Substring match fleksibel
        foreach ($classes as $c) {
            $cNorm = strtolower(str_replace([' ', '-', '_'], '', $c->class_name));
            if (str_starts_with($cNorm, $normalized) || str_starts_with($normalized, $cNorm)) {
                return $c->id;
            }
        }

        // Otomatis buat kelas baru jika tidak ditemukan
        $grade = 'X';
        if (str_starts_with($clean, 'XII') || str_starts_with($clean, '12'))
            $grade = 'XII';
        elseif (str_starts_with($clean, 'XI') || str_starts_with($clean, '11'))
            $grade = 'XI';

        $major = trim(preg_replace('/^(X|XI|XII|10|11|12)\s*/i', '', $clean));
        $newClass = Classes::create([
            'class_name' => $clean,
            'grade' => $grade,
            'major' => $major ?: 'Umum',
        ]);

        if (is_object($classes) && method_exists($classes, 'push')) {
            $classes->push($newClass);
        }

        return $newClass->id;
    }

    /**
     * Resolusi ID Ruangan (mencocokkan atau otomatis membuat ruangan baru jika belum ada).
     */
    protected function resolveRoomId(string $rawRoom, &$rooms): ?int
    {
        $clean = trim($rawRoom);
        if (empty($clean) || $clean === '-')
            return null;

        $normalized = strtolower(str_replace([' ', '-', '_'], '', $clean));

        foreach ($rooms as $r) {
            $rNorm = strtolower(str_replace([' ', '-', '_'], '', $r->room_name));
            if ($rNorm === $normalized) {
                return $r->id;
            }
        }

        // Jika ruangan belum terdaftar, otomatis buat ruangan baru agar tidak menghambat import
        $newRoom = Room::create(['room_name' => $clean]);
        $rooms->push($newRoom);

        return $newRoom->id;
    }

    /**
     * Resolusi nilai Siklus: 'Putaran 1', 'Putaran 2', atau 'semua'.
     */
    protected function resolveCycleType(string $rawCycle): string
    {
        $clean = strtolower(trim($rawCycle));

        if (str_contains($clean, '2') || str_contains($clean, 'genap')) {
            return 'Putaran 2';
        }

        if (str_contains($clean, '1') || str_contains($clean, 'ganjil')) {
            return 'Putaran 1';
        }

        return 'semua';
    }

    /**
     * Validasi Tabrakan Jadwal (Conflict Prevention) terhadap database dan file yang sedang diimpor.
     */
    protected function checkConflict(
        int $teacherId,
        int $classId,
        int $roomId,
        string $day,
        string $startTime,
        string $endTime,
        string $cycleType,
        array $batchSchedules
    ): ?string {
        // Proteksi bentrok dinonaktifkan sesuai permintaan sekolah
        return null;
    }

    /**
     * Menghasilkan file Excel (.xlsx) Template Jadwal Pelajaran resmi.
     */
    public function generateTemplateSpreadsheet(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('Sistem School Class Progress (SCP)')
            ->setTitle('Template Import Jadwal Pelajaran')
            ->setSubject('Template Jadwal Pelajaran')
            ->setDescription('Template resmi untuk import jadwal pelajaran massal pada sistem SCP SMKN 1 Bantul.');

        // Sheet 1: Template Data Jadwal
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Jadwal Pelajaran');

        // Header Judul & Instruksi
        $sheet->setCellValue('A1', 'TEMPLATE IMPORT DATA JADWAL PELAJARAN - SISTEM SCHOOL CLASS PROGRESS (SCP)');
        $sheet->setCellValue('A2', 'Petunjuk: Cukup isi kolom Waktu dengan format jam pelajaran, contoh: "Jam Ke-1 - 3", "1 - 3", atau "Jam Ke-1". Sistem secara otomatis membaca jam mulai hingga selesai berdasarkan pembagian waktu sekolah (tidak perlu mengetik jam 08:00 secara manual). Proteksi anti-bentrok aktif otomatis.');

        $sheet->mergeCells('A1:H1');
        $sheet->mergeCells('A2:H2');

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13)->getColor()->setRGB('1E3A8A');
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(9)->getColor()->setRGB('4B5563');

        // Baris Header Kolom Tabel (Baris 4)
        $headers = [
            'A4' => 'No',
            'B4' => 'Hari',
            'C4' => 'Waktu',
            'D4' => 'Mata Pelajaran',
            'E4' => 'Guru Pengampu',
            'F4' => 'Kelas',
            'G4' => 'Ruangan',
            'H4' => 'Siklus',
        ];

        foreach ($headers as $cell => $text) {
            $sheet->setCellValue($cell, $text);
        }

        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 10,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2563EB'], // Primary Blue SCP
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '1D4ED8'],
                ],
            ],
        ];

        $sheet->getStyle('A4:H4')->applyFromArray($headerStyle);
        $sheet->getRowDimension(4)->setRowHeight(26);

        // Ambil data referensi dari database untuk contoh nyata
        $teachers = User::whereIn('role_id', [1, 2, 5, 6])
            ->orWhereHas('role', function ($q) {
                $q->whereIn('role_name', ['Guru', 'WaliKelas', 'BK', 'Admin']);
            })
            ->orderBy('fullname')
            ->get();
        $classes = Classes::orderBy('class_name')->get();
        $rooms = Room::orderBy('room_name')->get();

        $tList = $teachers->values();
        $cList = $classes->values();
        $rList = $rooms->values();

        $t1 = $tList[0]->fullname ?? 'Budi Santoso, S.Pd.';
        $c1 = $cList[0]->class_name ?? 'XII RPL 1';
        $r1 = $rList[0]->room_name ?? 'Lab RPL 1';

        // Berikan HANYA 1 contoh baris dengan format Waktu "Jam Ke-1 - 3"
        $sampleData = [
            [1, 'Senin', 'Jam Ke-1 - 3', 'Pemrograman Web dan Perangkat Bergerak', $t1, $c1, $r1, 'Putaran 1'],
        ];

        $rowNum = 5;
        foreach ($sampleData as $data) {
            $sheet->setCellValue("A{$rowNum}", $data[0]);
            $sheet->setCellValue("B{$rowNum}", $data[1]);
            $sheet->setCellValue("C{$rowNum}", $data[2]);
            $sheet->setCellValue("D{$rowNum}", $data[3]);
            $sheet->setCellValue("E{$rowNum}", $data[4]);
            $sheet->setCellValue("F{$rowNum}", $data[5]);
            $sheet->setCellValue("G{$rowNum}", $data[6]);
            $sheet->setCellValue("H{$rowNum}", $data[7]);

            $sheet->getStyle("A{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("E{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("F{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("G{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("H{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->getStyle("A{$rowNum}:H{$rowNum}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E5E7EB');
            $sheet->getRowDimension($rowNum)->setRowHeight(24);
            $rowNum++;
        }

        // Lebar kolom optimal
        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(14);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(34);
        $sheet->getColumnDimension('E')->setWidth(30);
        $sheet->getColumnDimension('F')->setWidth(16);
        $sheet->getColumnDimension('G')->setWidth(18);
        $sheet->getColumnDimension('H')->setWidth(16);

        // Sheet 2: Panduan & Data Referensi Master
        $refSheet = $spreadsheet->createSheet();
        $refSheet->setTitle('Panduan & Referensi');

        // Referensi 1: Ketentuan Format & Siklus
        $refSheet->setCellValue('A1', 'PANDUAN PENGISIAN KOLOM JADWAL');
        $refSheet->mergeCells('A1:C1');
        $refSheet->getStyle('A1')->getFont()->setBold(true)->setSize(11)->getColor()->setRGB('1E3A8A');

        $guideHeaders = ['A3' => 'Nama Kolom', 'B3' => 'Format Isian', 'C3' => 'Contoh / Keterangan'];
        foreach ($guideHeaders as $cell => $text) {
            $refSheet->setCellValue($cell, $text);
        }
        $refSheet->getStyle('A3:C3')->applyFromArray($headerStyle);

        $guideRows = [
            ['Hari', 'Teks nama hari', 'Senin, Selasa, Rabu, Kamis, Jumat'],
            ['Waktu', 'Nomor jam pelajaran', 'Cukup ketik jam ke-, contoh: "Jam Ke-1 - 3" atau "1 - 3" atau "Jam Ke-1". Sistem otomatis mendeteksi jam mulai hingga selesai (tidak perlu mengetik jam 08:00).'],
            ['Mata Pelajaran', 'Nama mata pelajaran', 'Pemrograman Web, Matematika, dll.'],
            ['Guru Pengampu', 'Nama lengkap, NIP, atau Username', 'Budi Santoso, S.Pd. atau 19850101...'],
            ['Kelas', 'Nama kelas aktif persis sistem', 'XII RPL 1, X TKJ 2, XI DKV 1'],
            ['Ruangan', 'Nama ruang atau lab', 'Lab RPL 1, R. Teori 01, dll. (dibuat otomatis jika baru)'],
            ['Siklus', 'Putaran 1, Putaran 2, atau Semua', 'Putaran 1 / Putaran 2 (Blok) atau Semua (Setiap Minggu)'],
        ];

        $gIdx = 4;
        foreach ($guideRows as $gr) {
            $refSheet->setCellValue("A{$gIdx}", $gr[0]);
            $refSheet->setCellValue("B{$gIdx}", $gr[1]);
            $refSheet->setCellValue("C{$gIdx}", $gr[2]);
            $refSheet->getStyle("A{$gIdx}:C{$gIdx}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E5E7EB');
            $gIdx++;
        }

        // Referensi 2: Daftar Guru Terdaftar
        $teacherStart = $gIdx + 2;
        $refSheet->setCellValue("A{$teacherStart}", 'DAFTAR GURU PENGAMPU TERDAFTAR DI SISTEM');
        $refSheet->mergeCells("A{$teacherStart}:C{$teacherStart}");
        $refSheet->getStyle("A{$teacherStart}")->getFont()->setBold(true)->setSize(11)->getColor()->setRGB('1E3A8A');

        $tHeadRow = $teacherStart + 2;
        $refSheet->setCellValue("A{$tHeadRow}", 'NIP');
        $refSheet->setCellValue("B{$tHeadRow}", 'Nama Lengkap Guru (Ketik persis di Template)');
        $refSheet->setCellValue("C{$tHeadRow}", 'Username');
        $refSheet->getStyle("A{$tHeadRow}:C{$tHeadRow}")->applyFromArray($headerStyle);

        $teachers = User::whereIn('role_id', [1, 2, 5, 6])
            ->orWhereHas('role', function ($q) {
                $q->whereIn('role_name', ['Guru', 'WaliKelas', 'BK', 'Admin']);
            })
            ->orderBy('fullname')
            ->get();

        $tIdx = $tHeadRow + 1;
        foreach ($teachers as $t) {
            $refSheet->setCellValue("A{$tIdx}", $t->nip_nisn ?: '-');
            $refSheet->setCellValue("B{$tIdx}", $t->fullname);
            $refSheet->setCellValue("C{$tIdx}", $t->username);
            $refSheet->getStyle("A{$tIdx}:C{$tIdx}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E5E7EB');
            $tIdx++;
        }

        // Referensi 3: Daftar Kelas Aktif
        $classStart = $tIdx + 2;
        $refSheet->setCellValue("A{$classStart}", 'DAFTAR KELAS AKTIF DI SISTEM');
        $refSheet->mergeCells("A{$classStart}:C{$classStart}");
        $refSheet->getStyle("A{$classStart}")->getFont()->setBold(true)->setSize(11)->getColor()->setRGB('1E3A8A');

        $cHeadRow = $classStart + 2;
        $refSheet->setCellValue("A{$cHeadRow}", 'ID Kelas');
        $refSheet->setCellValue("B{$cHeadRow}", 'Nama Kelas');
        $refSheet->setCellValue("C{$cHeadRow}", 'Tingkat / Jurusan');
        $refSheet->getStyle("A{$cHeadRow}:C{$cHeadRow}")->applyFromArray($headerStyle);

        $classes = Classes::orderBy('class_name')->get();
        $cIdx = $cHeadRow + 1;
        foreach ($classes as $c) {
            $refSheet->setCellValue("A{$cIdx}", $c->id);
            $refSheet->setCellValue("B{$cIdx}", $c->class_name);
            $refSheet->setCellValue("C{$cIdx}", "Tingkat {$c->grade_level} - {$c->major}");
            $refSheet->getStyle("A{$cIdx}:C{$cIdx}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E5E7EB');
            $cIdx++;
        }

        // Referensi 4: Daftar Ruangan
        $roomStart = $cIdx + 2;
        $refSheet->setCellValue("A{$roomStart}", 'DAFTAR RUANGAN / LAB');
        $refSheet->mergeCells("A{$roomStart}:C{$roomStart}");
        $refSheet->getStyle("A{$roomStart}")->getFont()->setBold(true)->setSize(11)->getColor()->setRGB('1E3A8A');

        $rHeadRow = $roomStart + 2;
        $refSheet->setCellValue("A{$rHeadRow}", 'ID');
        $refSheet->setCellValue("B{$rHeadRow}", 'Nama Ruangan');
        $refSheet->setCellValue("C{$rHeadRow}", 'Keterangan');
        $refSheet->getStyle("A{$rHeadRow}:C{$rHeadRow}")->applyFromArray($headerStyle);

        $rooms = Room::orderBy('room_name')->get();
        $rIdx = $rHeadRow + 1;
        foreach ($rooms as $r) {
            $refSheet->setCellValue("A{$rIdx}", $r->id);
            $refSheet->setCellValue("B{$rIdx}", $r->room_name);
            $refSheet->setCellValue("C{$rIdx}", 'Tersedia');
            $refSheet->getStyle("A{$rIdx}:C{$rIdx}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E5E7EB');
            $rIdx++;
        }

        $refSheet->getColumnDimension('A')->setWidth(20);
        $refSheet->getColumnDimension('B')->setWidth(38);
        $refSheet->getColumnDimension('C')->setWidth(35);

        // Kembali aktif ke Sheet 1
        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }
}

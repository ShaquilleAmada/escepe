<?php

namespace App\Services;

use App\Models\Classes;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Smalot\PdfParser\Parser as PdfParser;

class WalasImportService
{
    /**
     * Memproses file upload (Excel, CSV, Word, atau PDF) untuk impor plotting Wali Kelas.
     */
    public function import(UploadedFile $file): array
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');

        $ext = strtolower($file->getClientOriginalExtension());
        $path = $file->getRealPath();

        $rows = match ($ext) {
            'xlsx', 'xls', 'csv' => $this->parseSpreadsheet($path),
            'docx' => $this->parseDocx($path),
            'pdf' => $this->parsePdf($path),
            default => throw new \Exception("Format file .{$ext} tidak didukung. Gunakan Excel (.xlsx, .xls, .csv), Word (.docx), atau PDF."),
        };

        if (empty($rows)) {
            return [
                'total_rows' => 0,
                'updated' => 0,
                'errors' => ['File tidak memiliki data atau baris tidak dapat dibaca.'],
            ];
        }

        return $this->processRows($rows);
    }

    protected function parseSpreadsheet(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        return $sheet->toArray(null, true, true, false);
    }

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
        }
        return $rows;
    }

    protected function parsePdf(string $filePath): array
    {
        $parser = new PdfParser();
        $pdf = $parser->parseFile($filePath);
        $text = $pdf->getText();

        $rows = [];
        $lines = explode("\n", $text);
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (empty($trimmed)) continue;

            // Jika dipisah tab atau multi spasi atau koma
            if (str_contains($trimmed, "\t")) {
                $cols = explode("\t", $trimmed);
            } elseif (str_contains($trimmed, ',')) {
                $cols = str_getcsv($trimmed);
            } else {
                $cols = preg_split('/\s{2,}/', $trimmed);
            }
            $cleanCols = array_values(array_map('trim', (array)$cols));
            if (count($cleanCols) >= 2) {
                $rows[] = $cleanCols;
            }
        }
        return $rows;
    }

    /**
     * Memproses baris data mentah menjadi penetapan Wali Kelas ke database.
     */
    public function processRows(array $rawRows): array
    {
        $classes = Classes::all();
        $teachers = User::whereIn('role_id', [1, 2, 5, 6])->get();
        $scheduleImportService = new ScheduleImportService();
        $refl = new \ReflectionClass($scheduleImportService);
        $stripAcademicTitles = $refl->getMethod('stripAcademicTitles');
        $stripAcademicTitles->setAccessible(true);

        $updated = 0;
        $skipped = 0;
        $errors = [];

        // Deteksi header kolom jika ada
        $classCol = null;
        $walasCol = null;

        foreach ($rawRows as $idx => $row) {
            $cells = array_values(array_map(fn($v) => trim((string)$v), (array)$row));
            if (empty(array_filter($cells, fn($c) => $c !== ''))) continue;

            $joined = strtolower(implode(' ', $cells));

            // Cek baris judul / header
            if (str_contains($joined, 'kelas') && (str_contains($joined, 'wali') || str_contains($joined, 'guru') || str_contains($joined, 'walas') || str_contains($joined, 'nama'))) {
                foreach ($cells as $cIdx => $cell) {
                    $cLower = strtolower($cell);
                    if (str_contains($cLower, 'kelas') && !str_contains($cLower, 'wali')) {
                        $classCol = $cIdx;
                    } elseif (str_contains($cLower, 'wali') || str_contains($cLower, 'walas')) {
                        $walasCol = $cIdx;
                    }
                }
                continue;
            }

            // Ekstrak nilai kelas & nama walas
            $rawClass = '';
            $rawWalas = '';

            if ($classCol !== null && $walasCol !== null) {
                $rawClass = $cells[$classCol] ?? '';
                $rawWalas = $cells[$walasCol] ?? '';
            } else {
                // Heuristik posisi: jika kolom 0 adalah nomor, ambil kolom 1 dan 2
                if (is_numeric($cells[0] ?? '') && count($cells) >= 3) {
                    $rawClass = $cells[1] ?? '';
                    $rawWalas = $cells[2] ?? '';
                } elseif (count($cells) >= 2) {
                    $rawClass = $cells[0] ?? '';
                    $rawWalas = $cells[1] ?? '';
                }
            }

            if (empty($rawClass) || empty($rawWalas)) continue;
            if (str_contains(strtolower($rawClass), 'daftar') || str_contains(strtolower($rawClass), 'semester') || str_contains(strtolower($rawClass), 'keterangan')) continue;

            $matchedClass = $this->matchClass($rawClass, $classes);
            if (!$matchedClass) {
                $errors[] = "Baris " . ($idx + 1) . ": Rombel kelas '{$rawClass}' tidak dikenali di sistem.";
                $skipped++;
                continue;
            }

            $matchedTeacher = $this->findTeacherSmart($rawWalas, $teachers, $stripAcademicTitles, $scheduleImportService);
            if (!$matchedTeacher) {
                $errors[] = "Baris " . ($idx + 1) . ": Guru walas '{$rawWalas}' untuk kelas {$matchedClass->class_name} tidak ditemukan di sistem.";
                $skipped++;
                continue;
            }

            // Kembalikan walas lama kelas ini ke Guru jika bukan guru yang sama
            $currentWalas = User::where('class_id', $matchedClass->id)->where('role_id', 6)->get();
            foreach ($currentWalas as $old) {
                if ($old->id != $matchedTeacher->id) {
                    $old->update(['role_id' => 2, 'class_id' => null]);
                }
            }

            // Tetapkan guru ini sebagai WaliKelas
            $matchedTeacher->update([
                'role_id' => 6,
                'class_id' => $matchedClass->id,
            ]);

            $updated++;
        }

        return [
            'total_rows' => $updated + $skipped,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    public function matchClass(string $raw, $classes)
    {
        $c = strtoupper(trim($raw));
        $c = preg_replace('/\s+/', ' ', $c);

        // Normalisasi format jurusan & nomor: 'AK 1' -> 'AKL 1', 'AK1' -> 'AKL 1'
        $norm = preg_replace('/\bAK\s*(\d)/', 'AKL $1', $c);
        $norm = preg_replace('/\bAKL(\d)/', 'AKL $1', $norm);

        // Kelas tunggal tanpa rombel (misal: 'X BD' -> 'X BD 1', 'XII LPS' -> 'XII LPS 1')
        if (preg_match('/^(X|XI|XII)\s+(BD|LPS)$/', $norm)) {
            $norm .= ' 1';
        }

        foreach ($classes as $cls) {
            if (strtoupper($cls->class_name) === $norm) {
                return $cls;
            }
            if (str_replace(' ', '', strtoupper($cls->class_name)) === str_replace(' ', '', $norm)) {
                return $cls;
            }
        }
        return null;
    }

    public function findTeacherSmart(string $name, $teachers, $stripMethod, $service)
    {
        $clean = trim($name, " \t\n\r\"");
        $lower = strtolower($clean);

        // 1. Direct match
        foreach ($teachers as $t) {
            if (strtolower($t->fullname) === $lower) return $t;
        }

        // 2. Tanpa gelar
        $cleanNoTitle = $stripMethod->invoke($service, $lower);
        $cleanAlpha = preg_replace('/[^a-z]/', '', $cleanNoTitle);

        foreach ($teachers as $t) {
            $tNoTitle = $stripMethod->invoke($service, strtolower($t->fullname));
            $tAlpha = preg_replace('/[^a-z]/', '', $tNoTitle);
            if ($cleanAlpha === $tAlpha && !empty($cleanAlpha)) return $t;
        }

        // 3. Kata kunci nama
        $cleanWords = array_values(array_filter(explode(' ', preg_replace('/[^a-z]/', ' ', $cleanNoTitle)), fn($w) => strlen($w) >= 3));

        $bestMatch = null;
        $maxMatches = 0;

        foreach ($teachers as $t) {
            $tNoTitle = $stripMethod->invoke($service, strtolower($t->fullname));
            $tWords = array_values(array_filter(explode(' ', preg_replace('/[^a-z]/', ' ', $tNoTitle)), fn($w) => strlen($w) >= 3));

            $matches = count(array_intersect($cleanWords, $tWords));
            if ($matches >= 2 && $matches > $maxMatches) {
                $maxMatches = $matches;
                $bestMatch = $t;
            } elseif ($matches === 1 && count($cleanWords) === 1 && $matches > $maxMatches) {
                $maxMatches = $matches;
                $bestMatch = $t;
            }
        }

        if ($bestMatch) return $bestMatch;

        // 4. Toleransi salah ketik (Levenshtein)
        foreach ($teachers as $t) {
            $tNoTitle = $stripMethod->invoke($service, strtolower($t->fullname));
            $tAlpha = preg_replace('/[^a-z]/', '', $tNoTitle);
            $dist = levenshtein($cleanAlpha, $tAlpha);
            if ($dist <= 2 && strlen($cleanAlpha) >= 6) {
                return $t;
            }
        }

        return null;
    }
}


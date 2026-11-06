<?php

namespace App\Services;

use App\Models\User;
use App\Models\Role;
use App\Models\Classes;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Smalot\PdfParser\Parser as PdfParser;

class UserImportService
{
    /**
     * Memproses file yang diunggah (Excel/CSV, Word DOCX, atau PDF).
     */
    public function import(UploadedFile $file): array
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');

        $extension = strtolower($file->getClientOriginalExtension());
        $path = $file->getRealPath();

        $rows = match ($extension) {
            'xlsx', 'xls', 'csv' => $this->parseSpreadsheet($path),
            'docx' => $this->parseDocx($path),
            'pdf' => $this->parsePdf($path),
            default => throw new \InvalidArgumentException("Format file .{$extension} tidak didukung. Harap unggah file Excel (.xlsx, .xls, .csv), Word (.docx), atau PDF (.pdf)."),
        };

        if (empty($rows)) {
            return [
                'total_rows' => 0,
                'created' => 0,
                'updated' => 0,
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
     * Otomatis mendukung buku kerja multi-sheet (Sheet X, XI, XII) dan format Daftar Hadir Siswa.
     */
    protected function parseSpreadsheet(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);

        // 1. Periksa apakah dokumen ini memiliki format Daftar Hadir Siswa di salah satu sheet
        $isAttendance = false;
        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $sampleRows = $sheet->toArray(null, true, true, false);
            if ($this->isAttendanceRoster($sampleRows)) {
                $isAttendance = true;
                break;
            }
        }

        if ($isAttendance) {
            return $this->parseAttendanceSpreadsheet($spreadsheet);
        }

        // 2. Jika bukan format presensi, baca semua sheet dan gabungkan baris datanya
        $allRows = [];
        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $sheetRows = $sheet->toArray(null, true, true, false);
            if (!empty($sheetRows)) {
                if (!empty($allRows) && $this->isHeaderRow($sheetRows[0] ?? [])) {
                    $sheetRows = array_slice($sheetRows, 1);
                }
                $allRows = array_merge($allRows, $sheetRows);
            }
        }

        return $allRows;
    }

    /**
     * Mengurai spreadsheet Daftar Hadir Siswa dari seluruh sheet (misal: Sheet X, XI, XII).
     */
    protected function parseAttendanceSpreadsheet(Spreadsheet $spreadsheet): array
    {
        $allStudents = [
            ['No', 'Nama Lengkap', 'Email', 'Username', 'NIP / NIS', 'Role / Peran', 'Kelas', 'Keterangan Tambahan']
        ];

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $rows = $sheet->toArray(null, true, true, false);
            $extracted = $this->extractStudentsFromAttendanceRows($rows);
            foreach ($extracted as $s) {
                $allStudents[] = $s;
            }
        }

        return $allStudents;
    }

    /**
     * Memeriksa apakah baris data merepresentasikan struktur format Daftar Hadir Siswa.
     */
    protected function isAttendanceRoster(array $rows): bool
    {
        $foundTitle = false;
        $foundClass = false;

        foreach ($rows as $row) {
            $joined = implode(' ', array_filter(array_map('strval', (array)$row)));
            if (stripos($joined, 'DAFTAR HADIR SISWA') !== false || stripos($joined, 'PRESENSI SISWA') !== false) {
                $foundTitle = true;
            }
            if (preg_match('/KELAS\s*:\s*[A-Z0-9]/i', $joined)) {
                $foundClass = true;
            }
            if ($foundTitle && $foundClass) {
                return true;
            }
        }

        return false;
    }

    /**
     * Mengekstrak baris-baris siswa dari dokumen format Daftar Hadir Siswa secara berurutan.
     */
    protected function extractStudentsFromAttendanceRows(array $rows): array
    {
        $currentClass = null;
        $students = [];

        foreach ($rows as $row) {
            if (!is_array($row)) continue;

            $joined = implode(' ', array_filter(array_map('strval', $row)));
            $trimmedJoined = trim($joined);
            if ($trimmedJoined === '') continue;

            // Cek jika baris merupakan pergantian header kelas: "KELAS : X RPL 1", "KELAS : XII AK 4", dll.
            if (preg_match('/KELAS\s*:\s*([^,\n\r]+)/i', $joined, $m)) {
                $currentClass = trim($m[1]);
                $currentClass = trim($currentClass, " \t\n\r\0\x0B:.,");
                continue;
            }

            // Cek jika baris adalah baris data siswa:
            // Col 0: No Urut (1, 2, ...)
            // Col 1: NIS (numeric, 4-10 digit)
            // Col 2: Nama Siswa
            // Col 3: Gender (L/P)
            $cells = array_values(array_map(fn($v) => trim((string)$v), $row));

            $colNo = null;
            $colNis = null;
            $colName = null;
            $colGender = null;

            for ($i = 0; $i < min(5, count($cells) - 2); $i++) {
                $v0 = $cells[$i];
                $v1 = $cells[$i + 1] ?? '';
                $v2 = $cells[$i + 2] ?? '';
                $v3 = $cells[$i + 3] ?? '';

                if (is_numeric($v0) && (int)$v0 > 0 && (int)$v0 <= 60 && is_numeric($v1) && strlen($v1) >= 4 && strlen($v1) <= 12 && !empty($v2) && !is_numeric($v2)) {
                    $colNo = $i;
                    $colNis = $i + 1;
                    $colName = $i + 2;
                    $colGender = $i + 3;
                    break;
                }
            }

            if ($colNo !== null) {
                $no = (int)$cells[$colNo];
                $nis = $cells[$colNis];
                $rawName = $cells[$colName];
                $gender = strtoupper($cells[$colGender] ?? '');

                // Bersihkan karakter asteris (*), catatan NIS terakhir, dan spasi berlebih
                $cleanName = trim(preg_replace('/[\*]+$/', '', $rawName));
                $cleanName = trim(preg_replace('/\bNo NIS Terakhir\b/i', '', $cleanName));
                $cleanName = trim(preg_replace('/\s+/', ' ', $cleanName));

                if (!empty($cleanName)) {
                    $students[] = [
                        $no,                        // No
                        $cleanName,                 // Nama Lengkap
                        '',                         // Email (dikosongkan)
                        '',                         // Username (otomatis dari nama depan)
                        $nis,                       // NIP / NIS
                        'Siswa',                    // Role
                        $currentClass ?: 'Siswa',   // Kelas
                        in_array($gender, ['L', 'P']) ? "Gender: {$gender}" : '', // Keterangan Tambahan
                    ];
                }
            }
        }

        return $students;
    }

    /**
     * Memeriksa apakah baris adalah baris header tabel pengguna standar.
     */
    protected function isHeaderRow(array $row): bool
    {
        $joined = strtolower(implode(' ', array_filter(array_map('strval', $row))));
        return (str_contains($joined, 'nama') || str_contains($joined, 'name')) &&
               (str_contains($joined, 'user') || str_contains($joined, 'role') || str_contains($joined, 'peran') || str_contains($joined, 'nip') || str_contains($joined, 'nis'));
    }

    /**
     * Membaca tabel atau konten dari file Word (.docx) menggunakan ZipArchive bawaan PHP.
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
            // Jika dokumen tidak menggunakan tabel formal, coba baca baris paragraf
            $paragraphs = $dom->getElementsByTagName('p');
            foreach ($paragraphs as $p) {
                $text = trim($p->textContent);
                if (empty($text)) continue;

                if (str_contains($text, "\t")) {
                    $rows[] = array_map('trim', explode("\t", $text));
                } elseif (str_contains($text, "|")) {
                    $rows[] = array_map('trim', array_filter(explode("|", $text), fn($val) => trim($val) !== ''));
                } elseif (str_contains($text, ",")) {
                    $rows[] = str_getcsv($text);
                }
            }
        }

        if ($this->isAttendanceRoster($rows)) {
            $students = $this->extractStudentsFromAttendanceRows($rows);
            return array_merge([
                ['No', 'Nama Lengkap', 'Email', 'Username', 'NIP / NIS', 'Role / Peran', 'Kelas', 'Keterangan Tambahan']
            ], $students);
        }

        return $rows;
    }

    /**
     * Membaca teks terstruktur dari file PDF menggunakan smalot/pdfparser.
     */
    protected function parsePdf(string $filePath): array
    {
        $parser = new PdfParser();
        $pdf = $parser->parseFile($filePath);
        $text = $pdf->getText();

        $lines = preg_split('/\r\n|\r|\n/', $text);
        $rows = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // Header/pemisah umum pada PDF
            if (str_contains($line, "\t")) {
                $cols = array_map('trim', explode("\t", $line));
            } elseif (str_contains($line, "|")) {
                $cols = array_map('trim', array_filter(explode("|", $line), fn($val) => trim($val) !== ''));
            } else {
                // Pisahkan spasi ganda (2 atau lebih spasi) sebagai kolom
                $cols = preg_split('/\s{2,}/', $line);
            }

            if (is_array($cols) && count($cols) >= 3) {
                $rows[] = array_values(array_map('trim', $cols));
            }
        }

        if ($this->isAttendanceRoster($rows)) {
            $students = $this->extractStudentsFromAttendanceRows($rows);
            return array_merge([
                ['No', 'Nama Lengkap', 'Email', 'Username', 'NIP / NIS', 'Role / Peran', 'Kelas', 'Keterangan Tambahan']
            ], $students);
        }

        return $rows;
    }

    /**
     * Memproses baris mentah menjadi data pengguna dan menyimpannya ke database.
     */
    protected function processRows(array $rawRows): array
    {
        // Cari baris header untuk menentukan mapping kolom
        $headerIndex = -1;
        $colMap = [
            'no' => null,
            'name_email' => null,
            'name' => null,
            'email' => null,
            'username' => null,
            'role' => null,
            'class' => null,
            'nip_nisn' => null,
            'note' => null,
        ];

        foreach ($rawRows as $idx => $row) {
            if (!is_array($row)) continue;

            $normalizedRow = array_map(fn($v) => strtolower(trim((string)$v)), $row);
            $joined = implode(' ', $normalizedRow);

            // Cek apakah baris ini kemungkinan adalah header
            if (
                (str_contains($joined, 'nama') || str_contains($joined, 'name')) &&
                (str_contains($joined, 'user') || str_contains($joined, 'role') || str_contains($joined, 'peran'))
            ) {
                $headerIndex = $idx;

                foreach ($normalizedRow as $colIdx => $headerText) {
                    if (str_contains($headerText, 'no') && strlen($headerText) <= 4) {
                        $colMap['no'] = $colIdx;
                    } elseif (str_contains($headerText, 'nama') && str_contains($headerText, 'email')) {
                        $colMap['name_email'] = $colIdx;
                    } elseif (str_contains($headerText, 'username') || str_contains($headerText, 'user')) {
                        $colMap['username'] = $colIdx;
                    } elseif (str_contains($headerText, 'email')) {
                        $colMap['email'] = $colIdx;
                    } elseif (str_contains($headerText, 'nama') || str_contains($headerText, 'fullname') || str_contains($headerText, 'full name') || $headerText === 'name') {
                        $colMap['name'] = $colIdx;
                    } elseif (str_contains($headerText, 'role') || str_contains($headerText, 'peran')) {
                        $colMap['role'] = $colIdx;
                    } elseif (str_contains($headerText, 'kelas') || str_contains($headerText, 'class') || str_contains($headerText, 'rombel')) {
                        $colMap['class'] = $colIdx;
                    } elseif (str_contains($headerText, 'nip') || str_contains($headerText, 'nisn') || str_contains($headerText, 'nis')) {
                        $colMap['nip_nisn'] = $colIdx;
                    } elseif (str_contains($headerText, 'keterangan') || str_contains($headerText, 'catatan') || str_contains($headerText, 'note')) {
                        $colMap['note'] = $colIdx;
                    }
                }

                break;
            }
        }

        // Jika header tidak terdeteksi otomatis, gunakan urutan default:
        // Format A (6 kolom): No, Nama Lengkap, Email, Username, Role, Kelas
        // Format B (5 kolom): No, Nama Lengkap & Email, Username, Role, Kelas
        $dataRows = [];
        if ($headerIndex !== -1) {
            $dataRows = array_slice($rawRows, $headerIndex + 1);
        } else {
            // Anggap baris pertama adalah header jika ada teks, sisanya data
            $firstRowText = implode(' ', array_map('strval', $rawRows[0] ?? []));
            if (preg_match('/[a-zA-Z]/', $firstRowText)) {
                $dataRows = array_slice($rawRows, 1);
            } else {
                $dataRows = $rawRows;
            }
        }

        // Urutkan baris data sebelum diproses:
        // 1. Tingkat: Kelas 10 (X) -> Kelas 11 (XI) -> Kelas 12 (XII)
        // 2. Jurusan (A-Z) & Rombel (1, 2, ...)
        // 3. Nama Lengkap (A-Z)
        $dataRows = $this->sortDataRows($dataRows, $colMap);

        // Cache role dan kelas dari database untuk pencocokan cepat
        $roles = Role::all();
        $classes = Classes::all();

        // In-memory lookups untuk validasi keunikan O(1) super cepat tanpa ribuan query DB
        $existingUsersByUsername = [];
        $existingUsersByNip = [];
        $existingUsersByEmail = [];

        foreach (User::all(['id', 'username', 'fullname', 'email', 'nip_nisn', 'role_id', 'class_id', 'parent_id']) as $u) {
            $existingUsersByUsername[strtolower($u->username)] = $u;
            if ($u->nip_nisn) {
                $existingUsersByNip[trim($u->nip_nisn)] = $u;
            }
            if ($u->email) {
                $existingUsersByEmail[strtolower(trim($u->email))] = $u;
            }
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        $defaultPasswordHash = Hash::make('password123');

        foreach ($dataRows as $rowNum => $row) {
            $displayRowNum = $headerIndex !== -1 ? ($headerIndex + 1 + $rowNum + 1) : ($rowNum + 1);

            // Bersihkan baris dari nilai null
            $cells = array_values(array_map(fn($v) => trim((string)$v), $row));
            if (empty(array_filter($cells, fn($c) => $c !== ''))) {
                continue; // Lewati baris kosong
            }

            // Ekstrak data berdasarkan colMap atau fallback posisi
            $rawName = '';
            $rawEmail = '';
            $rawUsername = '';
            $rawRole = '';
            $rawClass = '';
            $rawNipNisn = '';
            $rawNote = '';

            if ($colMap['name_email'] !== null && isset($row[$colMap['name_email']])) {
                $combined = $row[$colMap['name_email']];
                [$rawName, $rawEmail] = $this->splitNameAndEmail($combined);
            } else {
                if ($colMap['name'] !== null && isset($row[$colMap['name']])) {
                    $rawName = $row[$colMap['name']];
                }
                if ($colMap['email'] !== null && isset($row[$colMap['email']])) {
                    $rawEmail = $row[$colMap['email']];
                }
            }

            if ($colMap['username'] !== null && isset($row[$colMap['username']])) {
                $rawUsername = $row[$colMap['username']];
            }
            if ($colMap['role'] !== null && isset($row[$colMap['role']])) {
                $rawRole = $row[$colMap['role']];
            }
            if ($colMap['class'] !== null && isset($row[$colMap['class']])) {
                $rawClass = $row[$colMap['class']];
            }
            if ($colMap['nip_nisn'] !== null && isset($row[$colMap['nip_nisn']])) {
                $rawNipNisn = $row[$colMap['nip_nisn']];
            }
            if ($colMap['note'] !== null && isset($row[$colMap['note']])) {
                $rawNote = $row[$colMap['note']];
            }

            // Fallback jika mapping kolom tidak lengkap
            if (empty($rawName) && empty($rawUsername)) {
                $colCount = count($cells);
                if ($colCount >= 8) {
                    // Format: No, Nama, Email, Username, NIP/NISN, Role, Kelas, Keterangan
                    $rawName = $cells[1] ?? '';
                    $rawEmail = $cells[2] ?? '';
                    $rawUsername = $cells[3] ?? '';
                    $rawNipNisn = $cells[4] ?? '';
                    $rawRole = $cells[5] ?? '';
                    $rawClass = $cells[6] ?? '';
                    $rawNote = $cells[7] ?? '';
                } elseif ($colCount >= 7) {
                    // Format: No, Nama, Email, Username, NIP/NISN, Role, Kelas
                    $rawName = $cells[1] ?? '';
                    $rawEmail = $cells[2] ?? '';
                    $rawUsername = $cells[3] ?? '';
                    $rawNipNisn = $cells[4] ?? '';
                    $rawRole = $cells[5] ?? '';
                    $rawClass = $cells[6] ?? '';
                } elseif ($colCount >= 6) {
                    // Format: No, Nama, Email, Username, Role, Kelas
                    $rawName = $cells[1] ?? '';
                    $rawEmail = $cells[2] ?? '';
                    $rawUsername = $cells[3] ?? '';
                    $rawRole = $cells[4] ?? '';
                    $rawClass = $cells[5] ?? '';
                } elseif ($colCount >= 5) {
                    // Format: No, Nama&Email, Username, Role, Kelas
                    [$rawName, $rawEmail] = $this->splitNameAndEmail($cells[1] ?? '');
                    $rawUsername = $cells[2] ?? '';
                    $rawRole = $cells[3] ?? '';
                    $rawClass = $cells[4] ?? '';
                } elseif ($colCount >= 4) {
                    // Format: Nama, Username, Role, Kelas
                    $rawName = $cells[0] ?? '';
                    $rawUsername = $cells[1] ?? '';
                    $rawRole = $cells[2] ?? '';
                    $rawClass = $cells[3] ?? '';
                }
            }

            // Validasi nama
            $rawName = trim($rawName);
            if (empty($rawName)) {
                // Abaikan baris kosong atau baris judul/catatan tanpa nama
                if (empty($rawUsername) && empty($rawEmail) && empty($rawNipNisn)) {
                    continue;
                }
                $errors[] = "Baris #{$displayRowNum}: Nama lengkap kosong, dilewati.";
                $skipped++;
                continue;
            }

            // Jika dalam rawName masih ada email, pisahkan
            if (empty($rawEmail)) {
                [$cleanName, $extractedEmail] = $this->splitNameAndEmail($rawName);
                $rawName = $cleanName;
                if (!empty($extractedEmail)) {
                    $rawEmail = $extractedEmail;
                }
            }

            // 1. Validasi & Normalisasi Email (kosongkan jika tidak ada di file)
            $email = null;
            $cleanEmail = trim(strtolower($rawEmail));
            if (!empty($cleanEmail) && !in_array($cleanEmail, ['-', 'tidak ada', 'null', 'none'])) {
                if (filter_var($cleanEmail, FILTER_VALIDATE_EMAIL)) {
                    $email = $cleanEmail;
                }
            }

            // 2. Validasi & Normalisasi NIP / NIS (kosongkan jika tidak ada di file)
            $nipNisn = null;
            $cleanNip = trim($rawNipNisn);
            if (!empty($cleanNip) && !in_array(strtolower($cleanNip), ['-', 'tidak ada', 'null', 'none'])) {
                $nipNisn = $cleanNip;
            }

            // 3. Normalisasi Username:
            // Sesuai aturan: nama_pertama_nama_kedua
            // Jika 2 nama depan sama, otomatis gunakan nama ke-3: nama_pertama_nama_kedua_nama_ketiga
            $rawUserTrim = trim($rawUsername);
            $existingUser = null;

            $useAutoUsername = empty($rawUserTrim) ||
                str_contains($rawUserTrim, ' ') ||
                is_numeric($rawUserTrim) ||
                preg_match('/[,\.]/', $rawUserTrim) ||
                strlen($rawUserTrim) > 25;

            if (!$useAutoUsername) {
                $username = strtolower($rawUserTrim);
                $existingUser = $existingUsersByUsername[$username] ?? null;
            } else {
                $username = $this->generateUsername($rawName, $existingUsersByUsername);
            }

            // Jika belum ketemu user by username, cari by NIP/NISN atau Email jika ada
            if (!$existingUser && $nipNisn !== null && isset($existingUsersByNip[$nipNisn])) {
                $existingUser = $existingUsersByNip[$nipNisn];
            }
            if (!$existingUser && !empty($email) && isset($existingUsersByEmail[$email])) {
                $existingUser = $existingUsersByEmail[$email];
            }

            // Tentukan Class ID terlebih dahulu jika berlaku
            $classId = $this->resolveClassId($rawClass, $classes);

            // Tentukan Role ID cerdas
            $roleId = $this->resolveRoleId($rawRole, $roles, $rawNote, $classId);

            try {
                // Cek konflik email
                if ($email !== null && isset($existingUsersByEmail[$email])) {
                    $conflicting = $existingUsersByEmail[$email];
                    if (!$existingUser || $conflicting->id !== $existingUser->id) {
                        $errors[] = "Baris #{$displayRowNum} ({$rawName}): Email '{$email}' sudah terdaftar pada akun lain. Akun disimpan tanpa email.";
                        $email = null;
                    }
                }

                // Cek konflik NIP/NIS
                if ($nipNisn !== null && isset($existingUsersByNip[$nipNisn])) {
                    $conflicting = $existingUsersByNip[$nipNisn];
                    if (!$existingUser || $conflicting->id !== $existingUser->id) {
                        $errors[] = "Baris #{$displayRowNum} ({$rawName}): NIP/NIS '{$nipNisn}' sudah digunakan akun lain. Akun disimpan tanpa NIP/NIS.";
                        $nipNisn = null;
                    }
                }

                if ($existingUser) {
                    // Update user yang sudah ada
                    $existingUser->fullname = $rawName;
                    $existingUser->role_id = $roleId;
                    $existingUser->email = $email; // Dikosongkan jika tidak ada di file
                    $existingUser->nip_nisn = $nipNisn; // Dikosongkan jika tidak ada di file
                    if ($classId !== null) {
                        $existingUser->class_id = $classId;
                    }
                    $existingUser->save();

                    // Update lookups
                    $existingUsersByUsername[strtolower($existingUser->username)] = $existingUser;
                    if ($nipNisn) $existingUsersByNip[$nipNisn] = $existingUser;
                    if ($email) $existingUsersByEmail[$email] = $existingUser;

                    // Jika siswa, otomatis buat/sinkronkan akun Orang Tua terpisah (P + NIS)
                    if ($roleId == 3) {
                        $studentNis = !empty($existingUser->nip_nisn) ? $existingUser->nip_nisn : $existingUser->username;
                        if (!empty($studentNis)) {
                            $parentUsername = 'P' . $studentNis;
                            $parentUser = $existingUsersByUsername[strtolower($parentUsername)] ?? null;
                            if (!$parentUser) {
                                $parentUser = User::create([
                                    'fullname'  => 'Orang Tua ' . $existingUser->fullname,
                                    'username'  => $parentUsername,
                                    'email'     => null,
                                    'nip_nisn'  => null,
                                    'password'  => $defaultPasswordHash,
                                    'role_id'   => 4, // OrangTua
                                    'parent_id' => $existingUser->id,
                                    'class_id'  => $classId,
                                ]);
                                $existingUsersByUsername[strtolower($parentUsername)] = $parentUser;
                            } else {
                                $parentUser->update([
                                    'parent_id' => $existingUser->id,
                                    'class_id'  => $classId,
                                ]);
                            }
                            $existingUser->update(['parent_id' => $parentUser->id]);
                        }
                    }

                    $updated++;
                } else {
                    // Buat akun baru
                    $newUser = User::create([
                        'fullname'  => $rawName,
                        'username'  => $username,
                        'email'     => $email,
                        'password'  => $defaultPasswordHash,
                        'role_id'   => $roleId,
                        'nip_nisn'  => $nipNisn,
                        'class_id'  => in_array($roleId, [2, 3, 5, 6]) ? $classId : null,
                    ]);

                    $existingUsersByUsername[strtolower($username)] = $newUser;
                    if ($nipNisn) $existingUsersByNip[$nipNisn] = $newUser;
                    if ($email) $existingUsersByEmail[$email] = $newUser;

                    // Jika siswa, otomatis buat akun Orang Tua terpisah (P + NIS)
                    if ($roleId == 3) {
                        $studentNis = !empty($nipNisn) ? $nipNisn : $username;
                        if (!empty($studentNis)) {
                            $parentUsername = 'P' . $studentNis;
                            $parentUser = $existingUsersByUsername[strtolower($parentUsername)] ?? null;
                            if (!$parentUser) {
                                $parentUser = User::create([
                                    'fullname'  => 'Orang Tua ' . $rawName,
                                    'username'  => $parentUsername,
                                    'email'     => null,
                                    'nip_nisn'  => null,
                                    'password'  => $defaultPasswordHash,
                                    'role_id'   => 4, // OrangTua
                                    'parent_id' => $newUser->id,
                                    'class_id'  => $classId,
                                ]);
                                $existingUsersByUsername[strtolower($parentUsername)] = $parentUser;
                            } else {
                                $parentUser->update([
                                    'parent_id' => $newUser->id,
                                    'class_id'  => $classId,
                                ]);
                            }
                            $newUser->update(['parent_id' => $parentUser->id]);
                        }
                    }

                    $created++;
                }
            } catch (\Throwable $e) {
                $errors[] = "Baris #{$displayRowNum} ({$rawName}): Terjadi kesalahan - " . $e->getMessage();
                $skipped++;
            }
        }

        return [
            'total_rows' => count($dataRows),
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    /**
     * Memisahkan nama dan email jika digabung dalam satu string.
     */
    protected function splitNameAndEmail(string $str): array
    {
        $str = trim($str);
        if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $str, $matches)) {
            $email = $matches[0];
            // Hapus email dan kurung dari nama
            $name = trim(preg_replace('/[\(\[\<]?\s*' . preg_quote($email, '/') . '\s*[\)\]\>]?/', '', $str));
            $name = trim($name, " \t\n\r\0\x0B-:,");
            return [$name, $email];
        }
        return [$str, ''];
    }

    /**
     * Menentukan role_id berdasarkan nama/teks peran, catatan keterangan, dan kelas binaan.
     */
    protected function resolveRoleId(string $roleText, $roles, string $noteText = '', ?int $classId = null): int
    {
        $clean = strtolower(trim($roleText));
        $cleanNote = strtolower(trim($noteText));

        // 1. Waka / Admin
        if (
            str_contains($clean, 'waka') || str_contains($clean, 'admin') || str_contains($clean, 'kurikulum') ||
            str_contains($cleanNote, 'waka') || str_contains($cleanNote, 'admin')
        ) {
            return 1; // Admin / Waka
        }

        // 2. Wali Kelas
        // Otomatis terdeteksi jika:
        // - Kolom peran berisi 'walas', 'wali kelas', atau 'wali'
        // - Kolom keterangan tambahan berisi 'walas', 'wali kelas', atau 'wali'
        // - Kolom peran adalah Guru (atau kosong) dan kolom Kelas diisi kelas binaan
        if (
            str_contains($clean, 'walas') || str_contains($clean, 'wali kelas') || str_contains($clean, 'wali') ||
            str_contains($cleanNote, 'walas') || str_contains($cleanNote, 'wali kelas') || str_contains($cleanNote, 'wali') ||
            ((empty($clean) || str_contains($clean, 'guru') || str_contains($clean, 'pengajar')) && $classId !== null)
        ) {
            return 6; // Wali Kelas
        }

        // 3. BK
        if (str_contains($clean, 'bk') || str_contains($clean, 'konseling') || str_contains($clean, 'bimbingan')) {
            return 5; // BK
        }

        // 4. Orang Tua (jika ada input khusus)
        if (str_contains($clean, 'ortu') || str_contains($clean, 'orang tua') || str_contains($clean, 'orangtua') || str_contains($clean, 'wali murid')) {
            return 4; // Orang Tua
        }

        // 5. Siswa
        if (str_contains($clean, 'siswa') || str_contains($clean, 'murid') || str_contains($clean, 'pelajar') || str_contains($clean, 'santri')) {
            return 3; // Siswa
        }

        // 6. Guru Mapel Murni
        if (str_contains($clean, 'guru') || str_contains($clean, 'pengajar') || str_contains($clean, 'pendidik')) {
            return 2; // Guru
        }

        // Cari exact match pada nama role di database
        foreach ($roles as $r) {
            if (strcasecmp($r->role_name, $clean) === 0) {
                return $r->id;
            }
        }

        return 2; // Default jika tidak ada yang cocok: Guru
    }

    /**
     * Mengekstrak HANYA nama depan untuk username.
     * Membersihkan nomor urut di awal, gelar kehormatan, gelar kebangsawanan,
     * serta seluruh gelar akademik depan dan belakang.
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
            'raden rara', 'raden mas', 'raden', 'rara', 'dr.', 'dra.', 'drs.', 'prof.',
            'h.', 'hj.', 'ir.', 'gr.', 's.pd.', 's.pd', 'm.pd.', 'm.pd', 's.kom.', 's.kom',
            'm.kom.', 'm.kom', 's.t.', 's.t', 'm.t.', 'm.t', 's.ag.', 's.ag', 'm.ag.', 'm.ag',
            's.si.', 's.si', 'm.si.', 'm.si', 's.sn.', 's.sn', 'm.sn.', 'm.sn', 's.ip.', 's.ip',
            'm.ip.', 'm.ip', 's.sos.', 's.sos', 's.s.', 's.s', 'm.eng.', 'm.eng', 'm.s.i.', 'm.s.i',
            'm.acc.', 'm.acc', 'mm.', 'mm', 'b.a.', 'b.a', 'ph.d.', 'ph.d'
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
        return substr($fallback, 0, 15) ?: 'user';
    }

    /**
     * Menghasilkan username sesuai aturan SCP:
     * 1. Nama pertama_nama kedua (contoh: ADELIA PUTRI MAHARANI -> adelia_putri)
     * 2. Jika 2 nama di depan sama (sudah terdaftar), gunakan nama ke-3: nama pertama_nama kedua_nama ketiga (contoh: ADELIA PUTRI SARI -> adelia_putri_sari)
     * 3. Jika hanya 1 kata nama: nama_pertama (contoh: SUNARSIH -> sunarsih)
     * 4. Gelar akademik, kebangsawanan, dan nomor urut dibersihkan.
     * 5. Jika masih bentrok, tambahkan angka pembeda (contoh: adelia_putri_sari1).
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
            'raden rara', 'raden mas', 'raden', 'rara', 'dr.', 'dra.', 'drs.', 'prof.',
            'h.', 'hj.', 'ir.', 'gr.', 's.pd.', 's.pd', 'm.pd.', 'm.pd', 's.kom.', 's.kom',
            'm.kom.', 'm.kom', 's.t.', 's.t', 'm.t.', 'm.t', 's.ag.', 's.ag', 'm.ag.', 'm.ag',
            's.si.', 's.si', 'm.si.', 'm.si', 's.sn.', 's.sn', 'm.sn.', 'm.sn', 's.ip.', 's.ip',
            'm.ip.', 'm.ip', 's.sos.', 's.sos', 's.s.', 's.s', 'm.eng.', 'm.eng', 'm.s.i.', 'm.s.i',
            'm.acc.', 'm.acc', 'mm.', 'mm', 'b.a.', 'b.a', 'ph.d.', 'ph.d'
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
            $base = substr($fallback, 0, 15) ?: 'user';
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
     * Menentukan class_id berdasarkan nama kelas.
     * Mendukung normalisasi rombel (contoh: AK -> AKL, XII LPS -> XII LPS 1, X BD -> X BD 1)
     * dan otomatis membuat kelas baru jika belum terdaftar di sistem.
     */
    protected function resolveClassId(string $classText, &$classes): ?int
    {
        $clean = strtolower(trim($classText));
        if (empty($clean) || $clean === '-' || $clean === 'tidak ada' || $clean === 'none') {
            return null;
        }

        $cleanNorm = str_replace([' ', '-', '_'], '', $clean);

        // 1. Pencocokan langsung atau tanpa spasi
        foreach ($classes as $c) {
            $cName = strtolower(trim($c->class_name));
            $cNorm = str_replace([' ', '-', '_'], '', $cName);
            if ($cName === $clean || $cNorm === $cleanNorm) {
                return $c->id;
            }
        }

        // 2. Pencocokan jurusan AK -> AKL (contoh: X AK 1 -> X AKL 1)
        if (preg_match('/(x|xi|xii)ak(\d+)/i', $cleanNorm, $m)) {
            $aklNorm = $m[1] . 'akl' . $m[2];
            foreach ($classes as $c) {
                $cNorm = str_replace([' ', '-', '_'], '', strtolower($c->class_name));
                if ($cNorm === $aklNorm) return $c->id;
            }
        }

        // 3. Pencocokan rombel tunggal tanpa nomor rombel (contoh: XII LPS -> XII LPS 1, X BD -> X BD 1)
        if (preg_match('/^(x|xi|xii)(lps|bd)$/i', $cleanNorm, $m)) {
            $candNorm = $m[1] . $m[2] . '1';
            foreach ($classes as $c) {
                $cNorm = str_replace([' ', '-', '_'], '', strtolower($c->class_name));
                if ($cNorm === $candNorm) return $c->id;
            }
        }

        // 4. Substring / prefix match fleksibel
        foreach ($classes as $c) {
            $cNorm = str_replace([' ', '-', '_'], '', strtolower($c->class_name));
            if (str_starts_with($cNorm, $cleanNorm) || str_starts_with($cleanNorm, $cNorm)) {
                return $c->id;
            }
        }

        // 5. Otomatis buat kelas baru jika belum ada di database
        $grade = 'X';
        if (str_starts_with(strtoupper($classText), 'XII') || str_starts_with($classText, '12')) $grade = 'XII';
        elseif (str_starts_with(strtoupper($classText), 'XI') || str_starts_with($classText, '11')) $grade = 'XI';

        $major = trim(preg_replace('/^(X|XI|XII|10|11|12)\s*/i', '', $classText));
        $newClass = Classes::create([
            'class_name' => $classText,
            'grade'      => $grade,
            'major'      => $major ?: 'Umum',
        ]);

        if (is_object($classes) && method_exists($classes, 'push')) {
            $classes->push($newClass);
        }

        return $newClass->id;
    }

    /**
     * Mengurutkan baris data sebelum diproses:
     * 1. Tingkat Kelas: Kelas 10 (X) -> Kelas 11 (XI) -> Kelas 12 (XII)
     * 2. Jurusan (A-Z) & Rombel (1, 2, 3...)
     * 3. Nama Lengkap (A-Z)
     */
    protected function sortDataRows(array $dataRows, array $colMap): array
    {
        $gradeWeight = function (string $c) {
            $upper = strtoupper(trim($c));
            if (str_starts_with($upper, 'X ') || str_starts_with($upper, '10 ') || $upper === 'X' || $upper === '10') return 1;
            if (str_starts_with($upper, 'XI ') || str_starts_with($upper, '11 ') || $upper === 'XI' || $upper === '11') return 2;
            if (str_starts_with($upper, 'XII ') || str_starts_with($upper, '12 ') || $upper === 'XII' || $upper === '12') return 3;
            return 4;
        };

        usort($dataRows, function ($a, $b) use ($colMap, $gradeWeight) {
            $cellsA = array_values(array_map('strval', (array)$a));
            $cellsB = array_values(array_map('strval', (array)$b));

            $classA = '';
            $classB = '';
            if ($colMap['class'] !== null && isset($a[$colMap['class']], $b[$colMap['class']])) {
                $classA = trim((string)$a[$colMap['class']]);
                $classB = trim((string)$b[$colMap['class']]);
            } else {
                $classA = trim($cellsA[6] ?? '');
                $classB = trim($cellsB[6] ?? '');
            }

            $nameA = '';
            $nameB = '';
            if ($colMap['name'] !== null && isset($a[$colMap['name']], $b[$colMap['name']])) {
                $nameA = trim((string)$a[$colMap['name']]);
                $nameB = trim((string)$b[$colMap['name']]);
            } else {
                $nameA = trim($cellsA[1] ?? '');
                $nameB = trim($cellsB[1] ?? '');
            }

            // 1. Bandingkan Tingkat Kelas (10 -> 11 -> 12)
            $gwA = $gradeWeight($classA);
            $gwB = $gradeWeight($classB);
            if ($gwA !== $gwB) {
                return $gwA <=> $gwB;
            }

            // 2. Bandingkan Jurusan & Rombel (A-Z)
            $subA = trim(preg_replace('/^(X|XI|XII|10|11|12)\s*/i', '', $classA));
            $subB = trim(preg_replace('/^(X|XI|XII|10|11|12)\s*/i', '', $classB));
            $cmpClass = strcasecmp($subA, $subB);
            if ($cmpClass !== 0) {
                return $cmpClass;
            }

            // 3. Bandingkan Nama Lengkap (A-Z)
            return strcasecmp($nameA, $nameB);
        });

        return $dataRows;
    }

    /**
     * Menghasilkan file Excel (.xlsx) Template Pengguna yang siap diunduh.
     */
    public function generateTemplateSpreadsheet(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('Sistem School Class Progress (SCP)')
            ->setTitle('Template Import Pengguna')
            ->setSubject('Template Data Pengguna SCP')
            ->setDescription('Template resmi untuk import akun pengguna (Guru, Walas, BK, Siswa, Orang Tua) pada sistem SCP.');

        // Sheet 1: Template Data Pengguna
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Pengguna');

        // Header Judul & Instruksi
        $sheet->setCellValue('A1', 'TEMPLATE IMPORT DATA PENGGUNA - SISTEM SCHOOL CLASS PROGRESS (SCP)');
        $sheet->setCellValue('A2', 'Petunjuk: Isi data pengguna sesuai kolom di bawah ini. Untuk Siswa, isi kolom NIP / NIS dengan NIS siswa (4-6 digit). Akun Orang Tua otomatis dibuatkan terpisah dan terhubung ke siswa dengan username "P" + NIS (contoh: NIS 5412 -> akun ortu P5412). Guru yang menjadi Wali Kelas otomatis terdeteksi jika mengisi kolom Kelas atau mencantumkan Wali Kelas di Keterangan Tambahan. Password awal: "password123".');
        
        $sheet->mergeCells('A1:H1');
        $sheet->mergeCells('A2:H2');

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13)->getColor()->setRGB('1E3A8A');
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(9)->getColor()->setRGB('4B5563');

        // Baris Header Kolom Tabel (Baris 4)
        $headers = [
            'A4' => 'No',
            'B4' => 'Nama Lengkap',
            'C4' => 'Email',
            'D4' => 'Username',
            'E4' => 'NIP / NIS',
            'F4' => 'Role / Peran',
            'G4' => 'Kelas',
            'H4' => 'Keterangan Tambahan',
        ];

        foreach ($headers as $cell => $text) {
            $sheet->setCellValue($cell, $text);
        }

        // Styling Baris Header Tabel
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

        // Contoh Data 1-per-1 Sesuai Role & Keterangan Khusus
        $sampleData = [
            [1, 'Budi Santoso, S.Pd.', 'budi.guru@sekolah.sch.id', 'budi_santoso', '198501012010011001', 'Guru Mapel', '-', 'Guru Mata Pelajaran (Pengajar Murni)'],
            [2, 'ROSYIDA RAMADHANI, S.Pd', 'rosyida.guru@sekolah.sch.id', 'rosyida_ramadhani', '198804152015022001', 'Guru Mapel', 'XII RPL 2', 'Wali Kelas XII RPL 2 (Otomatis terdeteksi Walas)'],
            [3, 'Ahmad Fauzi, S.Pd.', 'ahmad.bk@sekolah.sch.id', 'ahmad_bk', '198012202009011003', 'BK', '-', 'Guru BK (Bimbingan Konseling Murni / Tidak Mengajar)'],
            [4, 'Nurul Hidayah, S.Pd.', 'nurul.bk@sekolah.sch.id', 'nurul_bk', '198305102008012004', 'BK', 'X RPL 1', 'Guru BK (Mengajar Kelas X RPL 1)'],
            [5, 'Muhammad Rizky', 'rizky@siswa.sch.id', '5412', '5412', 'Siswa', 'XII RPL 1', 'Akun Orang Tua otomatis dibuat terpisah dengan username P5412'],
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

            // Alignment
            $sheet->getStyle("A{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("C{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("D{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("E{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("F{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("G{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("H{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

            // Border
            $sheet->getStyle("A{$rowNum}:H{$rowNum}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E5E7EB');
            $sheet->getRowDimension($rowNum)->setRowHeight(24);
            $rowNum++;
        }

        // Auto size columns
        foreach (['A' => 6, 'B' => 28, 'C' => 30, 'D' => 20, 'E' => 24, 'F' => 18, 'G' => 16, 'H' => 48] as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        // Sheet 2: Panduan & Data Referensi
        $refSheet = $spreadsheet->createSheet();
        $refSheet->setTitle('Panduan & Referensi');

        $refSheet->setCellValue('A1', 'PANDUAN ROLE / PERAN YANG DIDUKUNG');
        $refSheet->mergeCells('A1:C1');
        $refSheet->getStyle('A1')->getFont()->setBold(true)->setSize(11)->getColor()->setRGB('1E3A8A');

        $roleHeaders = ['A3' => 'Nama Role di Template', 'B3' => 'Role Sistem', 'C3' => 'Penjelasan'];
        foreach ($roleHeaders as $cell => $text) {
            $refSheet->setCellValue($cell, $text);
        }
        $refSheet->getStyle('A3:C3')->applyFromArray($headerStyle);

        $rolesData = [
            ['Guru Mapel', 'Guru (Role ID: 2)', 'Pengajar mata pelajaran murni. Kolom Kelas diisi "-".'],
            ['Wali Kelas / Walas', 'WaliKelas (Role ID: 6)', 'Guru pengajar yang merangkap Wali Kelas (contoh: Bu Rosyida). Kolom Kelas diisi kelas bimbingannya (contoh: XII RPL 2). Otomatis terdeteksi walas jika kolom Kelas diisi atau Keterangan Tambahan tertulis Wali Kelas.'],
            ['BK (Konseling)', 'BK (Role ID: 5)', 'Guru Bimbingan Konseling murni (tidak mengajar di kelas). Kolom Kelas diisi "-".'],
            ['BK (Mengajar)', 'BK (Role ID: 5)', 'Guru BK yang juga mengajar materi/BK di suatu kelas. Kolom Kelas diisi kelas yang diajar (contoh: X RPL 1).'],
            ['Siswa', 'Siswa (Role ID: 3)', 'Peserta didik aktif. Kolom NIP / NIS diisi NIS siswa (4-6 digit). Akun Orang Tua otomatis dibuat terpisah dengan username "P" + NIS (contoh: P5412).'],
            ['Orang Tua', 'OrangTua (Role ID: 4)', 'Akun orang tua murid. Otomatis dibuatkan terpisah dari NIS siswa (username "P" + NIS, password: password123).'],
            ['Waka (Admin)', 'Admin (Role ID: 1)', 'Waka Kurikulum / Administrator sistem.'],
        ];

        $rIndex = 4;
        foreach ($rolesData as $rd) {
            $refSheet->setCellValue("A{$rIndex}", $rd[0]);
            $refSheet->setCellValue("B{$rIndex}", $rd[1]);
            $refSheet->setCellValue("C{$rIndex}", $rd[2]);
            $refSheet->getStyle("A{$rIndex}:C{$rIndex}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E5E7EB');
            $rIndex++;
        }

        // Daftar Kelas Aktif di Sistem
        $classStartRow = $rIndex + 2;
        $refSheet->setCellValue("A{$classStartRow}", 'DAFTAR KELAS AKTIF SAAT INI DI SISTEM');
        $refSheet->mergeCells("A{$classStartRow}:C{$classStartRow}");
        $refSheet->getStyle("A{$classStartRow}")->getFont()->setBold(true)->setSize(11)->getColor()->setRGB('1E3A8A');

        $classHeaderRow = $classStartRow + 2;
        $refSheet->setCellValue("A{$classHeaderRow}", 'ID Kelas');
        $refSheet->setCellValue("B{$classHeaderRow}", 'Nama Kelas (Ketik persis di Template)');
        $refSheet->setCellValue("C{$classHeaderRow}", 'Status');
        $refSheet->getStyle("A{$classHeaderRow}:C{$classHeaderRow}")->applyFromArray($headerStyle);

        $classes = Classes::orderBy('class_name')->get();
        $cIndex = $classHeaderRow + 1;
        foreach ($classes as $c) {
            $refSheet->setCellValue("A{$cIndex}", $c->id);
            $refSheet->setCellValue("B{$cIndex}", $c->class_name);
            $refSheet->setCellValue("C{$cIndex}", 'Aktif');
            $refSheet->getStyle("A{$cIndex}:C{$cIndex}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E5E7EB');
            $cIndex++;
        }

        $refSheet->getColumnDimension('A')->setWidth(25);
        $refSheet->getColumnDimension('B')->setWidth(35);
        $refSheet->getColumnDimension('C')->setWidth(45);

        // Set active sheet kembali ke Sheet 1
        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }
}

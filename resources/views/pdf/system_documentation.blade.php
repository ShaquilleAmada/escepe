<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dokumentasi Lengkap Sistem SCP - SMK Negeri 1 Bantul</title>
    <style>
        @page {
            margin: 1.8cm 1.5cm 2cm 1.5cm;
            size: A4 portrait;
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            font-size: 10pt;
            line-height: 1.5;
        }

        /* Cover Page */
        .cover-page {
            page-break-after: always;
            text-align: center;
            padding-top: 5cm;
        }
        .cover-badge {
            display: inline-block;
            background-color: #0f172a;
            color: #ffffff;
            font-size: 9pt;
            font-weight: bold;
            padding: 5px 14px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 20px;
        }
        .cover-title {
            font-size: 24pt;
            font-weight: 800;
            color: #0f2537;
            margin: 0 0 10px 0;
            line-height: 1.2;
        }
        .cover-subtitle {
            font-size: 12pt;
            color: #475569;
            margin-bottom: 40px;
            font-weight: 500;
        }
        .cover-school {
            font-size: 14pt;
            font-weight: bold;
            color: #1e3a8a;
            border-top: 2px solid #e2e8f0;
            border-bottom: 2px solid #e2e8f0;
            padding: 15px 0;
            margin: 40px 30px;
        }
        .cover-meta {
            font-size: 9.5pt;
            color: #64748b;
            margin-top: 50px;
        }

        /* Headings */
        h1 {
            font-size: 16pt;
            color: #0f2537;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 5px;
            margin-top: 25px;
            margin-bottom: 12px;
            page-break-after: avoid;
        }
        h2 {
            font-size: 12.5pt;
            color: #1e3a8a;
            margin-top: 18px;
            margin-bottom: 8px;
            page-break-after: avoid;
        }
        h3 {
            font-size: 10.5pt;
            color: #334155;
            margin-top: 12px;
            margin-bottom: 6px;
            page-break-after: avoid;
        }

        p {
            margin: 0 0 8px 0;
            text-align: justify;
        }

        /* Tables */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0 16px 0;
            font-size: 9pt;
            page-break-inside: avoid;
        }
        table, th, td {
            border: 1px solid #cbd5e1;
        }
        th {
            background-color: #f1f5f9;
            color: #0f172a;
            font-weight: 700;
            padding: 6px 8px;
            text-align: left;
        }
        td {
            padding: 5px 8px;
            vertical-align: top;
        }
        tr:nth-child(even) td {
            background-color: #f8fafc;
        }

        /* Callout Boxes */
        .callout {
            background-color: #f0fdf4;
            border-left: 4px solid #16a34a;
            padding: 10px 12px;
            margin: 10px 0;
            border-radius: 4px;
            font-size: 9pt;
        }
        .callout-info {
            background-color: #eff6ff;
            border-left: 4px solid #2563eb;
            padding: 10px 12px;
            margin: 10px 0;
            border-radius: 4px;
            font-size: 9pt;
        }
        .callout-warning {
            background-color: #fffbeb;
            border-left: 4px solid #d97706;
            padding: 10px 12px;
            margin: 10px 0;
            border-radius: 4px;
            font-size: 9pt;
        }

        /* Code Blocks */
        pre {
            background-color: #0f172a;
            color: #e2e8f0;
            padding: 10px 12px;
            border-radius: 6px;
            font-family: 'Courier New', Courier, monospace;
            font-size: 8.5pt;
            overflow: hidden;
            margin: 8px 0 12px 0;
            line-height: 1.4;
            page-break-inside: avoid;
        }
        code {
            background-color: #f1f5f9;
            color: #0f2537;
            padding: 2px 5px;
            border-radius: 4px;
            font-family: 'Courier New', Courier, monospace;
            font-size: 8.5pt;
        }
        pre code {
            background-color: transparent;
            color: inherit;
            padding: 0;
        }

        .page-break {
            page-break-after: always;
        }

        /* Lists */
        ul, ol {
            margin: 0 0 10px 18px;
            padding: 0;
        }
        li {
            margin-bottom: 4px;
        }

        /* Badges */
        .badge {
            display: inline-block;
            font-size: 7.5pt;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 4px;
        }
        .badge-primary { background: #dbeafe; color: #1e40af; }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger  { background: #fee2e2; color: #991b1b; }

        /* Footer */
        .footer {
            position: fixed;
            bottom: -1cm;
            left: 0;
            right: 0;
            height: 0.8cm;
            font-size: 8pt;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 4px;
            text-align: right;
        }
    </style>
</head>
<body>

    <!-- HALAMAN COVER -->
    <div class="cover-page">
        <div class="cover-badge">Buku Panduan & Dokumentasi Teknis</div>
        <div class="cover-title">SISTEM SCP (SCHOOL CLASS PROGRESS)</div>
        <div class="cover-subtitle">Sistem Informasi Manajemen KBM Blok, Monitoring Presensi Real-Time, & Otomasi Perizinan Siswa</div>

        <div class="cover-school">
            SMK NEGERI 1 BANTUL<br>
            <span style="font-size: 11pt; font-weight: normal; color: #475569;">Tahun Ajaran 2026/2027 • Rubrik UKK RPL Paket 3 Terintegrasi</span>
        </div>

        <div class="cover-meta">
            <strong>Disusun untuk:</strong> Bahan Belajar Mandiri, Bedah Kode, & Pengoperasian Sistem<br>
            <strong>Teknologi:</strong> Laravel 11 • REST API Sanctum • Flutter Client • MySQL Database<br>
            <strong>Tanggal Terbit:</strong> {{ date('d F Y') }} • Versi 1.0.0 Resmi
        </div>
    </div>

    <!-- BAB 1: IKHTISAR SISTEM & ARSITEKTUR -->
    <h1>BAB 1: IKHTISAR SISTEM & ARSITEKTUR</h1>

    <h2>1.1 Latar Belakang & Masalah yang Diselesaikan</h2>
    <p>
        <strong>Sistem SCP (School Class Progress)</strong> dikembangkan khusus untuk menjawab tantangan pembelajaran sistem blok di SMK Negeri 1 Bantul. Dalam kurikulum vokasi, jadwal pembelajaran dibagi menjadi siklus putaran (Putaran 1 & Putaran 2) yang bergantian setiap pekan antara mapel kejuruan dan mapel umum.
    </p>
    <p>
        Tantangan utama yang diselesaikan oleh sistem ini meliputi:
    </p>
    <ul>
        <li><strong>Sinkronisasi Presensi Otomatis (CRIT-06):</strong> Surat izin atau sakit yang disetujui otomatis mengunci data presensi pada seluruh jam KBM sesi pelajaran guru pada hari yang sama.</li>
        <li><strong>Bypass Dinamis Wali Kelas & Guru BK:</strong> Jika Wali Kelas berhalangan hadir atau melewati batas 30 menit awal KBM, verifikasi perizinan siswa otomatis dialihkan ke Guru Bimbingan Konseling (BK) rombel binaan terkait.</li>
        <li><strong>Monitoring Guru & Jurnal Mengajar:</strong> Waka Kurikulum dapat memantau kehadiran guru, jurnal KBM, dan jam kosong secara langsung (*real-time*).</li>
        <li><strong>Perhitungan Hari Efektif Otomatis:</strong> Rekapitulasi presensi bulanan menghitung hari aktif sekolah (exclude Sabtu, Minggu, & hari libur) dengan rumus tingkat kehadiran akurat berstandar dinas.</li>
    </ul>

    <h2>1.2 Tech Stack & Arsitektur Aplikasi</h2>
    <table>
        <thead>
            <tr>
                <th style="width: 25%;">Komponen</th>
                <th style="width: 35%;">Teknologi / Pustaka</th>
                <th style="width: 40%;">Fungsi Utama</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Backend Framework</strong></td>
                <td>Laravel 11 (PHP 8.2+)</td>
                <td>Routing, ORM Eloquent, Auth Sanctum, Middleware, Transaksi Database.</td>
            </tr>
            <tr>
                <td><strong>Database</strong></td>
                <td>MySQL / MariaDB</td>
                <td>Penyimpanan relasional (48 kelas, jadwal blok, user, attendance, leave).</td>
            </tr>
            <tr>
                <td><strong>Web Dashboard</strong></td>
                <td>Blade Templating + Tailwind / Vanilla CSS</td>
                <td>Panel Waka Kurikulum (Super Admin) untuk manajemen master data & rekap.</td>
            </tr>
            <tr>
                <td><strong>Mobile Client</strong></td>
                <td>Flutter 3.x (Dart)</td>
                <td>Aplikasi mobile multi-peran (Guru Pengajar, Wali Kelas, Guru BK, Siswa, Ortu).</td>
            </tr>
            <tr>
                <td><strong>PDF Engine</strong></td>
                <td>Barryvdh DomPDF</td>
                <td>Cetak laporan rekapitulasi presensi berkop resmi sekolah & dokumen PDF.</td>
            </tr>
            <tr>
                <td><strong>Schedule Parser</strong></td>
                <td>Smalot PDFParser + Custom Heuristic</td>
                <td>Ekstraksi otomatis jadwal mingguan FET PDF & Matrix Ruangan.</td>
            </tr>
        </tbody>
    </table>

    <div class="page-break"></div>

    <!-- BAB 2: STRUKTUR PERAN & MATRIKS HAK AKSES -->
    <h1>BAB 2: STRUKTUR 6 PERAN PENGGUNA (RBAC)</h1>
    <p>
        Sistem SCP menerapkan *Role-Based Access Control* (RBAC) yang ketat berbasis ID Peran (<code>role_id</code>) pada tabel <code>users</code>:
    </p>

    <table>
        <thead>
            <tr>
                <th>Role ID</th>
                <th>Nama Peran</th>
                <th>Interface</th>
                <th>Wewenang & Cakupan Akses</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>1</strong></td>
                <td><strong>Waka Kurikulum (Admin)</strong></td>
                <td><span class="badge badge-primary">Web Browser</span></td>
                <td>Super Admin KBM: Kontrol siklus blok, import FET PDF, import user, mutasi kenaikan kelas, monitoring jurnal, rekapitulasi presensi sekolah.</td>
            </tr>
            <tr>
                <td><strong>2</strong></td>
                <td><strong>Guru Pengajar</strong></td>
                <td><span class="badge badge-success">Mobile App</span></td>
                <td>Melihat jadwal mengajar harian, mencatat presensi siswa (Hadir, Sakit, Izin, Alpa), pengisian jurnal materi KBM, draf lokal offline.</td>
            </tr>
            <tr>
                <td><strong>3</strong></td>
                <td><strong>Siswa</strong></td>
                <td><span class="badge badge-warning">Mobile App</span></td>
                <td>Melihat jadwal pelajaran kelas, rekap kehadiran pribadi, dan mengajukan surat permohonan izin/sakit dengan unggah bukti foto/PDF.</td>
            </tr>
            <tr>
                <td><strong>4</strong></td>
                <td><strong>Orang Tua Siswa</strong></td>
                <td><span class="badge badge-warning">Mobile App</span></td>
                <td>Memantau kehadiran anak secara transparan, mengajukan surat izin mewakili siswa dengan login nomor NIS diawali huruf 'P'.</td>
            </tr>
            <tr>
                <td><strong>5</strong></td>
                <td><strong>Guru BK</strong></td>
                <td><span class="badge badge-primary">Mobile App</span></td>
                <td>Memantau kedisiplinan rombel binaan (Pilihan A: 48 kelas dibagi ke 5 Guru BK), verifikasi izin bypass, deteksi alpa kritis, cetak PDF resmi.</td>
            </tr>
            <tr>
                <td><strong>6</strong></td>
                <td><strong>Wali Kelas</strong></td>
                <td><span class="badge badge-success">Mobile App</span></td>
                <td>Monitoring presensi kelas binaannya, verifikasi surat izin siswa (hak eksklusif 30 menit pertama), cetak rekapitulasi presensi bulanan.</td>
            </tr>
        </tbody>
    </table>

    <div class="callout-info">
        <strong>Ketentuan Khusus Akun Guru:</strong>
        Guru yang memiliki tugas tambahan sebagai Wali Kelas secara otomatis memiliki hak akses ganda: dapat mengajar jam KBM serta mengelola tab khusus <em>Kelas Binaan</em> di aplikasi mobile.
    </div>

    <!-- BAB 3: FITUR UTAMA WEB WAKA KURIKULUM -->
    <h1>BAB 3: FITUR UTAMA WEB WAKA KURIKULUM</h1>

    <h2>3.1 Dashboard Komando & Kontrol Siklus</h2>
    <p>
        Menampilkan ringkasan metrik tingkat sekolah: total guru, total siswa aktif, total 48 rombel, status siklus kurikulum (<em>Putaran 1</em> / <em>Putaran 2</em>), dan tombol kunci jadwal untuk mencegah perubahan data saat KBM sedang berjalan.
    </p>

    <h2>3.2 Impor Otomatis Jadwal FET PDF & Matrix Ruangan</h2>
    <p>
        Fitur unggulan untuk mengimpor jadwal sekolah tanpa input manual satu per satu:
    </p>
    <ul>
        <li><code>ScheduleImportService.php</code>: Membaca berkas PDF keluaran aplikasi FET timetable, mendeteksi hari, jam pelajaran, guru pengampu, mata pelajaran, dan ruangan secara heuristik anti-salah.</li>
        <li><code>RoomMatrixImportService.php</code>: Menormalkan nama ruangan lab/teori sekolah (misal: <em>LAB RPL 1</em>, <em>R. 20</em>) ke tabel <code>rooms</code>.</li>
    </ul>

    <h2>3.3 Kenaikan Kelas & Kelulusan Berantai (Promotion Flow)</h2>
    <p>
        Dikelola oleh <code>PromotionController.php</code> dengan validasi pencegahan konflik:
    </p>
    <ol>
        <li><strong>Kelulusan Kelas XII:</strong> Siswa tingkat XII ditandai lulus (<code>is_graduated = 1</code>) dan akun dinonaktifkan dari KBM aktif.</li>
        <li><strong>Kenaikan Kelas XI &rarr; XII:</strong> Rombel tingkat XI dinaikkan ke tingkat XII setelah kelas XII lama dikosongkan.</li>
        <li><strong>Kenaikan Kelas X &rarr; XI:</strong> Rombel tingkat X dinaikkan ke tingkat XI.</li>
    </ol>

    <h2>3.4 Monitoring Jurnal & Absensi Guru</h2>
    <p>
        Waka Kurikulum dapat memeriksa guru mana saja yang belum mengisi jurnal pembelajaran pada tanggal dan sesi KBM tertentu, sehingga ketertiban administrasi mengajar terjamin.
    </p>

    <h2>3.5 Rekapitulasi Presensi Berstandar Dinas & Cetak PDF</h2>
    <p>
        Dikelola oleh <code>AttendanceRecapService.php</code>:
    </p>
    <ul>
        <li>Menghitung total hari efektif dalam rentang tanggal yang dipilih (exclude Sabtu & Minggu).</li>
        <li>Menyajikan persentase kehadiran: <code>(Hadir / Hari Efektif) &times; 100%</code>.</li>
        <li>Menandai status: <em>Lengkap / Tertib</em>, <em>Peringatan Alpa</em>, atau <em>Belum Final</em> (jika ada permohonan izin yang masih pending).</li>
        <li>Mencetak laporan PDF berkop resmi SMK Negeri 1 Bantul lengkap dengan tanda tangan Kepala Sekolah, Waka Kurikulum, atau Wali Kelas.</li>
    </ul>

    <div class="page-break"></div>

    <!-- BAB 4: FITUR INTEGRASI PRESENSI KBM & LOGIKA BISNIS KUNCI -->
    <h1>BAB 4: FITUR INTEGRASI PRESENSI & LOGIKA BISNIS</h1>

    <h2>4.1 Form Presensi KBM & Penguncian Status Siswa (Guru Pengajar)</h2>
    <p>
        Guru Pengajar menggunakan <code>SesiMengajarPage</code> di Flutter untuk mengisi presensi jam KBM:
    </p>
    <ul>
        <li><strong>Status Siswa:</strong> Hadir (H), Izin (I), Sakit (S), Alpa (A).</li>
        <li><strong>Auto-Lock Siswa Berizin:</strong> Siswa yang memiliki surat izin/sakit resmi yang telah disetujui otomatis berstatus 'I' atau 'S' dan tombol pilihan statusnya dikunci (<em>disabled</em>) agar guru pengajar tidak salah mencatat siswa sebagai Alpa.</li>
        <li><strong>Draf Lokal Offline:</strong> Presensi tersimpan sementara di penyimpanan perangkat sebelum tombol <em>Selesai Sesi</em> ditekan, mencegah kehilangan data saat sinyal terputus.</li>
    </ul>

    <h2>4.2 Logika Bypass Izin Dinamis (Wali Kelas & Guru BK)</h2>
    <p>
        Aturan verifikasi surat perizinan siswa diatur dalam <code>LeaveBypassService.php</code>:
    </p>
    <div class="callout">
        <strong>Aturan 30 Menit KBM Pertama:</strong><br>
        1. Saat permohonan izin diajukan, hak verifikasi berada eksklusif pada <strong>Wali Kelas</strong>.<br>
        2. Jika Wali Kelas ditandai berhalangan hadir oleh Waka Kurikulum, atau KBM jam pertama telah berjalan &gt;30 menit dan izin masih pending, sistem otomatis mengaktifkan hak <strong>Bypass ke Guru BK</strong>.<br>
        3. <strong>Pilihan A (Rombel Binaan BK):</strong> Guru BK hanya berwenang memverifikasi izin siswa pada 48 rombel yang menjadi tanggung jawab binaannya sesuai SK Kepala Sekolah.
    </div>

    <h2>4.3 Pembagian 48 Rombel Binaan ke 5 Guru BK (Pilihan A)</h2>
    <table>
        <thead>
            <tr>
                <th style="width: 30%;">Nama Guru BK</th>
                <th style="width: 15%;">Jumlah Kelas</th>
                <th style="width: 55%;">Daftar Kelas Binaan yang Diampu</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>CANDRA RAHADIAN, S.Pd</strong><br><code>candra_rahadian</code></td>
                <td>10 Kelas</td>
                <td>X AKL 1, X AKL 2, X AKL 3, X TKJ 2, XI AKL 3, XI LPS 2, XI TKJ 2, XII AKL 4, XII LPS 1, XII TKJ 2</td>
            </tr>
            <tr>
                <td><strong>NURHIDAYAT SULISTIAWAN, S.Pd</strong><br><code>nurhidayat_sulistiawan</code></td>
                <td>10 Kelas</td>
                <td>X LPS 1, X TKJ 1, X DKV 1, X DKV 2, XI TKJ 1, XI DKV 1, XI DKV 2, XII TKJ 1, XII DKV 1, XII DKV 2</td>
            </tr>
            <tr>
                <td><strong>RYAWIDYANINGTYAS, S.Pd.</strong><br><code>ryawidyaningtyas</code></td>
                <td>9 Kelas</td>
                <td>X LPS 2, X RPL 1, X RPL 2, XI RPL 1, XI RPL 2, XII AKL 1, XII AKL 2, XII RPL 1, XII RPL 2</td>
            </tr>
            <tr>
                <td><strong>AISYAH WULANDARI, S.Pd</strong><br><code>aisyah_wulandari</code></td>
                <td>10 Kelas</td>
                <td>X MP 1, X MP 2, XI AKL 1, XI AKL 2, XI LPS 1, XI MP 1, XI MP 2, XII AKL 3, XII MP 1, XII MP 2</td>
            </tr>
            <tr>
                <td><strong>ALIVI SAZKIA HAMIE, S.Pd</strong><br><code>alivi_sazkia</code></td>
                <td>9 Kelas</td>
                <td>X BR 1, X BR 2, X BD 1, XI BR 1, XI BR 2, XI BD 1, XII BR 1, XII BR 2, XII BD 1</td>
            </tr>
        </tbody>
    </table>

    <div class="page-break"></div>

    <!-- BAB 5: PETA LETAK KODE & DIREKTORI PROYEK -->
    <h1>BAB 5: PETA LETAK KODE & DIREKTORI PROYEK</h1>
    <p>
        Berikut adalah panduan direktori berkas kode program pada sistem web Laravel (<code>c:\laragon\www\Sistem SCP</code>):
    </p>

    <h2>5.1 Model Data (Eloquent ORM) — <code>app/Models/</code></h2>
    <table>
        <thead>
            <tr>
                <th style="width: 28%;">Nama File</th>
                <th>Kegunaan & Relasi Utama</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><code>User.php</code></td>
                <td>Model pengguna semua peran (Waka, Guru, Siswa, Walas, BK, Ortu). Memiliki relasi <code>schoolClass()</code>, <code>parent()</code>, <code>children()</code>, <code>bkClasses()</code>.</td>
            </tr>
            <tr>
                <td><code>Classes.php</code></td>
                <td>Model kelas rombel (48 rombel). Menyimpan field <code>class_name</code>, <code>grade_level</code>, <code>major</code>, <code>section</code>, dan <code>bk_id</code> (Guru BK pengampu).</td>
            </tr>
            <tr>
                <td><code>Schedule.php</code></td>
                <td>Model jadwal mingguan (guru, mapel, kelas, ruangan, hari, jam, siklus Putaran 1/2).</td>
            </tr>
            <tr>
                <td><code>Journal.php</code></td>
                <td>Model jurnal mengajar sesi KBM guru (tanggal, jadwal_id, materi, status kehadiran).</td>
            </tr>
            <tr>
                <td><code>Attendance.php</code></td>
                <td>Catatan kehadiran siswa per sesi jurnal KBM (status: Hadir, Sakit, Izin, Alpa).</td>
            </tr>
            <tr>
                <td><code>LeaveRequest.php</code></td>
                <td>Permohonan surat izin/sakit siswa, tanggal, berkas lampiran, status persetujuan, verifikator.</td>
            </tr>
            <tr>
                <td><code>TeacherAbsence.php</code></td>
                <td>Catatan ketidakhadiran guru/walas yang ditandai oleh Waka (pemicu langsung bypass BK).</td>
            </tr>
            <tr>
                <td><code>SystemConfiguration.php</code></td>
                <td>Pengaturan global sistem: siklus aktif (Putaran 1/2), tahun ajaran, kunci jadwal KBM.</td>
            </tr>
        </tbody>
    </table>

    <h2>5.2 Controller Web Waka Kurikulum — <code>app/Http/Controllers/Web/Waka/</code></h2>
    <table>
        <thead>
            <tr>
                <th style="width: 32%;">Nama File</th>
                <th>Fitur & Endpoint yang Ditangani</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><code>DashboardController.php</code></td>
                <td>Halaman beranda Waka, toggle siklus aktif KBM, toggle kunci jadwal.</td>
            </tr>
            <tr>
                <td><code>ScheduleController.php</code></td>
                <td>CRUD jadwal pelajaran, validasi anti-bentrok guru & ruangan, import jadwal FET PDF.</td>
            </tr>
            <tr>
                <td><code>ClassController.php</code></td>
                <td>CRUD master kelas, penugasan wali kelas, import walas, toggle ketidakhadiran walas.</td>
            </tr>
            <tr>
                <td><code>PromotionController.php</code></td>
                <td>Kenaikan tingkat siswa berantai (X &rarr; XI &rarr; XII) dan kelulusan siswa XII.</td>
            </tr>
            <tr>
                <td><code>AttendanceRecapController.php</code></td>
                <td>Tabel rekapitulasi presensi bulanan tingkat sekolah & unduh PDF berstandar dinas.</td>
            </tr>
            <tr>
                <td><code>JournalMonitoringController.php</code></td>
                <td>Pemantauan real-time pengisian jurnal guru pengajar per sesi KBM.</td>
            </tr>
            <tr>
                <td><code>UserController.php</code></td>
                <td>CRUD akun guru/siswa/ortu, impor massal format tabel presensi sekolah.</td>
            </tr>
        </tbody>
    </table>

    <h2>5.3 Service Logika Bisnis — <code>app/Services/</code></h2>
    <table>
        <thead>
            <tr>
                <th style="width: 32%;">Nama File</th>
                <th>Deskripsi Logika Bisnis</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><code>LeaveBypassService.php</code></td>
                <td>Inti aturan bypass izin: evaluasi hak eksklusif walas 30 menit, wewenang BK per rombel binaan.</td>
            </tr>
            <tr>
                <td><code>AttendanceRecapService.php</code></td>
                <td>Perhitungan hari efektif KBM, agregasi H/I/S/A, persentase kehadiran kumulatif.</td>
            </tr>
            <tr>
                <td><code>ScheduleImportService.php</code></td>
                <td>Ekstraksi teks tabel dari PDF jadwal FET, pemetaan hari, jam, guru, dan kelas otomatis.</td>
            </tr>
            <tr>
                <td><code>RoomMatrixImportService.php</code></td>
                <td>Normalisasi matriks penggunaan ruangan teori dan laboratorium kejuruan.</td>
            </tr>
            <tr>
                <td><code>UserImportService.php</code></td>
                <td>Parser berkas absensi siswa/guru, deteksi otomatis wali kelas, pembuatan password default.</td>
            </tr>
            <tr>
                <td><code>OtpService.php</code></td>
                <td>Pembuatan kode OTP 6 digit, batas kedaluwarsa 10 menit, dan pengiriman email reset password.</td>
            </tr>
        </tbody>
    </table>

    <div class="page-break"></div>

    <!-- BAB 6: BEDAH KODE PENTING -->
    <h1>BAB 6: BEDAH KODE PENTING (CODE DEEP DIVE)</h1>

    <h2>6.1 Logika Otorisasi Bypass Izin BK (Pilihan A)</h2>
    <p>
        Potongan kode pada <code>app/Services/LeaveBypassService.php</code> yang memastikan Guru BK hanya dapat menyetujui izin pada kelas binaannya:
    </p>
    <pre><code>// 3. Guru BK (Mode Bypass Dinamis per Rombel Binaan - Pilihan A)
if ($user->role_id === Role::BK) {
    $studentClass = $leave->student?->schoolClass ?? Classes::find($studentClassId);
    
    // Cek apakah kelas siswa ini diampu oleh Guru BK yang sedang login
    if ($studentClass && $studentClass->bk_id && (int) $studentClass->bk_id !== (int) $user->id) {
        $assignedBk = $studentClass->bkTeacher?->fullname ?? 'Guru BK lain';
        return [
            'allowed' => false,
            'is_bypass' => false,
            'message' => "Akses ditolak: Anda hanya berwenang memverifikasi perizinan siswa untuk rombel binaan Anda. Kelas {$studentClass->class_name} berada di bawah tanggung jawab {$assignedBk}.",
        ];
    }

    // Evaluasi apakah Wali Kelas berhalangan hadir atau telah melewati toleransi 30 menit KBM
    if ($isWalasAbsent || $now->gte($bypassDeadline)) {
        return ['allowed' => true, 'is_bypass' => true, 'message' => 'Bypass BK aktif.'];
    }
}</code></pre>

    <h2>6.2 Sinkronisasi Otomatis Izin ke Sesi Presensi Guru (CRIT-06)</h2>
    <p>
        Potongan kode pada <code>app/Http/Controllers/Api/LeaveRequestController.php</code> di dalam fungsi <code>verify()</code>:
    </p>
    <pre><code>// Saat izin disetujui (Approved), sinkronkan otomatis ke presensi KBM
if ($leave->status_approval === 'approved' && $leave->student?->class_id) {
    $statusPresensi = $leave->leave_type ?: 'izin'; // 'izin' atau 'sakit'

    // Cari seluruh jurnal KBM pada kelas siswa dalam rentang tanggal izin
    $journals = Journal::whereBetween('date', [$leave->start_date, $leave->end_date])
        ->whereHas('schedule', fn($q) => $q->where('class_id', $leave->student->class_id))
        ->get();

    foreach ($journals as $journal) {
        Attendance::updateOrCreate(
            ['journal_id' => $journal->id, 'student_id' => $leave->student_id],
            ['status' => $statusPresensi]
        );
    }
}</code></pre>

    <h2>6.3 Perhitungan Hari Efektif KBM (Bebas Akhir Pekan)</h2>
    <p>
        Potongan kode pada <code>app/Services/AttendanceRecapService.php</code>:
    </p>
    <pre><code>public static function getEffectiveDays(string $startDate, string $endDate): int
{
    $start = Carbon::parse($startDate);
    $end = Carbon::parse($endDate);
    $effectiveDays = 0;

    while ($start->lte($end)) {
        // Hanya hitung hari Senin (1) sampai Jumat (5)
        if ($start->isWeekday()) {
            $effectiveDays++;
        }
        $start->addDay();
    }

    return max(1, $effectiveDays);
}</code></pre>

    <div class="page-break"></div>

    <!-- BAB 7: PANDUAN PRAKTIS & TROUBLESHOOTING -->
    <h1>BAB 7: PANDUAN BELAJAR & TROUBLESHOOTING</h1>

    <h2>7.1 Perintah Artisan Penting</h2>
    <table>
        <thead>
            <tr>
                <th style="width: 45%;">Perintah Terminal</th>
                <th>Tujuan & Kegunaan</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><code>php artisan migrate</code></td>
                <td>Menjalankan migrasi database terbaru (termasuk kolom <code>bk_id</code>).</td>
            </tr>
            <tr>
                <td><code>php artisan test</code></td>
                <td>Menjalankan seluruh 40 unit & feature test suite backend Laravel.</td>
            </tr>
            <tr>
                <td><code>php artisan test --filter=LeaveBypassLogicTest</code></td>
                <td>Menguji khusus logika bypass izin Wali Kelas & Guru BK.</td>
            </tr>
            <tr>
                <td><code>php artisan optimize:clear</code></td>
                <td>Membersihkan cache route, config, dan views Blade jika ada perubahan kode.</td>
            </tr>
            <tr>
                <td><code>php artisan tinker</code></td>
                <td>Membuka interactive PHP shell untuk mengecek data database secara langsung.</td>
            </tr>
        </tbody>
    </table>

    <h2>7.2 Perintah Pengujian Mobile (Flutter)</h2>
    <table>
        <thead>
            <tr>
                <th style="width: 45%;">Perintah Terminal</th>
                <th>Tujuan & Kegunaan</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><code>flutter analyze</code></td>
                <td>Memeriksa sintaks Dart dan memastikan 0 warning / error.</td>
            </tr>
            <tr>
                <td><code>flutter test</code></td>
                <td>Menjalankan seluruh 37 pengujian widget dan state mobile.</td>
            </tr>
            <tr>
                <td><code>flutter run -d chrome</code></td>
                <td>Menjalankan aplikasi mobile di web browser untuk simulasi cepat.</td>
            </tr>
        </tbody>
    </table>

    <h2>7.3 Troubleshooting & FAQ</h2>
    <div class="callout-warning">
        <strong>1. Error 403 Forbidden saat Verifikasi Izin:</strong><br>
        Pastikan akun yang sedang login memiliki wewenang: jika Wali Kelas, siswa harus berasal dari kelas binaannya; jika Guru BK, siswa harus berasal dari rombel binaan resmi sesuai SK 48 kelas.
    </div>
    <div class="callout-warning">
        <strong>2. Mengapa Siswa Terkunci 'Sakit' / 'Izin' di Jam Pelajaran Guru?</strong><br>
        Ini adalah perilaku yang benar (CRIT-06). Karena surat izin/sakit siswa sudah diverifikasi resmi oleh Walas atau BK, guru pengajar tidak perlu mengabsen ulang siswa tersebut.
    </div>
    <div class="callout-warning">
        <strong>3. Cara Mengubah Siklus Pembelajaran (Putaran 1 &rarr; Putaran 2):</strong><br>
        Waka Kurikulum dapat menekan tombol <em>Ganti Siklus KBM</em> pada Dashboard Web Waka. Seluruh jadwal yang tampil pada guru pengajar otomatis menyesuaikan putaran aktif.
    </div>

    <!-- FOOTER HALAMAN -->
    <div class="footer">
        Sistem SCP (School Class Progress) • SMK Negeri 1 Bantul • Dokumentasi Resmi TA 2026/2027
    </div>

</body>
</html>

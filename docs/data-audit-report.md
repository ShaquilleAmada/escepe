# Laporan Audit Validitas Data & Kepatuhan Spesifikasi Teknis
**Sistem SCP (School Class Progress) — SMK Negeri 1 Bantul**  
*Auditor: Pengembang Senior Laravel & Auditor Sistem KBM*  
*Tanggal Audit: 6 November 2026*  
*Status: FASE A & B SELESAI (BACA-SAJA) — Menunggu Persetujuan Pengguna Sebelum Fase C*  
*Database Salinan Audit: `sistem_scp_dev` (Dump Backup: `backup_sistem_scp_sebelum_audit.sql` - 1.89 MB)*

---

## 1. Ringkasan Eksekutif & Statistik Temuan

Berdasarkan audit komparatif menyeluruh terhadap basis data `sistem_scp_dev`, codebase backend Laravel, API Sanctum, Web Blade Waka, dan acuan mockup mobile `scp-mobile-ui.html`, ditemukan **28 temuan** penyimpangan dari spesifikasi final, kerentanan otorisasi, dan ketiadaan komponen kunci.

### Ringkasan Jumlah Temuan per Tingkat Keparahan
| Tingkat Keparahan | `[BUG]` | `[BEDA DARI SPEK]` | `[FITUR BELUM ADA]` | Total |
| :--- | :---: | :---: | :---: | :---: |
| **Kritis (Critical)** | 3 | 4 | 3 | **10** |
| **Tinggi (High)** | 2 | 4 | 3 | **9** |
| **Sedang (Medium)** | 1 | 3 | 2 | **6** |
| **Rendah (Low)** | 1 | 2 | 0 | **3** |
| **TOTAL** | **7** | **13** | **8** | **28** |

### Menu & Area Paling Berisiko
1. **Otorisasi & API Perizinan Siswa (`/api/leave-requests`):** Tidak ada validasi hak verifikasi Walas (Walas kelas A bisa approve kelas B), tidak ada aturan batas waktu bypass BK di backend, dan file surat izin anak di bawah umur tersimpan di disk publik tanpa proteksi.
2. **API Manajemen Jadwal (`/api/schedules`):** Endpoint CUD (`POST`, `PUT`, `DELETE`) terbuka bebas bagi seluruh role yang terautentikasi (siswa/ortu/guru bisa memanipulasi jadwal sekolah), bertentangan mutlak dengan aturan bahwa jadwal hanya diinput Waka.
3. **Struktur Master Data Pengguna & Role ID:** Inkonsistensi nomor `role_id` antara spesifikasi user (`role_id=4` Siswa, `role_id=6` Ortu) vs database aktual (`role_id=3` Siswa, `role_id=4` Ortu, `role_id=6` Walas), serta username 1.711 siswa tersimpan sebagai teks nama slug (`adinda_zahra`) alih-alih angka NIS murni.
4. **Modul Rekapitulasi Presensi & Hari Libur:** Belum tersedianya tabel `school_holidays`, rumus persentase kehadiran belum menyertakan cakupan hari efektif, dan belum ada penghasil PDF resmi di server.

---

## 2. Tabel Lengkap Temuan Audit (Diurutkan dari Kritis)

| ID | Area / Modul | Tingkat | Tag | Bukti Faktual | Dampak | Usulan Perbaikan | Est. Usaha |
| :--- | :--- | :---: | :---: | :--- | :--- | :--- | :---: |
| **CRIT-01** | API Jadwal Pelajaran | **Kritis** | `[BUG]` | `routes/api.php:87-90` mendaftarkan `POST`, `PUT`, `DELETE` `/api/schedules` di dalam `auth:sanctum` tanpa middleware `EnsureUserHasRole:1`. | Siswa, orang tua, atau guru dapat menambah, mengubah, atau menghapus jadwal resmi sekolah melalui API. Melanggar spesifikasi: *"Jadwal HANYA diinput Waka"*. | Hapus route CUD jadwal dari API mobile atau pasang middleware `EnsureUserHasRole:1` agar eksklusif Waka. | 30 menit |
| **CRIT-02** | Keamanan Data Anak / Upload | **Kritis** | `[BUG]` | `LeaveRequestController.php:72`: `$request->file('document')->store('documents/leave_requests', 'public')`. Symlink `public/storage` aktif di web server. | Dokumen medis/surat izin siswa (anak di bawah umur) dapat diakses langsung oleh publik di internet tanpa login jika URL file diketahui. | Pindahkan penyimpanan ke disk `local` privat (`storage/app/private/documents`), buat route streaming file dengan otorisasi ketat (`auth:sanctum` / middleware role). | 2 jam |
| **CRIT-03** | Otorisasi Verifikasi Izin | **Kritis** | `[BUG]` | `LeaveRequestController.php:110`: `if (!in_array($user->role_id, [1, 5, 6]))`. Tidak ada pengecekan kecocokan `user->class_id` dengan kelas siswa. | IDOR Kritis: Wali Kelas X RPL 1 bisa menyetujui atau menolak surat izin siswa kelas XII TKJ 2. Guru BK bisa memverifikasi kapan saja tanpa menunggu syarat bypass. | Validasi ketat di controller: Walas hanya boleh verifikasi siswa kelas binaannya (`user->class_id == student->class_id`); BK hanya boleh jika syarat bypass terpenuhi. | 2 jam |
| **CRIT-04** | Aturan Bypass BK | **Kritis** | `[FITUR BELUM ADA]` | `LeaveRequestController.php:107-130` tidak memiliki kalkulasi `jam_mulai_kbm + 30 menit` dan tidak mengecek status kehadiran Walas. | Logika bypass dinamis tidak berjalan di backend. BK bisa mem-bypass kapan saja atau malah tidak bisa mem-bypass jika UI mobile mengunci. | Buat service backend `LeaveBypassService` yang menghitung: (a) status absen Walas tanggal bersangkutan, (b) jam KBM pertama kelas siswa pada hari itu + 30 menit berdasarkan siklus aktif. | 4 jam |
| **CRIT-05** | Skema Keputusan Izin | **Kritis** | `[BEDA DARI SPEK]` | Tabel `leave_requests` hanya memiliki kolom: `id, student_id, applicant_id, verified_by, start_date, end_date, reason, document_path, status_approval`. | Kolom wajib spesifikasi belum ada: `is_bypass` (boolean), `decided_at` (timestamp), `rejection_reason` (text), dan `leave_type` ('izin'/'sakit'). Data keputusan tidak akuntabel. | Tambahkan migration alter tabel `leave_requests`: kolom `leave_type`, `is_bypass`, `decided_at`, dan `rejection_reason`. | 1 jam |
| **CRIT-06** | Sinkronisasi Presensi Izin | **Kritis** | `[BEDA DARI SPEK]` | `LeaveRequestController.php:121-125` hanya mengupdate baris `leave_requests`. Query B1.6: 0 attendances disinkronkan otomatis dari izin approved. | Siswa yang izinnya telah disetujui resmi tetap berisiko tercatat Alpa pada form presensi KBM guru mapel karena tidak ada sinkronisasi otomatis ke tabel `attendances`. | Saat izin di-approve, picu sinkronisasi ke tabel presensi: buat/update status presensi siswa pada seluruh sesi KBM di rentang tanggal tersebut menjadi `izin` atau `sakit`. | 3 jam |
| **CRIT-07** | Format Username Siswa | **Kritis** | `[BEDA DARI SPEK]` | Query B1.5: 1.711 siswa di tabel `users` memiliki `username` berupa nama slug (mis. `adinda_zahra`). Angka NIS (mis. `19126`) tersimpan di kolom `nip_nisn`. | Bertentangan dengan spesifikasi final: *"Siswa: username = NIS (4 sampai 5 digit, bukan NISN)"*. Menyulitkan siswa login karena nama slug rawan typo dan bentrok. | Buat script migrasi data untuk menukar `username` siswa menjadi nilai `nip_nisn` (NIS 5 digit), dan pastikan integritas relasi `P + NIS` orang tua. | 2 jam |
| **CRIT-08** | Inkonsistensi Pemetaan Role ID | **Kritis** | `[BEDA DARI SPEK]` | Isi tabel `roles`: 1=Admin, 2=Guru, 3=Siswa, 4=OrangTua, 5=BK, 6=WaliKelas. Spesifikasi mengasumsikan: Siswa=4, Ortu=6. | Jika developer menyetel `role_id=4` untuk Siswa, akun tersebut menjadi Orang Tua; jika disetel `role_id=6` untuk Ortu, akun menjadi Wali Kelas. | Tetapkan dan kunci konstanta Role ID di seluruh sistem: `Role::ADMIN=1`, `Role::GURU=2`, `Role::SISWA=3`, `Role::ORANG_TUA=4`, `Role::BK=5`, `Role::WALI_KELAS=6`. | 1 jam |
| **CRIT-09** | Tabel Kalender Hari Libur | **Kritis** | `[FITUR BELUM ADA]` | Query A2: Tabel `school_holidays` belum ada di database. | Perhitungan hari efektif KBM pada rentang tanggal bebas (PTS/Semester) mustahil akurat karena hari libur nasional/sekolah terhitung sebagai hari aktif. | Buat migration tabel `school_holidays (id, holiday_date, description, created_by)`, model, serta UI CRUD sederhana di panel Waka. | 2.5 jam |
| **CRIT-10** | Toggle Kehadiran Walas | **Kritis** | `[FITUR BELUM ADA]` | Query A2: Tidak ada tabel `teacher_absences` maupun endpoint/toggle kehadiran Walas di web Waka. | Waka tidak memiliki sarana untuk menandai Walas yang berhalangan hadir pada tanggal tertentu, sehingga Trigger Bypass (a) tidak dapat berfungsi. | Buat migration `teacher_absences (id, teacher_id, date, reason, marked_by, timestamps)`, model, dan widget toggle saklar cepat di Dashboard/User Waka. | 3 jam |
| **HIGH-01** | Transaksi Impor Data | **Tinggi** | `[BUG]` | `UserImportService.php` dan `ScheduleImportService.php` tidak membungkus proses dengan `DB::beginTransaction()`. | Gagal memenuhi aturan *"semua-atau-tidak-sama-sekali"*. Jika baris ke-100 error, 99 baris sebelumnya tetap masuk dan membuat data kotor/setengah jadi. | Bungkus seluruh loop parser dengan `DB::transaction()`. Jika ada satu baris format fatal, rollback seluruh transaksi dan laporkan rincian nomor barisnya. | 2 jam |
| **HIGH-02** | Proteksi Hapus Kelas & Guru | **Tinggi** | `[BUG]` | `ClassController.php:153` langsung memanggil `$class->delete()`. FK `schedules.class_id` diset `ON DELETE CASCADE`. | Menghapus kelas akan menghapus seluruh jadwal mengajar kelas tersebut secara permanen tanpa konfirmasi/peringatan relasi data. | Tambahkan proteksi relasi: tolak penghapusan kelas/guru jika sudah memiliki jadwal mengajar aktif atau siswa terdaftar (`throw ValidationException`). | 1.5 jam |
| **HIGH-03** | Token Sanctum Tanpa Kedaluwarsa | **Tinggi** | `[BEDA DARI SPEK]` | `config/sanctum.php:49`: `'expiration' => null`. | Token sesi mobile berlaku selamanya. Jika gawai siswa/guru hilang atau dipinjamkan, sesi tidak akan pernah kedaluwarsa. | Tetapkan masa kedaluwarsa token (mis. `'expiration' => 43200` atau 30 hari) dan sediakan mekanisme refresh token. | 30 menit |
| **HIGH-04** | Rate Limiting Login API | **Tinggi** | `[BEDA DARI SPEK]` | `routes/api.php:24`: `Route::post('/login', [AuthController::class, 'login'])` tanpa middleware `throttle`. | Rentan serangan brute-force kredensial, terutama karena format username NIS berurutan dan password default seragam (`password123`). | Tambahkan middleware `throttle:6,1` pada route `/api/login` dan `/login` web serta kunci akun sementara setelah 5 kali gagal. | 45 menit |
| **HIGH-05** | Akun Ortu Tetap Aktif Saat Lulus | **Tinggi** | `[BEDA DARI SPEK]` | `PromotionController.php:215-222`: hanya mengupdate `student_status='lulus'` pada akun Siswa. Baris Orang Tua (`role_id=4`) tidak disentuh. | Orang tua siswa yang sudah lulus tetap bisa login ke aplikasi mobile. Siswa yang lulus pun masih bisa login via API karena `/api/login` tidak memvalidasi `student_status`. | Update status akun Ortu menjadi nonaktif saat anaknya lulus; tambahkan pengecekan `student_status != 'lulus'` pada `Api\AuthController@login`. | 1.5 jam |
| **HIGH-06** | Bentrok Jadwal di Database | **Tinggi** | `[BUG]` | Query B1.4 menemukan bentrok eksisting: 179 bentrok ruangan, 126 bentrok kelas, dan 79 bentrok guru pada `schedules`. | `ScheduleController.php:324` menonaktifkan validasi bentrok (`return null;`). Guru dan ruangan terplot pada jam yang sama di siklus yang sama. | Aktifkan kembali validasi bentrok 3D pada `ScheduleController` dan lakukan rekonsiliasi data jadwal yang bentrok. | 3 jam |
| **HIGH-07** | Status Rekap Belum Final/Final | **Tinggi** | `[FITUR BELUM ADA]` | `AttendanceRecapController.php:76-100` hanya menghitung count `hadir, izin, sakit, alpa`. | Tidak ada label status "Belum Final (badge kuning)" dan "Final / Bersih (badge hijau)", serta tidak ada filter `Semua \| Belum Final \| Final`. | Tambahkan logika penentuan status rekap harian/kelas berdasarkan ada-tidaknya izin pending dan kelengkapan jurnal, serta filter dropdown-nya. | 3 jam |
| **HIGH-08** | Generator & Audit Cetak PDF | **Tinggi** | `[FITUR BELUM ADA]` | `composer.json` tidak memiliki library PDF (DomPDF/Snappy); tidak ada route cetak PDF mobile berotorisasi. | Fitur cetak PDF resmi untuk Walas (kelas binaan) dan BK (semua kelas) belum dapat berjalan di sisi server. | Pasang package `barryvdh/laravel-dompdf`, buat template Blade PDF resmi berkop sekolah, catat log audit pencetakan (`attendance_print_logs`). | 4 jam |
| **HIGH-09** | Log Konseling & Deteksi Alpa > 3 Hari | **Tinggi** | `[FITUR BELUM ADA]` | Query B3: Belum ada logika pendeteksi siswa alpa berturut-turut > 3 hari dan tidak ada tabel `counseling_logs`. | Layar konseling BK pada mockup mobile (`scp-mobile-ui.html:280`) tidak memiliki backend penopang. | Buat migration `counseling_logs (id, student_id, counselor_id, date, notes)`, service deteksi alpa berturut-turut (melewati hari libur), dan API pendukungnya. | 3.5 jam |
| **MED-01** | Ketiadaan FormRequest | **Sedang** | `[BEDA DARI SPEK]` | Folder `app/Http/Requests` kosong (0 file). Semua validasi ditulis inline di dalam controller. | Melanggar prinsip *Clean Architecture* dan spesifikasi B2. Validasi tersebar tidak seragam dan sulit diuji secara terisolasi. | Buat FormRequest khusus untuk tiap form (mis. `StoreScheduleRequest`, `VerifyLeaveRequest`, `StoreUserRequest`). | 3 jam |
| **MED-02** | Penghitungan Guru di Dashboard | **Sedang** | `[BUG]` | `DashboardController.php:32` & `Api\DashboardController.php:68`: `User::where('role_id', 2)->count()`. | Menampilkan hanya 39 guru, padahal riil ada 92 guru aktif (39 Guru Murni, 48 Walas, 5 BK). Angka dashboard tidak akurat. | Ubah query menjadi `User::whereIn('role_id', [2, 5, 6])->count()`. | 15 menit |
| **MED-03** | Format Persentase Tanpa Cakupan | **Sedang** | `[BEDA DARI SPEK]` | `AttendanceController.php:104` menghitung persentase polos tanpa menampilkan cakupan hari/sesi efektif. | Menimbulkan persentase 100% palsu jika baru 1 dari 20 sesi yang terisi presensi. Melanggar spesifikasi rekapitulasi presensi. | Tampilkan format string cakupan pendamping, contoh: `"100% (terisi 1 dari 20 sesi efektif)"`. | 1 jam |
| **MED-04** | Master Data Mata Pelajaran Ganda | **Sedang** | `[BEDA DARI SPEK]` | Tabel `schedules` menyimpan nama mata pelajaran sebagai teks bebas `subject varchar(100)`. Tidak ada tabel master `subjects`. | Jika ada typo saat impor jadwal, nama mapel terduplikasi di halaman Data Master Mapel. Tidak ada integritas kode mata pelajaran. | Buat tabel `subjects (id, code, name, type)` dan tautkan `schedules.subject_id` ke tabel master. | 3 jam |
| **MED-05** | Filter Rentang Tanggal Rekap Waka | **Sedang** | `[BEDA DARI SPEK]` | `AttendanceRecapController.php:23` hanya menerima parameter tanggal tunggal `date` (default hari ini). | Waka tidak bisa melihat akumulasi rekap bulanan (tgl 1 s/d akhir bulan) maupun rentang kustom (PTS/Semester). | Tambahkan parameter `start_date` dan `end_date` dengan nilai bawaan awal dan akhir bulan berjalan. | 2 jam |
| **MED-06** | Kredensial Default di Seeder | **Sedang** | `[BEDA DARI SPEK]` | `UserSeeder.php:19-22` mendaftarkan user `waka_kurikulum` dengan password default `password123`. | Berisiko tinggi jika seeder dijalankan di lingkungan staging/produksi tanpa penggantian kredensial. | Pisahkan seeder testing dengan seeder produksi; buat command generator akun admin interaktif pada instalasi baru. | 1 jam |
| **LOW-01** | Tabel Sisa `products` | **Rendah** | `[BEDA DARI SPEK]` | Database memuat tabel `products` dari sisa starter project Laravel. | Mengotori skema basis data dan tidak memiliki fungsi apapun dalam KBM. | Buat migration drop table `products`. | 15 menit |
| **LOW-02** | Constraint Unik Nama Ruangan | **Rendah** | `[BEDA DARI SPEK]` | Tabel `rooms` dan `classes` tidak memiliki unique index pada kolom `room_name` dan `class_name` di level database. | Integritas data nama ruangan dan kelas hanya bergantung pada validasi controller; rawan terduplikasi jika diakses via seeder/impor. | Tambahkan unique index pada `rooms.room_name` dan `classes.class_name`. | 30 menit |
| **LOW-03** | Constraint Unik Presensi Ganda | **Rendah** | `[BEDA DARI SPEK]` | Tabel `attendances` tidak memiliki composite unique index pada `(journal_id, student_id)`. | Rawan terjadi duplikasi baris absensi untuk siswa yang sama pada sesi yang sama jika ada request paralel / lag jaringan. | Tambahkan composite unique index `attendances_journal_student_unique (journal_id, student_id)`. | 30 menit |

---

## 3. Rincian Bukti & Hasil Uji Integritas Data (Fase B1 - B6)

### B1. Hasil Query Integritas Data (`sistem_scp_dev`)
* **Data Yatim (Orphan):**
  * `schedules` tanpa guru, kelas, atau ruangan: **0 baris** (Bersih).
  * `journals` tanpa jadwal: **0 baris** (Bersih).
  * `attendances` tanpa jurnal atau siswa: **0 baris** (Bersih).
  * `leave_requests` tanpa siswa atau applicant: **0 baris** (Bersih).
* **Duplikasi Data:**
  * Duplikat username, email, telepon, nama kelas, nama ruangan: **0 baris** (Bersih).
* **Bentrok Jadwal Eksisting (Irisan Waktu pada Hari & Siklus Sama):**
  * **Bentrok Ruangan:** **179 pasang jadwal** bertabrakan menempati ruangan yang sama pada jam yang sama (contoh: Jadwal ID 7 `Projek IP` Kelas X TKJ 1 dan Jadwal ID 485 `Projek IPAS` Kelas X AKL 3 sama-sama menempati `Lab IPA` pada Rabu Putaran 2 pukul 08:35–11:30).
  * **Bentrok Kelas:** **126 pasang jadwal** bertabrakan untuk kelas yang sama.
  * **Bentrok Guru:** **79 pasang jadwal** bertabrakan untuk guru yang sama.
* **Format Kredensial Siswa:**
  * **1.711 siswa** memiliki `username` berupa nama lengkap slug (`adinda_zahra`, dll.), bukan format NIS 4–5 digit murni. Akun orang tua sudah berformat `P` + NIS (`P19126`).

### B2. Validasi Kode & FormRequest
* Proyek saat ini memiliki **0 class FormRequest**. Semua validasi dilakukan secara langsung di method controller menggunakan `$request->validate()`.
* Aturan mass assignment (`$fillable`) pada model `User` sudah mencakup kolom-kolom sensitif, namun pada `ProfileController` pembaruan profil sudah dibatasi secara aman sehingga user biasa tidak bisa mengubah `role_id` mereka sendiri.

### B3. Aturan Bisnis & Logika Bypass
* **Skenario Bypass BK:**
  * Pada backend Laravel saat ini, belum ada kalkulasi dinamis waktu KBM jam ke-1 + 30 menit. Tombol verifikasi hanya dicek melalui `in_array(role_id, [1, 5, 6])`.
  * Pada aplikasi mobile (`AttendanceSyncService.dart`), logika bypass baru membaca flag dummy `_walasAttendance[kelas]` tanpa menghitung jam mulai KBM dinamis dari tabel `schedules`.

### B4. Otorisasi & Keamanan
* File lampiran izin siswa tersimpan di direktori publik `storage/app/public/documents/leave_requests`, sehingga URL-nya dapat diakses siapa saja tanpa token otentikasi.
* Halaman login web saat ini tidak menampilkan tulisan kredensial default di layar, namun di dalam `UserSeeder` akun waka default (`waka_kurikulum` / `password123`) masih terdaftar.
* Endpoint autentikasi API `/api/login` belum memiliki perlindungan rate limiting (`throttle`), berpotensi memicu serangan brute-force.

### B5. Konsistensi Web, API, dan Mobile
* Mockup mobile UI `scp-mobile-ui.html` memiliki layar **Log Konseling BK** (khusus siswa alpa > 3 hari berturut-turut) dan layar **Alasan Penolakan Izin**, namun di backend Laravel belum ada tabel `counseling_logs` dan belum ada kolom `rejection_reason` pada `leave_requests`.

---

## 4. Pertanyaan Keputusan yang Masih Terbuka (B7)

Berikut adalah daftar keputusan arsitektural yang **belum tercakup** dalam spesifikasi sebelumnya, disertai usulan jawaban bawaan (*default recommendation*) dari kami:

### Pertanyaan 1: Tahun Ajaran & Semester
* **Masalah:** Tabel `system_configurations` hanya menyimpan `active_cycle` (Putaran 1/2) dan `is_schedule_locked`. Tidak ada penanda Tahun Ajaran (misal: "2026/2027") dan Semester ("Ganjil" / "Genap").
* **Usulan Bawaan (Rekomendasi):**
  Tambahkan kolom `academic_year varchar(20)` (default: `"2026/2027"`) dan `semester enum('Ganjil','Genap')` (default: `"Ganjil"`) pada tabel `system_configurations`. Parameter ini akan menjadi header resmi pada lembar cetak PDF laporan presensi dan riwayat kelulusan.

### Pertanyaan 2: Format Tata Letak Dokumen PDF Resmi
* **Masalah:** Spesifikasi mewajibkan server mencetak PDF resmi presensi untuk Walas dan BK, namun format visual dan penandatangan belum dirinci.
* **Usulan Bawaan (Rekomendasi):**
  Gunakan tata letak standar kedinasan:
  * **Header:** Kop resmi SMK Negeri 1 Bantul lengkap dengan logo dan garis ganda.
  * **Body:** Tabel matriks presensi siswa (Hadir, Sakit, Izin, Alpa, Persentase, dan Cakupan Hari Efektif).
  * **Footer Tanda Tangan:**
    * Untuk Walas: Mengetahui Kepala Sekolah / Waka Kurikulum (kiri) & Wali Kelas bersangkutan (kanan).
    * Untuk BK: Mengetahui Waka Kurikulum (kiri) & Guru BK bersangkutan (kanan).
    * Footer audit kecil di bagian bawah: *"Dicetak oleh: [Nama User] ([Role]) pada [Tanggal & Waktu WIB]"*.

### Pertanyaan 3: Kebijakan Penghapusan Data (Soft Deletes vs Restrict)
* **Masalah:** Saat ini penghapusan kelas, guru, atau mata pelajaran langsung menghapus baris terkait secara permanen (*hard delete*) dengan efek *cascade*.
* **Usulan Bawaan (Rekomendasi):**
  Terapkan **Foreign Key `RESTRICT`** pada level database dan logika controller: Data kelas, guru, dan ruangan **dilarang dihapus** jika sudah memiliki riwayat jadwal KBM atau riwayat presensi siswa. Sekolah hanya boleh menonaktifkan (*deactivate*), bukan menghapus, demi menjaga integritas arsip akademik.

### Pertanyaan 4: Batasan Lockout Akun & Kebijakan Password
* **Masalah:** Belum ada mekanisme penguncian akun jika terjadi salah password berturut-turut.
* **Usulan Bawaan (Rekomendasi):**
  Terapkan proteksi: Jika pengguna salah memasukkan password **5 kali berturut-turut**, akun dikunci sementara selama **15 menit** menggunakan Laravel RateLimiter. Password awal siswa (`password123`) diwajibkan diganti saat login pertama kali (*force change password on first login*).

---

## 5. Status & Langkah Selanjutnya

> [!IMPORTANT]
> **Pemberitahuan Kepatuhan Aturan Kerja:**  
> Sesuai instruksi: **Fase A dan B telah selesai dijalankan dalam mode BACA-SAJA.**  
> Belum ada baris kode atau skema basis data produksi yang diubah.  
> **Kami berhenti di sini dan menunggu persetujuan eksplisit Anda sebelum melangkah ke FASE C (Eksekusi Perbaikan Berkelompok).**


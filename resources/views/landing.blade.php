<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>School Class Progress - SMK Negeri 1 Bantul</title>
  <link rel="icon" type="image/png" href="{{ str_replace(' ', '%20', asset('images/logo-smk1bantul.png')) }}">

  <!-- Google Fonts: Plus Jakarta Sans -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

  <!-- Bootstrap 5.3 CSS & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

  <style>
    :root {
      --font-sans: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      --font-mono: 'JetBrains Mono', monospace;

      --primary: #2563eb;
      --primary-hover: #1d4ed8;
      --primary-dark: #1e40af;
      --primary-light: #eff6ff;
      --primary-border: #bfdbfe;

      --slate-50: #f8fafc;
      --slate-100: #f1f5f9;
      --slate-200: #e2e8f0;
      --slate-300: #cbd5e1;
      --slate-400: #94a3b8;
      --slate-500: #64748b;
      --slate-600: #475569;
      --slate-700: #334155;
      --slate-800: #1e293b;
      --slate-900: #0f172a;

      --emerald: #10b981;
      --emerald-light: #ecfdf5;
      --amber: #f59e0b;
      --amber-light: #fffbeb;

      --radius-sm: 8px;
      --radius-md: 12px;
      --radius-lg: 18px;
      --radius-xl: 24px;
    }

    *, *::before, *::after {
      box-sizing: border-box;
    }

    html {
      scroll-behavior: smooth;
    }

    body {
      font-family: var(--font-sans);
      background-color: #ffffff;
      color: var(--slate-800);
      margin: 0;
      overflow-x: hidden;
      -webkit-font-smoothing: antialiased;
      line-height: 1.6;
    }

    /* Subtle background grid & ambient light */
    .hero-glow-bg {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 720px;
      background: radial-gradient(circle at 50% -20%, rgba(37, 99, 235, 0.12) 0%, rgba(248, 250, 252, 0.6) 50%, transparent 80%),
                  radial-gradient(circle at 85% 15%, rgba(14, 165, 233, 0.08) 0%, transparent 40%),
                  radial-gradient(circle at 15% 25%, rgba(99, 102, 241, 0.08) 0%, transparent 40%);
      pointer-events: none;
      z-index: 0;
    }

    .grid-pattern {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 720px;
      background-image: radial-gradient(rgba(148, 163, 184, 0.18) 1px, transparent 1px);
      background-size: 28px 28px;
      mask-image: linear-gradient(to bottom, black 40%, transparent 100%);
      -webkit-mask-image: linear-gradient(to bottom, black 40%, transparent 100%);
      pointer-events: none;
      z-index: 0;
    }

    /* Navbar */
    .scp-navbar {
      background: rgba(255, 255, 255, 0.88);
      backdrop-filter: blur(14px);
      -webkit-backdrop-filter: blur(14px);
      border-bottom: 1px solid rgba(226, 232, 240, 0.8);
      position: sticky;
      top: 0;
      z-index: 1000;
      transition: all 0.25s ease;
    }

    .brand-group {
      display: flex;
      align-items: center;
      gap: 12px;
      text-decoration: none;
    }

    .brand-logo {
      width: 44px;
      height: 44px;
      object-fit: contain;
      filter: drop-shadow(0 2px 6px rgba(0,0,0,0.06));
    }

    .brand-text-title {
      font-size: 1.12rem;
      font-weight: 800;
      letter-spacing: -0.025em;
      color: var(--slate-900);
      line-height: 1.15;
    }

    .brand-text-sub {
      font-size: 0.73rem;
      font-weight: 600;
      color: var(--primary);
      letter-spacing: 0.02em;
      text-transform: uppercase;
    }

    .nav-link-custom {
      color: var(--slate-600);
      font-weight: 600;
      font-size: 0.88rem;
      padding: 0.5rem 0.85rem;
      border-radius: var(--radius-sm);
      transition: all 0.2s ease;
      text-decoration: none;
    }

    .nav-link-custom:hover {
      color: var(--primary);
      background: rgba(37, 99, 235, 0.05);
    }

    /* Buttons */
    .btn-login-nav {
      background: var(--primary);
      color: #ffffff;
      border: 1px solid var(--primary);
      border-radius: var(--radius-sm);
      font-weight: 600;
      font-size: 0.88rem;
      padding: 0.5rem 1.15rem;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: all 0.2s ease;
      text-decoration: none;
      box-shadow: 0 4px 14px rgba(37, 99, 235, 0.2);
    }

    .btn-login-nav:hover {
      background: var(--primary-hover);
      color: #ffffff;
      transform: translateY(-1px);
      box-shadow: 0 6px 18px rgba(37, 99, 235, 0.28);
    }

    .btn-primary-hero {
      background: linear-gradient(135deg, #2563eb, #1d4ed8);
      color: #ffffff;
      font-weight: 700;
      font-size: 0.95rem;
      padding: 0.85rem 1.65rem;
      border-radius: var(--radius-md);
      border: none;
      display: inline-flex;
      align-items: center;
      gap: 10px;
      text-decoration: none;
      box-shadow: 0 10px 24px -4px rgba(37, 99, 235, 0.35);
      transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .btn-primary-hero:hover {
      background: linear-gradient(135deg, #1d4ed8, #1e40af);
      color: #ffffff;
      transform: translateY(-2px);
      box-shadow: 0 14px 28px -4px rgba(37, 99, 235, 0.45);
    }

    .btn-outline-hero {
      background: #ffffff;
      color: var(--slate-700);
      font-weight: 600;
      font-size: 0.95rem;
      padding: 0.85rem 1.4rem;
      border-radius: var(--radius-md);
      border: 1px solid var(--slate-200);
      display: inline-flex;
      align-items: center;
      gap: 8px;
      text-decoration: none;
      transition: all 0.2s ease;
    }

    .btn-outline-hero:hover {
      background: var(--slate-50);
      color: var(--slate-900);
      border-color: var(--slate-300);
    }

    /* Hero Typography */
    .hero-brand-kicker {
      font-size: clamp(1.15rem, 2vw, 1.4rem);
      font-weight: 800;
      letter-spacing: -0.015em;
      color: #2563eb;
      margin-bottom: 0.65rem;
      display: inline-block;
    }

    .hero-title {
      font-size: clamp(2rem, 4.2vw, 3.25rem);
      font-weight: 800;
      letter-spacing: -0.035em;
      line-height: 1.18;
      color: #000000;
      margin-top: 1.25rem;
      margin-bottom: 1.25rem;
      max-width: 820px;
      margin-left: auto;
      margin-right: auto;
    }

    .hero-description {
      font-size: clamp(0.95rem, 1.4vw, 1.12rem);
      color: var(--slate-600);
      line-height: 1.65;
      margin-bottom: 2rem;
      max-width: 620px;
      margin-left: auto;
      margin-right: auto;
    }

    /* Features Section */
    .features-section {
      padding: 6rem 0;
      background: #ffffff;
      position: relative;
    }

    .section-eyebrow {
      font-size: 0.8rem;
      font-weight: 800;
      color: var(--primary);
      text-transform: uppercase;
      letter-spacing: 0.08em;
      margin-bottom: 0.5rem;
    }

    .section-title {
      font-size: clamp(1.6rem, 3vw, 2.3rem);
      font-weight: 800;
      letter-spacing: -0.025em;
      color: var(--slate-900);
      margin-bottom: 0.85rem;
    }

    .section-subtitle {
      font-size: 0.98rem;
      color: var(--slate-500);
      max-width: 620px;
      margin: 0 auto 3rem auto;
    }

    .feature-card {
      background: #ffffff;
      border: 1px solid var(--slate-200);
      border-radius: var(--radius-lg);
      padding: 1.85rem;
      height: 100%;
      transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
      position: relative;
    }

    .feature-card:hover {
      border-color: var(--primary-border);
      transform: translateY(-4px);
      box-shadow: 0 16px 32px -8px rgba(37, 99, 235, 0.08);
    }

    .feature-name {
      font-size: 1.1rem;
      font-weight: 700;
      color: var(--slate-900);
      margin-bottom: 0.5rem;
    }

    .feature-text {
      font-size: 0.88rem;
      color: var(--slate-600);
      line-height: 1.55;
      margin-bottom: 0;
    }

    /* CTA Banner Section */
    .cta-banner-section {
      padding: 5rem 0;
      background: #ffffff;
    }

    .cta-card {
      background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
      border-radius: var(--radius-xl);
      padding: 3.5rem 2.5rem;
      color: #ffffff;
      position: relative;
      overflow: hidden;
      box-shadow: 0 24px 48px -12px rgba(15, 23, 42, 0.25);
    }

    .cta-glow {
      position: absolute;
      top: -100px;
      right: -100px;
      width: 320px;
      height: 320px;
      background: radial-gradient(circle, rgba(37, 99, 235, 0.35) 0%, transparent 70%);
      pointer-events: none;
    }

    .cta-title {
      font-size: clamp(1.75rem, 3.2vw, 2.5rem);
      font-weight: 800;
      letter-spacing: -0.03em;
      margin-bottom: 1rem;
      line-height: 1.2;
    }

    .cta-text {
      font-size: 1.05rem;
      color: var(--slate-300);
      max-width: 580px;
      margin-bottom: 2rem;
      line-height: 1.6;
    }

    /* Footer */
    .scp-footer {
      background: var(--slate-900);
      color: var(--slate-400);
      padding: 3.5rem 0 2rem 0;
      font-size: 0.85rem;
      border-top: 1px solid var(--slate-800);
    }

    .footer-brand {
      color: #ffffff;
      font-weight: 800;
      font-size: 1.15rem;
      margin-bottom: 0.5rem;
    }

    .footer-link {
      color: var(--slate-400);
      text-decoration: none;
      transition: color 0.2s ease;
    }

    .footer-link:hover {
      color: #ffffff;
    }

    .footer-divider {
      border-color: var(--slate-800);
      margin: 2rem 0 1.5rem 0;
    }

    @media (max-width: 991px) {
      .mockup-wrapper {
        margin-top: 2.5rem;
      }
      .hero-title {
        text-align: center;
      }
      .hero-description {
        text-align: center;
        margin-left: auto;
        margin-right: auto;
      }
      .hero-actions {
        justify-content: center !important;
      }
    }
  </style>
</head>
<body>

  <!-- Background Gradients -->
  <div class="hero-glow-bg"></div>
  <div class="grid-pattern"></div>

  <!-- Navbar -->
  <header class="scp-navbar">
    <div class="container py-2">
      <div class="d-flex align-items-center justify-content-between">
        <!-- Brand -->
        <a href="{{ route('landing') }}" class="brand-group">
          <img src="{{ asset('images/logo-smk1bantul.png') }}" alt="Logo SMK N 1 Bantul" class="brand-logo">
          <div>
            <div class="brand-text-title">School Class Progress</div>
            <div class="brand-text-sub">SMK Negeri 1 Bantul</div>
          </div>
        </a>

        <!-- Right Side: Navigation Links & CTA Login Action -->
        <div class="d-flex align-items-center gap-2 gap-md-3">
          <nav class="d-none d-md-flex align-items-center gap-1 me-1">
            <a href="#fitur" class="nav-link-custom">Fitur Unggulan</a>
            <a href="#tentang" class="nav-link-custom">Tentang SCP</a>
          </nav>

          @auth
            <a href="{{ route('waka.dashboard') }}" class="btn-login-nav">
              <span>Dashboard</span>
            </a>
          @else
            <a href="{{ route('login') }}" class="btn-login-nav">
              <span>Masuk Portal</span>
            </a>
          @endauth
        </div>
      </div>
    </div>
  </header>

  <!-- Hero Section -->
  <section class="position-relative pt-5 pb-5 pt-lg-6 pb-lg-6 text-center" id="beranda" style="z-index: 1;">
    <div class="container py-3">
      <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-9">
          
          <div class="hero-brand-kicker">School Class Progress</div>

          <h1 class="hero-title mt-0 text-black">
            Digitalisasi KBM Blok, Jurnal & Presensi Kelas Terintegrasi
          </h1>

          <p class="hero-description">
            Platform modern untuk monitoring jadwal KBM blok otomatis, pengisian jurnal mengajar harian, serta presensi kehadiran siswa terpusat secara real-time.
          </p>

          <div class="hero-actions d-flex flex-wrap align-items-center justify-content-center gap-3">
            @auth
              <a href="{{ route('waka.dashboard') }}" class="btn-primary-hero">
                <span>Buka Dashboard Utama</span>
              </a>
              <a href="{{ route('logout') }}" class="btn-outline-hero">
                <span>Keluar Akun</span>
              </a>
            @else
              <a href="javascript:void(0)" class="btn-primary-hero" data-bs-toggle="modal" data-bs-target="#downloadApkModal">
                <i class="bi bi-android2 fs-5"></i>
                <span>Unduh Aplikasi SCP</span>
                <span class="badge bg-warning text-dark ms-1" style="font-size: 0.7rem; font-weight: 700; padding: 4px 9px; border-radius: 999px;">Coming Soon</span>
              </a>
              <a href="#fitur" class="btn-outline-hero">
                <span>Pelajari Fitur</span>
              </a>
            @endauth
          </div>

          <!-- Quick Trust Metrics -->
          <div class="d-inline-flex flex-wrap align-items-center justify-content-center gap-4 mt-5 pt-4 border-top" style="border-color: rgba(226, 232, 240, 0.8) !important; max-width: 600px;">
            <div class="px-2">
              <div class="fw-bold text-dark fs-4 mb-0">1.700+</div>
              <div class="text-muted small" style="font-size: 0.8rem;">Siswa Terdata</div>
            </div>
            <div class="d-none d-sm-block" style="width: 1px; height: 32px; background: #e2e8f0;"></div>
            <div class="px-2">
              <div class="fw-bold text-dark fs-4 mb-0">48</div>
              <div class="text-muted small" style="font-size: 0.8rem;">Rombongan Belajar</div>
            </div>
            <div class="d-none d-sm-block" style="width: 1px; height: 32px; background: #e2e8f0;"></div>
            <div class="px-2">
              <div class="fw-bold text-dark fs-4 mb-0">100%</div>
              <div class="text-muted small" style="font-size: 0.8rem;">KBM Real-Time</div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </section>

  <!-- Key Features Section -->
  <section class="features-section" id="fitur">
    <div class="container">
      <div class="text-center">
        <div class="section-eyebrow">Efisiensi & Inovasi KBM</div>
        <h2 class="section-title">Fitur Unggulan School Class Progress</h2>
        <p class="section-subtitle">
          Dirancang khusus untuk mendukung ritme pembelajaran blok di SMK Negeri 1 Bantul agar setiap proses belajar mengajar terdokumentasi akurat dan transparan.
        </p>
      </div>

      <div class="row g-4">
        
        <!-- Feature 1 -->
        <div class="col-md-6 col-lg-4">
          <div class="feature-card">
            <h3 class="feature-name">Jadwal Blok Otomatis</h3>
            <p class="feature-text">
              Sinkronisasi putaran blok KBM berkala secara terpusat, mengeliminasi potensi jadwal bentrok antar guru mapel, jam pelajaran, dan ruang kelas.
            </p>
          </div>
        </div>

        <!-- Feature 2 -->
        <div class="col-md-6 col-lg-4">
          <div class="feature-card">
            <h3 class="feature-name">Jurnal Mengajar Real-Time</h3>
            <p class="feature-text">
              Guru mengisi materi pokok ajar, capaian kompetensi, dan catatan kelas langsung selesai sesi mengajar tanpa perlu rekonsiliasi manual yang rumit.
            </p>
          </div>
        </div>

        <!-- Feature 3 -->
        <div class="col-md-6 col-lg-4">
          <div class="feature-card">
            <h3 class="feature-name">Presensi Siswa Presisi</h3>
            <p class="feature-text">
              Pencatatan status Hadir, Sakit, Izin, dan Alpa per jam pelajaran dengan visualisasi persentase kehadiran yang langsung terhubung ke database.
            </p>
          </div>
        </div>

        <!-- Feature 4 -->
        <div class="col-md-6 col-lg-4">
          <div class="feature-card">
            <h3 class="feature-name">Monitoring & Rekap Waka</h3>
            <p class="feature-text">
              Waka Kurikulum memiliki visibilitas penuh memantau keterlaksanaan KBM, jurnal guru, dan data kehadiran siswa se-sekolah dari satu dashboard.
            </p>
          </div>
        </div>

        <!-- Feature 5 -->
        <div class="col-md-6 col-lg-4">
          <div class="feature-card">
            <h3 class="feature-name">Manajemen Massal Cepat</h3>
            <p class="feature-text">
              Impor dan ekspor ribuan data akun siswa, orang tua, guru, dan rombel dalam hitungan detik dengan algoritma generator username dan NIP/NIS yang rapi.
            </p>
          </div>
        </div>

        <!-- Feature 6 -->
        <div class="col-md-6 col-lg-4">
          <div class="feature-card">
            <h3 class="feature-name">Keamanan OTP & Multi-Peran</h3>
            <p class="feature-text">
              Proteksi akun berbasis role privilege dengan verifikasi OTP cepat untuk reset password, menjamin privasi data nilai dan kehadiran peserta didik.
            </p>
          </div>
        </div>

      </div>
    </div>
  </section>

  <!-- CTA Banner Section -->
  <section class="cta-banner-section" id="tentang">
    <div class="container">
      <div class="cta-card">
        <div class="cta-glow"></div>
        <div class="row align-items-center position-relative" style="z-index: 1;">
          <div class="col-lg-8">
            <h2 class="cta-title">Siap Memulai Aktivitas Pembelajaran Hari Ini?</h2>
            <p class="cta-text">
              Masuk dengan akun yang telah didaftarkan oleh Waka Kurikulum SMK Negeri 1 Bantul. Pengelolaan KBM yang tertib menghasilkan generasi kompeten dan berprestasi.
            </p>
          </div>
          <div class="col-lg-4 text-lg-end">
            @auth
              <a href="{{ route('waka.dashboard') }}" class="btn-primary-hero px-4 py-3" style="font-size: 1rem;">
                <span>Ke Dashboard Waka</span>
              </a>
            @else
              <div class="d-flex flex-column flex-sm-row gap-2 justify-content-lg-end">
                <a href="javascript:void(0)" class="btn-primary-hero px-4 py-3" data-bs-toggle="modal" data-bs-target="#downloadApkModal" style="font-size: 1rem;">
                  <i class="bi bi-android2 me-1"></i>
                  <span>Unduh Aplikasi SCP (.APK)</span>
                </a>
              </div>
            @endauth
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Modal Unduh APK Coming Soon -->
  <div class="modal fade" id="downloadApkModal" tabindex="-1" aria-labelledby="downloadApkModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg text-start" style="border-radius: 16px; overflow: hidden;">
        <div class="modal-header border-0 pb-0 pt-4 px-4 justify-content-end">
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body text-center px-4 pb-4 pt-0">
          <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary mb-3" style="width: 72px; height: 72px;">
            <i class="bi bi-android2" style="font-size: 2.3rem;"></i>
          </div>
          <h4 class="fw-bold text-dark mb-1" id="downloadApkModalLabel">Aplikasi Mobile SCP</h4>
          <div class="mb-3">
            <span class="badge bg-warning text-dark px-3 py-2 fw-semibold" style="border-radius: 20px; font-size: 0.82rem;">
              <i class="bi bi-clock-history me-1"></i>Segera Hadir (Coming Soon)
            </span>
          </div>
          <p class="text-secondary small mb-3" style="line-height: 1.6;">
            Aplikasi mobile Android (format <strong>.APK</strong>) untuk <strong>Guru, Wali Kelas, Siswa, dan Orang Tua</strong> saat ini sedang dalam tahap akhir penyempurnaan fitur dan uji coba kestabilan sistem.
          </p>
          <div class="p-3 bg-light rounded-3 text-start small mb-3 border">
            <div class="d-flex align-items-center gap-2 mb-2 text-dark fw-bold">
              <i class="bi bi-info-circle text-primary"></i>
              <span>Peruntukan Platform:</span>
            </div>
            <ul class="mb-0 ps-3 text-secondary" style="font-size: 0.83rem;">
              <li><strong>Siswa & Orang Tua:</strong> Mengakses jadwal KBM, riwayat presensi harian, dan pengajuan surat izin sakit/izin.</li>
              <li><strong>Guru & Wali Kelas:</strong> Mengisi jurnal mengajar harian, checklist presensi siswa, dan verifikasi izin resmi.</li>
              <li><strong>Waka Kurikulum:</strong> Manajemen jadwal KBM blok dan master data diakses via <strong>Portal Web Waka</strong>.</li>
            </ul>
          </div>
          <div class="d-grid gap-2">
            <a href="{{ route('login') }}" class="btn btn-outline-primary btn-sm py-2">
              <i class="bi bi-box-arrow-in-right me-1"></i>Buka Portal Web Waka Kurikulum
            </a>
            <button type="button" class="btn btn-secondary btn-sm py-2" data-bs-dismiss="modal">Tutup</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <footer class="scp-footer">
    <div class="container">
      <div class="row g-4">
        
        <div class="col-lg-5">
          <div class="d-flex align-items-center gap-2 mb-3">
            <img src="{{ asset('images/logo-smk1bantul.png') }}" alt="Logo SMK N 1 Bantul" style="width: 38px; height: 38px; object-fit: contain;">
            <div class="footer-brand mb-0">School Class Progress</div>
          </div>
          <p class="small text-slate-400 mb-2" style="max-width: 380px; line-height: 1.6;">
            Sistem Informasi Akademik dan Progres Pembelajaran Terpadu SMK Negeri 1 Bantul (SKANSABA).
          </p>
          <div class="small text-slate-500">
            Jl. Parangtritis Km. 11, Dukuh, Sabdodadi, Kec. Bantul, Kabupaten Bantul, D.I. Yogyakarta 55715
          </div>
        </div>

        <div class="col-6 col-lg-3">
          <div class="text-white fw-bold mb-3" style="font-size: 0.9rem;">Navigasi Cepat</div>
          <ul class="list-unstyled d-flex flex-column gap-2 small">
            <li><a href="#beranda" class="footer-link">Beranda Utama</a></li>
            <li><a href="#fitur" class="footer-link">Fitur Unggulan</a></li>
            <li><a href="{{ route('login') }}" class="footer-link">Halaman Login</a></li>
          </ul>
        </div>

        <div class="col-6 col-lg-4">
          <div class="text-white fw-bold mb-3" style="font-size: 0.9rem;">Bantuan & Layanan</div>
          <ul class="list-unstyled d-flex flex-column gap-2 small">
            <li><span class="text-slate-400">skansaba@smkn1bantul.sch.id</span></li>
            <li><span class="text-slate-400">(0274) 367156</span></li>
            <li><a href="https://www.smkn1bantul.sch.id" target="_blank" rel="noopener" class="footer-link">www.smkn1bantul.sch.id</a></li>
          </ul>
        </div>

      </div>

      <hr class="footer-divider">

      <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2 small text-slate-500">
        <div>&copy; {{ date('Y') }} SMK Negeri 1 Bantul &bull; Hak Cipta Dilindungi.</div>
        <div>School Class Progress (SCP) v2.0</div>
      </div>
    </div>
  </footer>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Dashboard') - School Class Progress</title>
<link rel="icon" type="image/png" href="{{ asset('images/logo-smk1bantul.png') }}">

<!-- Google Fonts: Plus Jakarta Sans & JetBrains Mono -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

<!-- Bootstrap 5.3 CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>
:root {
  --bs-font-sans-serif: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
  --font-sans: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
  --font-mono: 'JetBrains Mono', 'Fira Code', 'Courier New', monospace;

  /* Color Palette - Clean White Aesthetic */
  --header-h: 56px;
  --sidebar-w: 250px;
  --bg-page: #f7f7f8;
  --sidebar-bg: #ffffff;
  --card-border: #e4e4e7;
  --border-light: #f1f1f3;

  --text-900: #111827;
  --text-700: #374151;
  --text-500: #6b7280;
  --text-400: #9ca3af;
  --text-primary: #111827;
  --text-secondary: #4b5563;
  --text-muted: #6b7280;

  --blue-600: #2563eb;
  --blue-700: #1d4ed8;
  --blue-50: #eff6ff;
  --blue-100: #dbeafe;

  --r-sm: 4px;
  --r-md: 8px;
  --r-lg: 12px;
  --shadow-card: 0 1px 2px 0 rgba(0, 0, 0, .05), 0 0 0 1px rgba(0, 0, 0, .04);
}

*, *::before, *::after {
  box-sizing: border-box;
}

body {
  font-family: var(--font-sans);
  background-color: var(--bg-page);
  color: var(--text-900);
  min-height: 100vh;
  margin: 0;
  -webkit-font-smoothing: antialiased;
}

/* ============================================================
   TOP HEADER
============================================================ */
.top-header {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  height: var(--header-h);
  background-color: #ffffff;
  border-bottom: 1px solid var(--card-border);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 24px;
  z-index: 1030;
}

.header-brand {
  display: flex;
  align-items: center;
  gap: 10px;
  text-decoration: none;
  color: var(--text-900);
}

.brand-title {
  font-size: 15px;
  font-weight: 700;
  letter-spacing: -0.01em;
  color: var(--text-900);
  margin: 0;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 12px;
}

.header-user-info {
  text-align: right;
  line-height: 1.25;
}

.header-user-name {
  font-size: 13px;
  font-weight: 600;
  color: var(--text-900);
}

.header-user-role {
  font-size: 11px;
  color: var(--text-500);
}

/* Black circle avatar with WA or user initials */
.avatar-circle {
  width: 34px;
  height: 34px;
  background-color: #111827;
  color: #ffffff;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.02em;
  cursor: pointer;
  transition: opacity 0.15s ease;
  user-select: none;
  flex-shrink: 0;
}

.avatar-circle:hover {
  opacity: 0.9;
}

/* ============================================================
   SIDEBAR
============================================================ */
#sidebar-wrapper {
  position: fixed;
  top: var(--header-h);
  left: 0;
  bottom: 0;
  width: var(--sidebar-w);
  background-color: #ffffff;
  border-right: 1px solid var(--card-border);
  overflow-y: auto;
  padding: 14px 0 24px;
  z-index: 1020;
  transition: transform 0.25s ease-out;
  scrollbar-width: thin;
  scrollbar-color: #e4e4e7 transparent;
  display: flex;
  flex-direction: column;
}

#sidebar-wrapper::-webkit-scrollbar {
  width: 5px;
}
#sidebar-wrapper::-webkit-scrollbar-thumb {
  background: #e4e4e7;
  border-radius: 4px;
}

.sidebar-direct {
  padding: 0 12px 6px;
}

.sidebar-group {
  margin-bottom: 2px;
}

.sidebar-group-toggle {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 8px 14px 6px;
  background: none;
  border: none;
  color: var(--text-500);
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .07em;
  cursor: pointer;
  transition: color .15s ease;
}

.sidebar-group-toggle:hover {
  color: var(--text-700);
}

.sidebar-group-toggle .chevron {
  transition: transform .2s ease;
  font-size: 11px;
}

.sidebar-group.collapsed .chevron {
  transform: rotate(-90deg);
}

.sidebar-group.collapsed .sidebar-group-body {
  display: none;
}

.sidebar-items {
  padding: 0 12px 4px;
}

.nav-item-link {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 12px;
  border-radius: var(--r-md);
  color: var(--text-700);
  font-size: 13px;
  font-weight: 500;
  text-decoration: none;
  transition: background-color .12s ease, color .12s ease;
  cursor: pointer;
  border: none;
  background: none;
  width: 100%;
  text-align: left;
}

.nav-item-link:hover {
  background-color: #f4f4f5;
  color: var(--text-900);
}

.nav-item-link.active {
  background-color: var(--blue-50);
  color: var(--blue-700);
  font-weight: 600;
}

.nav-item-link i {
  font-size: 15px;
  width: 18px;
  text-align: center;
  flex-shrink: 0;
  color: var(--text-500);
  transition: color .12s ease;
}

.nav-item-link:hover i {
  color: var(--text-700);
}

.nav-item-link.active i {
  color: var(--blue-700);
}

.sidebar-footer {
  margin-top: auto;
  padding: 12px;
  border-top: 1px solid var(--border-light);
}

/* ============================================================
   PAGE CONTENT WRAPPER
============================================================ */
#page-content-wrapper {
  margin-left: var(--sidebar-w);
  padding-top: var(--header-h);
  min-height: 100vh;
  background-color: var(--bg-page);
  display: flex;
  flex-direction: column;
}

.main-content {
  padding: 24px 28px 48px;
  flex: 1;
}

/* Breadcrumb */
.page-breadcrumb {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 8px;
  font-size: 12px;
  color: var(--text-500);
}
.page-breadcrumb a {
  color: var(--text-500);
  text-decoration: none;
}
.page-breadcrumb a:hover {
  color: var(--blue-600);
}
.page-breadcrumb .sep {
  color: var(--text-400);
}
.page-breadcrumb .current {
  color: var(--text-700);
  font-weight: 500;
}

/* Heading Typography */
.heading-section {
  font-size: 1.5rem;
  font-weight: 800;
  letter-spacing: -0.025em;
  color: var(--text-900);
  line-height: 1.25;
}

/* Card Overrides */
.card {
  border: 1px solid var(--card-border);
  border-radius: var(--r-lg);
  box-shadow: var(--shadow-card);
  background-color: #ffffff;
}

.card-header {
  background-color: #ffffff;
  border-bottom: 1px solid var(--card-border);
  font-weight: 600;
  padding: 1rem 1.25rem;
}

.font-mono {
  font-family: var(--font-mono) !important;
}

/* Responsive Sidebar Mobile */
@media (max-width: 991.98px) {
  #sidebar-wrapper {
    transform: translateX(-100%);
  }
  #sidebar-wrapper.toggled {
    transform: translateX(0);
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15);
  }
  #page-content-wrapper {
    margin-left: 0;
  }
  .sidebar-backdrop {
    display: none;
    position: fixed;
    top: var(--header-h);
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.3);
    z-index: 1015;
  }
  .sidebar-backdrop.show {
    display: block;
  }
}
</style>
@stack('styles')
</head>
<body>

@php
  $authUser = Auth::user();
  $userInitials = strtoupper(substr($authUser->fullname ?? $authUser->username ?? 'WA', 0, 2));
@endphp

<!-- ============================================================
     TOP HEADER BAR
============================================================ -->
<header class="top-header">
  <div class="d-flex align-items-center gap-2">
    <!-- Hamburger Button (Mobile Only) -->
    <button class="btn btn-sm btn-light border-0 d-lg-none p-1 text-secondary" id="sidebarToggle" aria-label="Toggle Sidebar">
      <i class="bi bi-list fs-4"></i>
    </button>

    <a href="{{ route('waka.dashboard') }}" class="header-brand">
      <span class="brand-title">School Class Progress</span>
    </a>
  </div>

  <div class="header-right">
    <div class="header-user-info d-none d-sm-block">
      <div class="header-user-name">{{ $authUser->fullname ?? 'Waka Kurikulum' }}</div>
      <div class="header-user-role">Waka Kurikulum</div>
    </div>

    <!-- User Avatar Dropdown -->
    <div class="dropdown">
      <div class="avatar-circle" id="userMenuDropdown" data-bs-toggle="dropdown" aria-expanded="false" title="{{ $authUser->fullname ?? 'Waka Kurikulum' }}">
        {{ $userInitials }}
      </div>
      <ul class="dropdown-menu dropdown-menu-end shadow-sm border py-2" aria-labelledby="userMenuDropdown" style="border-radius: 10px; min-width: 200px;">
        <li class="px-3 py-1">
          <div class="fw-bold text-dark small">{{ $authUser->fullname ?? 'Waka Kurikulum' }}</div>
          <div class="text-muted small" style="font-size: 0.75rem;">{{ $authUser->email ?? ($authUser->username ?? 'admin') }}</div>
        </li>
        <li><hr class="dropdown-divider my-2"></li>
        <li>
          <a class="dropdown-item small d-flex align-items-center gap-2 py-2" href="{{ route('waka.dashboard') }}">
            <i class="bi bi-grid text-muted"></i> Dashboard
          </a>
        </li>
        <li>
          <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="dropdown-item small text-danger d-flex align-items-center gap-2 py-2">
              <i class="bi bi-box-arrow-right"></i> Keluar Sistem
            </button>
          </form>
        </li>
      </ul>
    </div>
  </div>
</header>

<!-- Mobile Sidebar Backdrop -->
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<!-- ============================================================
     WHITE SIDEBAR (COLLAPSIBLE GROUPS)
============================================================ -->
<aside id="sidebar-wrapper">
  <!-- Direct Top Item: Dashboard -->
  <div class="sidebar-direct">
    <a href="{{ route('waka.dashboard') }}" class="nav-item-link {{ request()->routeIs('waka.dashboard') ? 'active' : '' }}">
      <i class="bi bi-grid"></i>
      <span>Dashboard</span>
    </a>
  </div>

  <!-- Group 1: Manajemen Akademik -->
  <div class="sidebar-group" id="grp-akademik">
    <button class="sidebar-group-toggle" type="button" onclick="toggleGroup('grp-akademik')">
      <span>Manajemen Akademik</span>
      <i class="bi bi-chevron-down chevron"></i>
    </button>
    <div class="sidebar-group-body">
      <div class="sidebar-items">
        <a href="{{ route('waka.journals.index') }}" class="nav-item-link {{ request()->routeIs('waka.journals.*') ? 'active' : '' }}">
          <i class="bi bi-journal-text"></i>
          <span>Jurnal Mengajar</span>
        </a>
        <a href="{{ route('waka.schedules.index') }}" class="nav-item-link {{ request()->routeIs('waka.schedules.*') ? 'active' : '' }}">
          <i class="bi bi-calendar3"></i>
          <span>Jadwal Mengajar</span>
        </a>
        <a href="{{ route('waka.attendances.recap') }}" class="nav-item-link {{ request()->routeIs('waka.attendances.*') ? 'active' : '' }}">
          <i class="bi bi-clipboard2-pulse"></i>
          <span>Rekap Presensi</span>
        </a>
      </div>
    </div>
  </div>

  <!-- Group 2: Data Master -->
  <div class="sidebar-group" id="grp-master">
    <button class="sidebar-group-toggle" type="button" onclick="toggleGroup('grp-master')">
      <span>Data Master</span>
      <i class="bi bi-chevron-down chevron"></i>
    </button>
    <div class="sidebar-group-body">
      <div class="sidebar-items">
        <a href="{{ route('waka.users.index') }}" class="nav-item-link {{ request()->routeIs('waka.users.*') ? 'active' : '' }}">
          <i class="bi bi-people"></i>
          <span>Pengguna</span>
        </a>
        <a href="{{ route('waka.classes.index') }}" class="nav-item-link {{ request()->routeIs('waka.classes.*') ? 'active' : '' }}">
          <i class="bi bi-mortarboard"></i>
          <span>Kelas</span>
        </a>
        <a href="{{ route('waka.promotions.index') }}" class="nav-item-link {{ request()->routeIs('waka.promotions.*') ? 'active' : '' }}">
          <i class="bi bi-arrow-up-right-circle"></i>
          <span>Kenaikan Kelas</span>
        </a>
        <a href="{{ route('waka.subjects.index') }}" class="nav-item-link {{ request()->routeIs('waka.subjects.*') ? 'active' : '' }}">
          <i class="bi bi-book"></i>
          <span>Mata Pelajaran</span>
        </a>
        <a href="{{ route('waka.rooms.index') }}" class="nav-item-link {{ request()->routeIs('waka.rooms.*') ? 'active' : '' }}">
          <i class="bi bi-door-closed"></i>
          <span>Ruangan</span>
        </a>
      </div>
    </div>
  </div>

  <!-- Group 3: Settings -->
  <div class="sidebar-group" id="grp-settings">
    <button class="sidebar-group-toggle" type="button" onclick="toggleGroup('grp-settings')">
      <span>Settings</span>
      <i class="bi bi-chevron-down chevron"></i>
    </button>
    <div class="sidebar-group-body">
      <div class="sidebar-items">
        <a href="{{ route('waka.cycles.index') }}" class="nav-item-link {{ request()->routeIs('waka.cycles.*') ? 'active' : '' }}">
          <i class="bi bi-arrow-repeat"></i>
          <span>Siklus Putaran</span>
        </a>
      </div>
    </div>
  </div>

  <!-- Sidebar Footer: Keluar Sistem -->
  <div class="sidebar-footer">
    <form action="{{ route('logout') }}" method="POST" id="logout-form">
      @csrf
      <button type="submit" class="nav-item-link text-danger border-0 bg-transparent w-100 text-start">
        <i class="bi bi-box-arrow-right text-danger"></i>
        <span>Keluar Sistem</span>
      </button>
    </form>
  </div>
</aside>

<!-- ============================================================
     MAIN PAGE CONTENT WRAPPER
============================================================ -->
<div id="page-content-wrapper">
  <main class="main-content">
    {{-- Breadcrumb Support --}}
    @hasSection('breadcrumb')
      <nav class="page-breadcrumb" aria-label="breadcrumb">
        @yield('breadcrumb')
      </nav>
    @endif

    {{-- Flash Notifications --}}
    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show border-0 d-flex align-items-center mb-4" role="alert" style="background-color: #ecfdf5; color: #065f46; border-radius: 8px;">
        <i class="bi bi-check-circle-fill me-2 fs-5 text-success"></i>
        <div>{{ session('success') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    @if(session('info'))
      <div class="alert alert-info alert-dismissible fade show border-0 d-flex align-items-center mb-4" role="alert" style="background-color: #eff6ff; color: #1e40af; border-radius: 8px;">
        <i class="bi bi-info-circle-fill me-2 fs-5 text-primary"></i>
        <div>{{ session('info') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    @if(session('error'))
      <div class="alert alert-danger alert-dismissible fade show border-0 d-flex align-items-center mb-4" role="alert" style="background-color: #fef2f2; color: #991b1b; border-radius: 8px;">
        <i class="bi bi-exclamation-triangle-fill me-2 fs-5 text-danger"></i>
        <div>{{ session('error') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    @if(session('warning'))
      <div class="alert alert-warning alert-dismissible fade show border-0 d-flex align-items-center mb-4" role="alert" style="background-color: #fffbeb; color: #92400e; border-radius: 8px;">
        <i class="bi bi-shield-lock-fill me-2 fs-5 text-warning"></i>
        <div>{{ session('warning') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    @if($errors->any())
      <div class="alert alert-danger alert-dismissible fade show border-0 mb-4" role="alert" style="background-color: #fef2f2; color: #991b1b; border-radius: 8px;">
        <div class="d-flex align-items-center mb-1">
          <i class="bi bi-exclamation-triangle-fill me-2 fs-5 text-danger"></i>
          <strong class="fw-semibold">Terjadi Kesalahan:</strong>
        </div>
        <ul class="mb-0 ps-4 small">
          @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    @yield('content')
  </main>
</div>

<!-- ============================================================
     MODAL: PERINGATAN JADWAL TERKUNCI
============================================================ -->
<div class="modal fade" id="scheduleLockedModal" tabindex="-1" aria-labelledby="scheduleLockedModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
      <div class="modal-header border-0 bg-danger bg-opacity-10 pt-4 px-4 pb-2">
        <div class="d-flex align-items-center gap-3">
          <div class="rounded-circle bg-danger text-white d-flex align-items-center justify-content-center shadow-sm" style="width: 48px; height: 48px; min-width: 48px; font-size: 1.4rem;">
            <i class="bi bi-lock-fill"></i>
          </div>
          <div>
            <h2 class="modal-title fw-bold text-danger mb-0 fs-5" id="scheduleLockedModalLabel">Jadwal Pelajaran Terkunci!</h2>
            <p class="text-muted small mb-0">Proteksi Keamanan Jadwal Pelajaran</p>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <div class="pb-3 mb-3 border-bottom">
          <div class="d-flex align-items-start gap-2">
            <i class="bi bi-shield-exclamation fs-5 flex-shrink-0 mt-1 text-danger"></i>
            <div class="small">
              <strong class="text-danger">Peringatan Sistem:</strong> Penambahan atau perubahan jadwal tidak diizinkan karena status jadwal pelajaran saat ini sedang <strong>DIKUNCI (FIX/FINAL)</strong>.
            </div>
          </div>
        </div>
        <p class="text-secondary small mb-3">
          Seluruh jadwal pelajaran untuk putaran aktif telah difinalisasi agar tidak terjadi perubahan yang membingungkan guru, siswa, maupun wali murid yang sedang mengikuti jadwal berjalan.
        </p>
        <div class="pb-1">
          <div class="d-flex align-items-center gap-2 mb-1">
            <i class="bi bi-key-fill text-secondary"></i>
            <span class="fw-semibold small text-dark">Cara Membuka Kunci:</span>
          </div>
          <p class="small text-muted mb-0 ps-4">
            Apabila terdapat perubahan kurikulum atau revisi jadwal yang sah, silakan buka kunci terlebih dahulu melalui panel <strong>Akses Pengisian Jadwal</strong> di <strong>Dashboard Kontrol Waka</strong>.
          </p>
        </div>
      </div>
      <div class="modal-footer border-0 bg-light p-3 d-flex justify-content-between">
        <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal">
          <i class="bi bi-x-circle me-1"></i> Mengerti & Tutup
        </button>
        <a href="{{ route('waka.dashboard') }}" class="btn btn-danger px-3 fw-semibold">
          <i class="bi bi-unlock-fill me-1"></i> Buka Kunci di Dashboard
        </a>
      </div>
    </div>
  </div>
</div>

<!-- Bootstrap 5.3 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Toggle Collapsible Sidebar Group
function toggleGroup(id) {
  const grp = document.getElementById(id);
  if (grp) {
    grp.classList.toggle('collapsed');
  }
}

// Show Coming Soon notification for items under development
function showComingSoon(name) {
  alert('Modul ' + name + ' sedang dalam tahap integrasi.');
}

document.addEventListener('DOMContentLoaded', function () {
  // Mobile Sidebar Toggle
  const sidebarToggle = document.getElementById('sidebarToggle');
  const sidebar = document.getElementById('sidebar-wrapper');
  const backdrop = document.getElementById('sidebarBackdrop');

  if (sidebarToggle && sidebar && backdrop) {
    sidebarToggle.addEventListener('click', function () {
      sidebar.classList.toggle('toggled');
      backdrop.classList.toggle('show');
    });

    backdrop.addEventListener('click', function () {
      sidebar.classList.remove('toggled');
      backdrop.classList.remove('show');
    });
  }

  // Ensure groups with active link are NOT collapsed
  document.querySelectorAll('.sidebar-group').forEach(function(grp) {
    if (grp.querySelector('.nav-item-link.active')) {
      grp.classList.remove('collapsed');
    }
  });
});
</script>
@stack('scripts')
@yield('scripts')
</body>
</html>

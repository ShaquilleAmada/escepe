@extends('layouts.auth')

@section('page_title', 'Login')

@section('card_content')
  <div class="auth-card-header">
    <h1 class="auth-card-title">Masuk ke Sistem</h1>
    <p class="auth-card-subtitle">Silakan masukkan username dan password akun Anda</p>
  </div>

  {{-- Error Alert --}}
  @if(isset($errors) && $errors->any())
    <div class="d-flex align-items-center gap-2 mb-3 py-1 text-danger small" style="font-size: 0.85rem; font-weight: 500;">
      <i class="bi bi-exclamation-circle-fill flex-shrink-0"></i>
      <div>{{ $errors->first() }}</div>
    </div>
  @endif

  {{-- Session Info (setelah logout) --}}
  @if(session('info'))
    <div class="d-flex align-items-center gap-2 mb-3 py-1 text-primary small" style="font-size: 0.85rem; font-weight: 500;">
      <i class="bi bi-info-circle-fill flex-shrink-0"></i>
      <div>{{ session('info') }}</div>
    </div>
  @endif

  {{-- Session Success (setelah registrasi) --}}
  @if(session('success'))
    <div class="d-flex align-items-center gap-2 mb-3 py-1 small" style="font-size: 0.85rem; font-weight: 500; color: #15803d;">
      <i class="bi bi-check-circle-fill flex-shrink-0"></i>
      <div>{{ session('success') }}</div>
    </div>
  @endif

  <!-- Login Form -->
  <form action="{{ route('login.post') }}" method="POST">
    @csrf

    <!-- Username Input -->
    <div class="form-group">
      <label for="username" class="form-label">Username</label>
      <div class="input-wrapper">
        <input 
          type="text" 
          class="form-control" 
          id="username" 
          name="username" 
          value="{{ old('username') }}" 
          placeholder="Masukkan username" 
          required 
          autofocus
          autocomplete="username"
        >
        <i class="bi bi-person input-icon"></i>
      </div>
    </div>

    <!-- Password Input -->
    <div class="form-group">
      <label for="password" class="form-label">Password</label>
      <div class="input-wrapper">
        <input 
          type="password" 
          class="form-control" 
          id="password" 
          name="password" 
          placeholder="Masukkan password" 
          required
          autocomplete="current-password"
          style="padding-right: 42px;"
        >
        <i class="bi bi-lock input-icon"></i>
        <button type="button" class="toggle-pwd-btn" id="togglePasswordBtn" title="Lihat Password" tabindex="-1">
          <i class="bi bi-eye" id="lockIcon"></i>
        </button>
      </div>
    </div>

    <!-- Options: Remember Me + Lupa Password -->
    <div class="form-options">
      <div class="form-check mb-0">
        <input class="form-check-input" type="checkbox" id="remember" name="remember">
        <label class="form-check-label" for="remember">
          Ingat Saya
        </label>
      </div>
      <a href="{{ route('password.forgot') }}" style="font-size: 0.83rem; color: var(--primary-blue); font-weight: 500; text-decoration: none;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">Lupa password?</a>
    </div>

    <!-- Submit Button -->
    <button type="submit" class="btn-login">
      <span>Masuk</span>
      <i class="bi bi-arrow-right"></i>
    </button>
  </form>

  <!-- Kembali ke Landing Page -->
  <div class="auth-switch-link">
    <a href="{{ route('landing') }}">
      <i class="bi bi-arrow-left"></i> Kembali ke halaman utama
    </a>
  </div>

@endsection

@section('scripts')
<!-- Toggle Password Visibility Script -->
<script>
const toggleBtn = document.getElementById('togglePasswordBtn');
const passwordInput = document.getElementById('password');
const lockIcon = document.getElementById('lockIcon');

if (toggleBtn && passwordInput && lockIcon) {
  toggleBtn.addEventListener('click', function() {
    if (passwordInput.type === 'password') {
      passwordInput.type = 'text';
      lockIcon.className = 'bi bi-eye-slash';
      toggleBtn.title = 'Sembunyikan Password';
    } else {
      passwordInput.type = 'password';
      lockIcon.className = 'bi bi-eye';
      toggleBtn.title = 'Lihat Password';
    }
  });
}
</script>
@endsection

@extends('layouts.auth')

@section('page_title', 'Perbarui Password')

@section('card_content')
  <div class="auth-card-header" style="text-align: center;">
    <h1 class="auth-card-title">Perbarui Password</h1>
    <p class="auth-card-subtitle">
      Silakan buat kata sandi baru untuk akun <strong>{{ $email ?? session('otp_email') }}</strong>
    </p>
  </div>

  {{-- Status notifikasi sukses verifikasi OTP --}}
  @if(session('status'))
    <p style="font-size: 0.84rem; color: #059669; text-align: center; margin-bottom: 1.25rem; line-height: 1.5; font-weight: 500;" role="status">
      {{ session('status') }}
    </p>
  @endif

  <form action="{{ route('password.reset.post') }}" method="POST" id="resetPasswordForm" novalidate>
    @csrf

    <!-- 1. Password Baru -->
    <div class="form-group">
      <label for="password" class="form-label">Password Baru</label>
      <div class="input-wrapper">
        <input
          type="password"
          class="form-control @error('password') is-invalid @enderror"
          id="password"
          name="password"
          placeholder="Masukkan minimal 8 karakter"
          required
          autofocus
          autocomplete="new-password"
          style="padding-right: 42px;"
          aria-describedby="password_error password_hint"
        >
        <i class="bi bi-lock input-icon"></i>
        <button type="button" class="toggle-pwd-btn" id="togglePwdBtn" title="Lihat Password" tabindex="-1">
          <i class="bi bi-eye" id="pwdIcon"></i>
        </button>
      </div>
      @error('password')
        <div class="field-error" id="password_error" role="alert">
          <i class="bi bi-exclamation-circle"></i>
          <span>{{ $message }}</span>
        </div>
      @enderror
      @if(!$errors->has('password'))
        <div class="input-hint" id="password_hint">Minimal 8 karakter kombinasi huruf/angka</div>
      @endif
    </div>

    <!-- 2. Konfirmasi Password Baru -->
    <div class="form-group" style="margin-bottom: 1.5rem;">
      <label for="password_confirmation" class="form-label">Konfirmasi Password Baru</label>
      <div class="input-wrapper">
        <input
          type="password"
          class="form-control @error('password_confirmation') is-invalid @enderror"
          id="password_confirmation"
          name="password_confirmation"
          placeholder="Ulangi password baru Anda"
          required
          autocomplete="new-password"
          style="padding-right: 42px;"
        >
        <i class="bi bi-lock-fill input-icon"></i>
        <button type="button" class="toggle-pwd-btn" id="toggleConfirmBtn" title="Lihat Password" tabindex="-1">
          <i class="bi bi-eye" id="confirmIcon"></i>
        </button>
      </div>
    </div>

    <!-- Submit Button -->
    <button type="submit" class="btn-login" id="btnSubmitPassword">
      <span>Simpan Password Baru</span>
      <i class="bi bi-check-lg"></i>
    </button>
  </form>

  <!-- Link Kembali ke Login -->
  <div class="auth-switch-link" style="margin-top: 1.25rem;">
    <a href="{{ route('login') }}" style="display: inline-flex; align-items: center; gap: 4px;">
      <i class="bi bi-arrow-left" style="font-size: 0.8rem;"></i>
      Batal dan kembali ke halaman login
    </a>
  </div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  // Toggle Password Baru
  const togglePwdBtn   = document.getElementById('togglePwdBtn');
  const passwordInput  = document.getElementById('password');
  const pwdIcon        = document.getElementById('pwdIcon');

  if (togglePwdBtn && passwordInput && pwdIcon) {
    togglePwdBtn.addEventListener('click', function () {
      if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        pwdIcon.className  = 'bi bi-eye-slash';
        togglePwdBtn.title = 'Sembunyikan Password';
      } else {
        passwordInput.type = 'password';
        pwdIcon.className  = 'bi bi-eye';
        togglePwdBtn.title = 'Lihat Password';
      }
    });
  }

  // Toggle Konfirmasi Password
  const toggleConfirmBtn   = document.getElementById('toggleConfirmBtn');
  const confirmInput       = document.getElementById('password_confirmation');
  const confirmIcon        = document.getElementById('confirmIcon');

  if (toggleConfirmBtn && confirmInput && confirmIcon) {
    toggleConfirmBtn.addEventListener('click', function () {
      if (confirmInput.type === 'password') {
        confirmInput.type      = 'text';
        confirmIcon.className  = 'bi bi-eye-slash';
        toggleConfirmBtn.title = 'Sembunyikan Password';
      } else {
        confirmInput.type      = 'password';
        confirmIcon.className  = 'bi bi-eye';
        toggleConfirmBtn.title = 'Lihat Password';
      }
    });
  }

  // Prevent multiple submits
  const form = document.getElementById('resetPasswordForm');
  const submitBtn = document.getElementById('btnSubmitPassword');
  if (form && submitBtn) {
    form.addEventListener('submit', function () {
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<span>Menyimpan Password...</span> <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
    });
  }
});
</script>
@endsection

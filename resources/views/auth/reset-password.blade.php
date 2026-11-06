@extends('layouts.auth')

@section('page_title', 'Reset Password')

@section('card_content')
  <div class="auth-card-header">
    <h1 class="auth-card-title">Reset Password</h1>
    <p class="auth-card-subtitle">
      @if(session('otp_email'))
        Masukkan kode OTP yang dikirim ke <strong>{{ session('otp_email') }}</strong>
      @else
        Masukkan kode verifikasi OTP dan password baru Anda
      @endif
    </p>
  </div>

  {{-- Status notifikasi pengiriman email --}}
  @if(session('status'))
    <div style="background-color: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 12px 14px; margin-bottom: 1.25rem; display: flex; align-items: flex-start; gap: 10px;" role="alert">
      <i class="bi bi-envelope-check-fill" style="color: #2563eb; font-size: 1.15rem; line-height: 1.3;"></i>
      <div style="font-size: 0.82rem; color: #1e40af; line-height: 1.45;">
        {{ session('status') }}
      </div>
    </div>
  @endif

  {{-- ================================================================
       KOTAK OTP (mode display lokal/dev) — tampil jika mode display aktif
       ================================================================ --}}
  @if(session('otp_code'))
    <div style="margin-bottom: 1.25rem; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 12px; text-align: center;" role="alert" aria-live="polite">
      <div style="font-size: 0.8rem; font-weight: 600; color: #1d4ed8; margin-bottom: 0.25rem;">[Mode Dev] Kode Verifikasi Layar</div>
      <div style="font-size: 1.6rem; font-weight: 800; color: #1e3a8a; letter-spacing: 0.18em; font-family: 'JetBrains Mono', monospace; line-height: 1.2;">
        {{ session('otp_code') }}
      </div>
      <div style="font-size: 0.75rem; color: #64748b; margin-top: 0.25rem;">
        Berlaku 10 menit. Masukkan kode di atas pada form di bawah.
      </div>
    </div>
  @endif

  {{-- Error global (selain per-field) --}}
  @if(isset($errors) && $errors->has('otp') && !$errors->has('password'))
    {{-- error ditampilkan per-field di bawah --}}
  @endif

  <form action="{{ route('password.reset.post') }}" method="POST" novalidate>
    @csrf

    <!-- Kode OTP -->
    <div class="form-group">
      <label for="otp" class="form-label">Kode Verifikasi</label>
      <div class="input-wrapper">
        <input
          type="text"
          class="form-control @error('otp') is-invalid @enderror"
          id="otp"
          name="otp"
          value="{{ old('otp') }}"
          placeholder="Masukkan 6 digit kode"
          required
          autofocus
          autocomplete="one-time-code"
          maxlength="6"
          inputmode="numeric"
          pattern="[0-9]{6}"
          aria-describedby="otp_error"
        >
        <i class="bi bi-shield-check input-icon"></i>
      </div>
      @error('otp')
        <div class="field-error" id="otp_error" role="alert">
          <i class="bi bi-exclamation-circle"></i>
          <span>{{ $message }}</span>
        </div>
      @enderror
    </div>

    <!-- Password Baru -->
    <div class="form-group">
      <label for="password" class="form-label">Password Baru</label>
      <div class="input-wrapper">
        <input
          type="password"
          class="form-control @error('password') is-invalid @enderror"
          id="password"
          name="password"
          placeholder="Masukkan password baru"
          required
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
        <div class="input-hint" id="password_hint">Minimal 8 karakter</div>
      @endif
    </div>

    <!-- Konfirmasi Password -->
    <div class="form-group" style="margin-bottom: 1.5rem;">
      <label for="password_confirmation" class="form-label">Konfirmasi Password Baru</label>
      <div class="input-wrapper">
        <input
          type="password"
          class="form-control @error('password') is-invalid @enderror"
          id="password_confirmation"
          name="password_confirmation"
          placeholder="Ulangi password baru"
          required
          autocomplete="new-password"
          style="padding-right: 42px;"
        >
        <i class="bi bi-lock input-icon"></i>
        <button type="button" class="toggle-pwd-btn" id="toggleConfirmBtn" title="Lihat Password" tabindex="-1">
          <i class="bi bi-eye" id="confirmIcon"></i>
        </button>
      </div>
    </div>

    <!-- Submit Button -->
    <button type="submit" class="btn-login">
      <span>Simpan Password</span>
      <i class="bi bi-arrow-right"></i>
    </button>
  </form>

  <!-- Link minta kode baru -->
  <div class="auth-switch-link" style="margin-top: 1.25rem;">
    Tidak menerima kode di email? <a href="{{ route('password.forgot') }}">Kirim ulang kode OTP</a>
  </div>
@endsection

@section('scripts')
<script>
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
</script>
@endsection

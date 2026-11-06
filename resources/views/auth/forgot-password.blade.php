@extends('layouts.auth')

@section('page_title', 'Lupa Password')

@section('card_content')
  <div class="auth-card-header">
    <h1 class="auth-card-title">Lupa Password</h1>
    <p class="auth-card-subtitle">Masukkan email akun Anda yang terdaftar untuk menerima kode verifikasi OTP</p>
  </div>

  <form action="{{ route('password.send-otp') }}" method="POST" id="forgotForm" novalidate>
    @csrf

    <!-- Email Input -->
    <div class="form-group" style="margin-bottom: 1.5rem;">
      <label for="email" class="form-label">Email</label>
      <div class="input-wrapper">
        <input
          type="email"
          class="form-control @error('email') is-invalid @enderror"
          id="email"
          name="email"
          value="{{ old('email') }}"
          placeholder="nama@email.com"
          required
          autofocus
          autocomplete="email"
          aria-describedby="email_error"
        >
        <i class="bi bi-envelope input-icon"></i>
      </div>
      @error('email')
        <div class="field-error" id="email_error" role="alert">
          <i class="bi bi-exclamation-circle"></i>
          <span>{{ $message }}</span>
        </div>
      @enderror
    </div>

    <!-- Submit Button -->
    <button type="submit" class="btn-login" id="btnSubmitForgot">
      <span>Kirim Kode Verifikasi</span>
      <i class="bi bi-arrow-right"></i>
    </button>
  </form>

  <!-- Link kembali ke Login -->
  <div class="auth-switch-link">
    <a href="{{ route('login') }}" style="display: inline-flex; align-items: center; gap: 4px;">
      <i class="bi bi-arrow-left" style="font-size: 0.8rem;"></i>
      Kembali ke halaman login
    </a>
  </div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('forgotForm');
  const btn = document.getElementById('btnSubmitForgot');
  if (form && btn) {
    form.addEventListener('submit', function () {
      btn.disabled = true;
      btn.innerHTML = '<span>Mengirim Kode OTP...</span> <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
    });
  }
});
</script>
@endsection

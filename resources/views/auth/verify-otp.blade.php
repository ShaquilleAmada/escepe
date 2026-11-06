@extends('layouts.auth')

@section('page_title', 'Verifikasi Kode OTP')

@section('card_content')
  <div class="auth-card-header">
    <div style="display: inline-flex; align-items: center; justify-content: center; width: 54px; height: 54px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 50%; color: #2563eb; font-size: 1.5rem; margin-bottom: 0.85rem;">
      <i class="bi bi-shield-check"></i>
    </div>
    <h1 class="auth-card-title">Verifikasi Kode OTP</h1>
    <p class="auth-card-subtitle">
      Masukkan 6 digit kode yang dikirim ke <strong style="color: #1e293b;">{{ $email ?? session('otp_email') }}</strong>
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

  {{-- Mode Dev Display (hanya aktif jika RESET_OTP_MODE=display) --}}
  @if(session('otp_code'))
    <div style="margin-bottom: 1.25rem; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 12px; text-align: center;" role="alert" aria-live="polite">
      <div style="font-size: 0.8rem; font-weight: 600; color: #1d4ed8; margin-bottom: 0.25rem;">[Mode Dev] Kode Verifikasi Layar</div>
      <div style="font-size: 1.6rem; font-weight: 800; color: #1e3a8a; letter-spacing: 0.18em; font-family: 'JetBrains Mono', monospace; line-height: 1.2;">
        {{ session('otp_code') }}
      </div>
      <div style="font-size: 0.75rem; color: #64748b; margin-top: 0.25rem;">
        Berlaku 10 menit. Masukkan kode di atas pada kolom berikut.
      </div>
    </div>
  @endif

  <form action="{{ route('password.verify.post') }}" method="POST" id="verifyOtpForm" novalidate>
    @csrf

    <!-- Input Kode OTP -->
    <div class="form-group" style="margin-bottom: 1.25rem;">
      <label for="otp" class="form-label" style="text-align: center; display: block; font-weight: 600;">
        6 Digit Kode OTP
      </label>
      <div class="input-wrapper">
        <input
          type="text"
          class="form-control @error('otp') is-invalid @enderror"
          id="otp"
          name="otp"
          value="{{ old('otp') }}"
          placeholder="&bull; &bull; &bull; &bull; &bull; &bull;"
          required
          autofocus
          autocomplete="one-time-code"
          maxlength="6"
          inputmode="numeric"
          pattern="[0-9]{6}"
          style="font-size: 1.55rem; font-weight: 700; letter-spacing: 0.35em; text-align: center; font-family: 'JetBrains Mono', monospace; height: 56px; padding: 0 16px;"
          aria-describedby="otp_error"
        >
      </div>
      @error('otp')
        <div class="field-error" id="otp_error" role="alert" style="justify-content: center; margin-top: 8px;">
          <i class="bi bi-exclamation-circle"></i>
          <span>{{ $message }}</span>
        </div>
      @enderror
      <div style="font-size: 0.78rem; color: #64748b; text-align: center; margin-top: 6px;">
        Kode berlaku selama 10 menit.
      </div>
    </div>

    <!-- Tombol Submit Verifikasi -->
    <button type="submit" class="btn-login" id="btnSubmitOtp">
      <span>Verifikasi Kode OTP</span>
      <i class="bi bi-arrow-right"></i>
    </button>
  </form>

  <!-- Area Kirim Ulang OTP & Ubah Email -->
  <div style="margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid #f1f5f9; text-align: center;">
    <p style="font-size: 0.82rem; color: #64748b; margin-bottom: 0.75rem;">
      Tidak menerima kode OTP di email?
    </p>

    <div style="display: flex; align-items: center; justify-content: center; gap: 14px; flex-wrap: wrap;">
      <form action="{{ route('password.resend-otp') }}" method="POST" id="resendOtpForm" style="display: inline-block;">
        @csrf
        <button type="submit" id="resendBtn" class="btn btn-sm btn-outline-primary" style="font-size: 0.8rem; font-weight: 600; border-radius: 8px; padding: 6px 14px; display: inline-flex; align-items: center; gap: 6px;">
          <i class="bi bi-arrow-repeat"></i>
          <span id="resendText">Kirim Ulang Kode OTP</span>
        </button>
      </form>

      <a href="{{ route('password.forgot') }}" style="font-size: 0.8rem; color: #475569; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
        <i class="bi bi-pencil-square"></i>
        <span>Ganti Email</span>
      </a>
    </div>
  </div>

  <!-- Link Kembali ke Login -->
  <div class="auth-switch-link" style="margin-top: 1.25rem;">
    <a href="{{ route('login') }}" style="display: inline-flex; align-items: center; gap: 4px;">
      <i class="bi bi-arrow-left" style="font-size: 0.8rem;"></i>
      Kembali ke halaman login
    </a>
  </div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const otpInput = document.getElementById('otp');
  const verifyForm = document.getElementById('verifyOtpForm');
  const btnSubmit = document.getElementById('btnSubmitOtp');
  const resendBtn = document.getElementById('resendBtn');
  const resendText = document.getElementById('resendText');

  // Bersihkan input otomatis hanya menerima angka
  if (otpInput) {
    otpInput.addEventListener('input', function (e) {
      this.value = this.value.replace(/\D/g, '').slice(0, 6);
    });

    otpInput.addEventListener('paste', function (e) {
      e.preventDefault();
      const pasteData = (e.clipboardData || window.clipboardData).getData('text');
      const cleanDigits = pasteData.replace(/\D/g, '').slice(0, 6);
      this.value = cleanDigits;
    });
  }

  // Mencegah double click pada saat verifikasi
  if (verifyForm && btnSubmit) {
    verifyForm.addEventListener('submit', function () {
      btnSubmit.disabled = true;
      btnSubmit.innerHTML = '<span>Memverifikasi...</span> <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
    });
  }

  // Cooldown tombol kirim ulang (30 detik)
  if (resendBtn && resendText) {
    const resendForm = document.getElementById('resendOtpForm');
    if (resendForm) {
      resendForm.addEventListener('submit', function () {
        resendBtn.disabled = true;
        resendBtn.classList.add('disabled');
        let countdown = 30;
        resendText.textContent = `Tunggu (${countdown}s)`;
        const interval = setInterval(function () {
          countdown--;
          if (countdown <= 0) {
            clearInterval(interval);
            resendBtn.disabled = false;
            resendBtn.classList.remove('disabled');
            resendText.textContent = 'Kirim Ulang Kode OTP';
          } else {
            resendText.textContent = `Tunggu (${countdown}s)`;
          }
        }, 1000);
      });
    }
  }
});
</script>
@endsection


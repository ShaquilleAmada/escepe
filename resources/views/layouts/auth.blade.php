<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('page_title') - School Class Progress</title>
<link rel="icon" type="image/png" href="{{ str_replace(' ', '%20', asset('images/logo-smk1bantul.png')) }}">

<!-- Google Fonts: Plus Jakarta Sans -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

<!-- Bootstrap 5.3 CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>
:root {
  --font-sans: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
  --font-mono: 'JetBrains Mono', monospace;

  --bg-page: #f8fafc;
  --card-bg: #ffffff;
  --border-color: #e2e8f0;
  --border-light: #f1f5f9;

  --text-900: #0f172a;
  --text-800: #1e293b;
  --text-700: #334155;
  --text-600: #475569;
  --text-500: #64748b;
  --text-400: #94a3b8;

  --primary-blue: #2563eb;
  --primary-hover: #1d4ed8;
  --primary-light: #eff6ff;

  --error-red: #B91C1C;
  --error-bg: #fef2f2;
  --error-border: #fecaca;

  --r-md: 8px;
  --r-lg: 14px;
  --r-xl: 20px;
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
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  padding: 2.5rem 1.25rem;
  -webkit-font-smoothing: antialiased;
  position: relative;
  overflow-x: hidden;
}

/* Subtle background accent grid & ambient gradient glow */
.auth-glow-bg {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: radial-gradient(circle at 50% -15%, rgba(37, 99, 235, 0.15) 0%, rgba(248, 250, 252, 0.5) 45%, transparent 80%),
              radial-gradient(circle at 85% 20%, rgba(14, 165, 233, 0.09) 0%, transparent 45%),
              radial-gradient(circle at 15% 30%, rgba(99, 102, 241, 0.09) 0%, transparent 45%);
  pointer-events: none;
  z-index: 0;
}

.auth-bg-pattern {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-image: radial-gradient(rgba(148, 163, 184, 0.18) 1px, transparent 1px);
  background-size: 28px 28px;
  pointer-events: none;
  z-index: 0;
}

/* Centered Container Wrapper */
.auth-wrapper {
  position: relative;
  z-index: 1;
  width: 100%;
  max-width: 480px;
  margin: auto;
}

/* Brand Header */
.auth-brand {
  text-align: center;
  margin-bottom: 1.75rem;
}

.brand-logo-img {
  width: 54px;
  height: 54px;
  object-fit: contain;
  margin-bottom: 0.75rem;
  filter: drop-shadow(0 4px 10px rgba(0, 0, 0, 0.08));
}

.brand-name {
  font-size: 1.35rem;
  font-weight: 800;
  color: var(--text-900);
  letter-spacing: -0.02em;
  line-height: 1.2;
  margin-bottom: 0.2rem;
}

.brand-desc {
  font-size: 0.85rem;
  color: var(--primary-blue);
  font-weight: 600;
  margin-bottom: 0;
}

/* Card Container */
.auth-card {
  background: #ffffff;
  border: 1px solid var(--border-color);
  border-radius: var(--r-xl);
  box-shadow: 0 20px 45px -12px rgba(15, 23, 42, 0.09), 0 8px 16px -6px rgba(15, 23, 42, 0.03);
  padding: 2.25rem 2rem;
}

.auth-card-header {
  margin-bottom: 1.5rem;
  text-align: left;
}

.auth-card-title {
  font-size: 1.28rem;
  font-weight: 800;
  color: var(--text-900);
  letter-spacing: -0.02em;
  margin-bottom: 0.35rem;
}

.auth-card-subtitle {
  font-size: 0.84rem;
  color: var(--text-500);
  margin-bottom: 0;
  line-height: 1.5;
}

/* Form Controls */
.form-group {
  margin-bottom: 1.25rem;
}

.form-label {
  display: block;
  font-size: 0.82rem;
  font-weight: 600;
  color: var(--text-700);
  margin-bottom: 0.45rem;
}

.input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.input-wrapper .input-icon {
  position: absolute;
  left: 12px;
  color: var(--text-400);
  font-size: 1.05rem;
  pointer-events: none;
  transition: color 0.15s ease;
}

.input-wrapper .form-control {
  width: 100%;
  height: 44px;
  padding: 0 12px 0 38px;
  background-color: #ffffff !important;
  border: 1px solid var(--border-color);
  border-radius: var(--r-md);
  font-size: 0.9rem;
  color: var(--text-900);
  font-family: inherit;
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.input-wrapper .form-control:focus {
  background-color: #ffffff !important;
  border-color: var(--primary-blue);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
  outline: none;
}

.input-wrapper .form-control:-webkit-autofill,
.input-wrapper .form-control:-webkit-autofill:hover, 
.input-wrapper .form-control:-webkit-autofill:focus, 
.input-wrapper .form-control:-webkit-autofill:active {
  -webkit-box-shadow: 0 0 0 1000px #ffffff inset !important;
  box-shadow: 0 0 0 1000px #ffffff inset !important;
  -webkit-text-fill-color: var(--text-900) !important;
  caret-color: var(--text-900) !important;
  transition: background-color 5000s ease-in-out 0s;
}

.input-wrapper .form-control:focus ~ .input-icon {
  color: var(--primary-blue);
}

.input-wrapper .form-control::placeholder {
  color: var(--text-400);
  font-weight: 400;
}

.input-wrapper .form-control.is-invalid {
  border-color: var(--error-red);
  box-shadow: none;
}

.input-wrapper .form-control.is-invalid:focus {
  border-color: var(--error-red);
  box-shadow: 0 0 0 3px rgba(185, 28, 28, 0.10);
}

.field-error {
  font-size: 0.80rem;
  color: var(--error-red);
  margin-top: 0.35rem;
  display: flex;
  align-items: center;
  gap: 4px;
}

.toggle-pwd-btn {
  position: absolute;
  right: 10px;
  background: none;
  border: none;
  color: var(--text-400);
  font-size: 1.1rem;
  padding: 4px 6px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
  transition: color 0.15s ease;
}

.toggle-pwd-btn:hover {
  color: var(--text-700);
}

.form-options {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.4rem;
  font-size: 0.85rem;
}

.form-check-input {
  width: 1rem;
  height: 1rem;
  margin-top: 0.1rem;
  border: 1px solid var(--border-color);
  border-radius: 4px;
  cursor: pointer;
}

.form-check-input:checked {
  background-color: var(--primary-blue);
  border-color: var(--primary-blue);
}

.form-check-input:focus {
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
  outline: none;
}

.form-check-label {
  color: var(--text-700);
  font-weight: 500;
  cursor: pointer;
  padding-left: 0.25rem;
}

.btn-login {
  width: 100%;
  height: 44px;
  background-color: var(--primary-blue);
  color: #ffffff;
  border: 1px solid var(--primary-blue);
  border-radius: var(--r-md);
  font-size: 0.92rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  cursor: pointer;
  transition: all 0.15s ease;
  font-family: inherit;
}

.btn-login:hover {
  background-color: var(--primary-hover);
  border-color: var(--primary-hover);
}

.btn-login:active {
  transform: scale(0.99);
}

.btn-login:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.auth-switch-link {
  text-align: center;
  margin-top: 1.25rem;
  font-size: 0.85rem;
  color: var(--text-500);
}

.auth-switch-link a {
  color: var(--primary-blue);
  font-weight: 600;
  text-decoration: none;
}

.auth-switch-link a:hover {
  text-decoration: underline;
}

.auth-footer {
  text-align: center;
  margin-top: 1.75rem;
  font-size: 0.78rem;
  color: var(--text-500);
}
</style>
</head>
<body>

<div class="auth-glow-bg"></div>
<div class="auth-bg-pattern"></div>

<div class="auth-wrapper">
  <!-- Brand Header -->
  <div class="auth-brand">
    <img src="{{ asset('images/logo-smk1bantul.png') }}" alt="Logo SMK Negeri 1 Bantul" class="brand-logo-img">
    <div class="brand-name">School Class Progress</div>
    <p class="brand-desc">SMK Negeri 1 Bantul</p>
  </div>

  <!-- Auth Card -->
  <div class="auth-card">
    @yield('card_content')
  </div>

  <!-- Footer Info -->
  <div class="auth-footer">
    &copy; {{ date('Y') }} SMK Negeri 1 Bantul &bull; School Class Progress
  </div>
</div>

@yield('scripts')
</body>
</html>

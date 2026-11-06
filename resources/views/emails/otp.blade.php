<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kode OTP Reset Password - Sistem SCP</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #334155; line-height: 1.6;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f1f5f9; padding: 30px 15px;">
    <tr>
      <td align="center">
        <!-- Main Card -->
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width: 540px; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 16px rgba(15, 23, 42, 0.08); border: 1px solid #e2e8f0;">
          
          <!-- Header Banner -->
          <tr>
            <td align="center" style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%); padding: 32px 24px; text-align: center;">
              <h1 style="margin: 0 auto; font-size: 22px; font-weight: 700; color: #ffffff; letter-spacing: 0.02em; text-align: center;">
                Sistem SCP
              </h1>
              <p style="margin: 6px auto 0 auto; font-size: 13px; color: #bfdbfe; text-align: center; line-height: 1.4;">
                School Class Progress &bull; Layanan Pemulihan Akun
              </p>
            </td>
          </tr>

          <!-- Content Body -->
          <tr>
            <td style="padding: 32px 28px;">
              <p style="margin: 0 0 16px 0; font-size: 15px; color: #1e293b;">
                Halo, <strong>{{ $userName ?? 'Pengguna' }}</strong>!
              </p>
              
              <p style="margin: 0 0 20px 0; font-size: 14px; color: #475569; line-height: 1.6;">
                Kami menerima permintaan untuk mereset kata sandi akun Anda pada <strong>Sistem SCP</strong>. Silakan gunakan kode verifikasi (OTP) berikut untuk menyelesaikan proses penggantian kata sandi:
              </p>

              <!-- OTP Box -->
              <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin: 24px 0;">
                <tr>
                  <td align="center" style="background-color: #eff6ff; border: 2px dashed #93c5fd; border-radius: 12px; padding: 22px 16px;">
                    <span style="display: block; font-size: 12px; font-weight: 600; text-transform: uppercase; color: #1d4ed8; letter-spacing: 0.08em; margin-bottom: 8px;">
                      Kode Verifikasi Sekali Pakai (OTP)
                    </span>
                    <span style="display: inline-block; font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, Courier, monospace; font-size: 36px; font-weight: 800; letter-spacing: 8px; color: #1e3a8a; line-height: 1.1; user-select: all;">{{ trim($otp) }}</span>
                    <span style="display: block; font-size: 12px; color: #3b82f6; margin-top: 8px;">
                      Berlaku selama {{ $validMinutes ?? 10 }} menit
                    </span>
                  </td>
                </tr>
              </table>

              <!-- Notice Box -->
              <div style="background-color: #f8fafc; border-left: 4px solid #3b82f6; border-radius: 4px; padding: 12px 16px; margin-bottom: 24px;">
                <p style="margin: 0 0 6px 0; font-size: 13px; font-weight: 600; color: #1e293b;">
                  Peringatan Keamanan Penting:
                </p>
                <ul style="margin: 0; padding-left: 18px; font-size: 12.5px; color: #64748b; line-height: 1.5;">
                  <li>Jangan berikan kode ini kepada siapa pun, termasuk administrator atau pihak sekolah.</li>
                  <li>Kode ini hanya dapat digunakan satu kali.</li>
                  <li>Jika Anda tidak meminta reset kata sandi, abaikan email ini. Akun Anda tetap aman.</li>
                </ul>
              </div>

              <p style="margin: 0; font-size: 13px; color: #64748b; line-height: 1.5;">
                Salam hangat,<br>
                <strong style="color: #334155;">Tim Pengembang Sistem SCP</strong>
              </p>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 18px 24px; text-align: center;">
              <p style="margin: 0; font-size: 11.5px; color: #94a3b8; line-height: 1.5;">
                Email ini dikirim secara otomatis oleh Sistem SCP. Mohon untuk tidak membalas email ini.<br>
                &copy; {{ date('Y') }} Sistem SCP. Hak cipta dilindungi undang-undang.
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>

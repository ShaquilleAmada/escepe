<?php

$cookieJar = tempnam(sys_get_temp_dir(), 'scp_cookie_');

// 1. Ambil halaman login dan extract CSRF token
$ch = curl_init('http://localhost/Sistem%20SCP/public/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
$html = curl_exec($ch);
curl_close($ch);

preg_match('/name="_token"\s+value="([^"]+)"/', $html, $matches);
$csrfToken = $matches[1] ?? '';

if (empty($csrfToken)) {
    echo "[GAGAL] Tidak dapat menemukan token CSRF pada form login Web Blade.\n";
    exit(1);
}

// 2. Submit form login
$ch = curl_init('http://localhost/Sistem%20SCP/public/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    '_token' => $csrfToken,
    'username' => 'waka_kurikulum',
    'password' => 'password123',
]));
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$redirect = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
curl_close($ch);

echo "=== MEMERIKSA LOGIN WEB WAKA (LARAVEL BLADE) ===\n";
echo "HTTP Status: $code\n";
echo "Redirect Target: $redirect\n";

if ($code === 302) {
    // 3. Akses halaman redirect
    $ch = curl_init($redirect);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
    $dashboardHtml = curl_exec($ch);
    $dashCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (strpos($dashboardHtml, 'Waka') !== false || strpos($dashboardHtml, 'Dashboard') !== false || $dashCode === 200) {
        echo "[LULUS] Web Waka berhasil login dan membuka dashboard (HTTP $dashCode).\n";
    } else {
        echo "[GAGAL] Berhasil login namun dashboard tidak merespons (HTTP $dashCode).\n";
    }
} else {
    echo "[GAGAL] Login Web gagal, status: $code\n";
}

@unlink($cookieJar);

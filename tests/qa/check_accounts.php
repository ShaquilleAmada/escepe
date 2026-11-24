<?php

$accounts = [
    ['Waka', 'waka_kurikulum', 'password123'],
    ['Guru', 'aris_purwantinah', 'password123'],
    ['Walas', 'sri_sunartini', 'password123'],
    ['BK', 'ryawidyaningtyas', 'password123'],
    ['Siswa', '19126', 'password123'],
    ['OrangTua', 'P19126', 'password123'],
];

echo "=== MEMERIKSA AKUN UJI KE-6 ROLE (LARAVEL API) ===\n";

foreach ($accounts as $a) {
    [$role, $user, $pass] = $a;
    $ch = curl_init('http://localhost/Sistem%20SCP/public/api/login');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['username' => $user, 'password' => $pass]));
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $json = json_decode($res, true);
    if ($code === 200 && ! empty($json['success'])) {
        $name = $json['user']['fullname'] ?? $json['user']['name'] ?? 'User';
        $roleId = $json['user']['role_id'] ?? '-';
        $token = substr($json['token'] ?? '', 0, 10).'...';
        echo "[LULUS] $role ($user): $name | role_id: $roleId | Token: $token\n";
    } else {
        $msg = $json['message'] ?? $res;
        echo "[GAGAL] $role ($user): HTTP $code | $msg\n";
    }
}

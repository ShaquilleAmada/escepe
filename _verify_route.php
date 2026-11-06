<?php
// Verifikasi route: GET / harus render landing (guest), bukan redirect ke login
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Illuminate\Http\Request::create('/', 'GET');
$response = $kernel->handle($request);

echo "Status : " . $response->getStatusCode() . "\n";
$isRedirect = $response->getStatusCode() >= 300 && $response->getStatusCode() < 400;
echo "Tipe   : " . ($isRedirect ? ('REDIRECT -> ' . $response->headers->get('Location')) : 'HTML') . "\n";

$body = $response->getContent();
if (!$isRedirect) {
    echo (str_contains($body, 'Masuk ke Akun') ? "Isi    : landing page OK\n" : "Isi    : BUKAN landing page\n");
}

// Cek proteksi area waka tetap berjalan
$req2 = Illuminate\Http\Request::create('/waka/dashboard', 'GET');
$res2 = $kernel->handle($req2);
echo "GET /waka/dashboard (guest) -> " . $res2->getStatusCode()
    . " " . ($res2->headers->get('Location') ?? '') . "\n";

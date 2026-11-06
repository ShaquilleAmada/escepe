<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$pdfPath = 'C:/Users/LENOVO/.gemini/antigravity/brain/9427c070-1751-43c6-aeb0-4cc516e75dd4/.user_uploaded/media_1793891060897_de676277.pdf';
$parser = new \App\Services\Pdf\CustomPdfParser();
$pdf = $parser->parseFile($pdfPath);
$classes = \App\Models\Classes::all();

$service = new \App\Services\ScheduleImportService();
$ref = new ReflectionClass($service);
$parseMethod = $ref->getMethod('parseFetClassPdf');
$parseMethod->setAccessible(true);

$slotDefs = [
    0 => ['start' => '07:15', 'end' => '08:00', 'period' => 1],
    1 => ['start' => '08:00', 'end' => '08:45', 'period' => 2],
    2 => ['start' => '08:45', 'end' => '09:30', 'period' => 3],
    3 => ['start' => '09:45', 'end' => '10:30', 'period' => 4],
    4 => ['start' => '10:30', 'end' => '11:15', 'period' => 5],
    5 => ['start' => '11:15', 'end' => '12:00', 'period' => 6],
    6 => ['start' => '12:30', 'end' => '13:15', 'period' => 7],
    7 => ['start' => '13:15', 'end' => '14:00', 'period' => 8],
    8 => ['start' => '14:00', 'end' => '14:45', 'period' => 9],
    9 => ['start' => '14:45', 'end' => '15:30', 'period' => 10],
];

// Let's test current parseFetClassPdf on X RPL 2:
$rows = $parseMethod->invoke($service, $pdf, $classes, 'Putaran 1', $slotDefs);

$bkRows = array_filter($rows, fn($r) => stripos($r[2], 'bimbingan') !== false || stripos($r[2], 'bk') !== false || stripos($r[3], 'ryawidyaningtyas') !== false);
echo "Total BK rows parsed currently: " . count($bkRows) . "\n";
foreach ($bkRows as $r) {
    echo "  {$r[0]} {$r[1]} | Class: {$r[4]} | Subject: {$r[2]} | Teacher: {$r[3]}\n";
}


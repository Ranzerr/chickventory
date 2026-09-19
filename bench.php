<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== BENCHMARKING SUPABASE CONNECTION & QUERIES ===" . PHP_EOL;

$start = microtime(true);
DB::connection()->getPdo();
$connectTime = (microtime(true) - $start) * 1000;
echo "1. Initial Database Connection Time: " . round($connectTime, 2) . " ms" . PHP_EOL;

$queryTimes = [];
for ($i = 1; $i <= 5; $i++) {
    $qStart = microtime(true);
    DB::select('SELECT 1');
    $queryTimes[] = (microtime(true) - $qStart) * 1000;
}
echo "2. Ping Query (SELECT 1) Round-trip Latency: avg " . round(array_sum($queryTimes) / count($queryTimes), 2) . " ms (individual: " . implode(" ms, ", array_map('round', $queryTimes)) . " ms)" . PHP_EOL;

// Measure ProductController@index execution
$ctrlStart = microtime(true);
DB::enableQueryLog();
$controller = app(\App\Http\Controllers\ProductController::class);
$response = $controller->index(request());
$ctrlTotal = (microtime(true) - $ctrlStart) * 1000;
$queryLog = DB::getQueryLog();

echo "3. ProductController@index Total Execution Time: " . round($ctrlTotal, 2) . " ms" . PHP_EOL;
echo "4. Total SQL Queries Executed: " . count($queryLog) . PHP_EOL;
$totalSqlTime = 0;
foreach ($queryLog as $idx => $q) {
    echo "   Query " . ($idx + 1) . ": " . round($q['time'], 2) . " ms | " . substr($q['query'], 0, 70) . "..." . PHP_EOL;
    $totalSqlTime += $q['time'];
}
echo "5. Total Time Spent in SQL Queries: " . round($totalSqlTime, 2) . " ms (" . round(($totalSqlTime / $ctrlTotal) * 100, 1) . "% of controller time)" . PHP_EOL;


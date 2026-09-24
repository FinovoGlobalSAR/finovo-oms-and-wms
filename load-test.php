<?php
/**
 * Simple concurrent load test — ek route ko baar-baar, parallel mein
 * request bhejta hai, dekhta hai kitna time lagta hai aur kitne fail hote hain.
 */
$url = 'http://localhost:8000/login';
$totalRequests = 100000;
$concurrency = 100;

$successCount = 0;
$failCount = 0;
$totalTime = 0;

$startOverall = microtime(true);

for ($batch = 0; $batch < $totalRequests / $concurrency; $batch++) {
    $multiHandle = curl_multi_init();
    $handles = [];

    for ($i = 0; $i < $concurrency; $i++) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_multi_add_handle($multiHandle, $ch);
        $handles[] = $ch;
    }

    $running = null;
    do {
        curl_multi_exec($multiHandle, $running);
    } while ($running > 0);

    foreach ($handles as $ch) {
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpCode === 200) {
            $successCount++;
        } else {
            $failCount++;
        }
        curl_multi_remove_handle($multiHandle, $ch);
        curl_close($ch);
    }

    curl_multi_close($multiHandle);
}

$totalTime = microtime(true) - $startOverall;

echo "==================================\n";
echo "Load Test Results\n";
echo "==================================\n";
echo "Total requests: {$totalRequests}\n";
echo "Concurrency: {$concurrency}\n";
echo "Successful: {$successCount}\n";
echo "Failed: {$failCount}\n";
echo "Total time: " . round($totalTime, 2) . "s\n";
echo "Avg time per request: " . round($totalTime / $totalRequests, 3) . "s\n";
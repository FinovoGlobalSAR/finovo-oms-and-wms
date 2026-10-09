<?php

$lockFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'finovo-auto-sync.lock';
$lock = fopen($lockFile, 'c');

if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
    echo "Another auto-sync cycle is still running. Skipping this run.\n";
    exit(0);
}

require_once __DIR__ . '/auto-sync.php';

echo "Running worker...\n";
require_once __DIR__ . '/worker.php';

flock($lock, LOCK_UN);
fclose($lock);
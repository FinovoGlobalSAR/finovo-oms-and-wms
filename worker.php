<?php

require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/app/Models/JobQueue.php';
require_once __DIR__ . '/app/Models/Store.php';
require_once __DIR__ . '/app/Services/StoreSyncRunner.php';

$jobQueue = new JobQueue();
$storeModel = new Store();

echo "Worker started...\n";

$processedCount = 0;

while (true) {
    $job = $jobQueue->nextPending();

    if (!$job) {
        break;
    }

    $jobId = (int) $job['id'];
    $storeId = (int) $job['store_id'];
    $type = $job['type'];

    echo "Processing job #{$jobId} ({$type}) for store #{$storeId}...\n";
    $jobQueue->markProcessing($jobId);

    try {
        $store = $storeModel->find($storeId);
        if (!$store) {
            throw new Exception('Store not found.');
        }

        $result = runSyncJob($type, $store);
        $jobQueue->markCompleted($jobId, $result);
        echo "  -> Completed: {$result}\n";
    } catch (Throwable $e) {
        $jobQueue->markFailed($jobId, $e->getMessage());
        echo "  -> Failed: " . $e->getMessage() . "\n";
    }

    $processedCount++;
}

echo "Worker finished. {$processedCount} job(s) processed.\n";

/**
 * Ek job chalao — har job wahi controller function chalata hai jo us ka "Sync" button chalata hai
 * (StoreSyncRunner::jobMap). Pehle 6 platforms ki product sync yahan alag, purana code chalati thi.
 */
function runSyncJob(string $type, array $store): string
{
    if (StoreSyncRunner::handles($type)) {
        return StoreSyncRunner::run($type, $store);
    }

    throw new Exception("Unknown job type: {$type}");
}
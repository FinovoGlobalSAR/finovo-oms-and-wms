<?php
require_once __DIR__ . '/../app/Models/JobQueue.php';

use PHPUnit\Framework\TestCase;

/**
 * Sync queue: job sirf EK worker uthaye, aur beech mein ruka hua job hamesha "processing" mein na atka rahe.
 */
class JobQueueTest extends TestCase
{
    private JobQueue $queue;
    private PDO $db;
    private int $storeId;
    private array $jobIds = [];

    protected function setUp(): void
    {
        $this->queue = new JobQueue();
        $this->db = Database::getConnection();
        $this->storeId = (int) $this->db->query("SELECT id FROM stores LIMIT 1")->fetchColumn();
    }

    protected function tearDown(): void
    {
        if ($this->jobIds) {
            $this->db->exec("DELETE FROM jobs WHERE id IN (" . implode(',', array_map('intval', $this->jobIds)) . ")");
        }
    }

    private function addJob(string $status, string $updatedAgo = '0 MINUTE'): int
    {
        $this->db->prepare(
            "INSERT INTO jobs (type, store_id, status, updated_at) VALUES ('phpunit_job', ?, ?, NOW() - INTERVAL {$updatedAgo})"
        )->execute([$this->storeId, $status]);
        return $this->jobIds[] = (int) $this->db->lastInsertId();
    }

    public function testStuckProcessingJobIsReleased(): void
    {
        $stuck = $this->addJob('processing', '45 MINUTE');
        $running = $this->addJob('processing', '5 MINUTE');

        $this->assertGreaterThanOrEqual(1, $this->queue->releaseStale());

        $this->assertSame('failed', $this->queue->find($stuck)['status']);
        $this->assertStringContainsString('did not finish', $this->queue->find($stuck)['result_message']);
        // Abhi abhi shuru hua job nahi chheda jata
        $this->assertSame('processing', $this->queue->find($running)['status']);
    }

    public function testJobIsClaimedOnlyOnce(): void
    {
        $others = (int) $this->db->query("SELECT COUNT(*) FROM jobs WHERE status = 'pending'")->fetchColumn();
        if ($others > 0) {
            $this->markTestSkipped('Database has real pending jobs; not touching them.');
        }

        $id = $this->addJob('pending');

        $first = $this->queue->nextPending();
        $this->assertSame($id, (int) $first['id']);
        $this->assertSame('processing', $first['status']);
        $this->assertSame(1, (int) $first['attempts']);

        // Doosra worker: wohi job dobara nahi milta
        $this->assertNull($this->queue->nextPending());

        // Purana code markProcessing() bhi chalaye to attempts dobara nahi barhte
        $this->queue->markProcessing($id);
        $this->assertSame(1, (int) $this->queue->find($id)['attempts']);
    }

    public function testRecentForStoreRespectsLimit(): void
    {
        $this->addJob('completed');
        $this->addJob('completed');
        $this->assertCount(1, $this->queue->recentForStore($this->storeId, 1));
    }
}
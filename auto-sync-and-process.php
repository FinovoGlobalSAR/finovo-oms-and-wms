<?php
/**
 * Task Scheduler yehi script chalayega — pehle naye jobs queue karta hai,
 * phir turant worker.php ka logic chalake unhe process kar deta hai.
 * Ek hi command mein poora automatic sync cycle complete ho jata hai.
 */
require_once __DIR__ . '/auto-sync.php';

echo "Running worker...\n";
require_once __DIR__ . '/worker.php';
<?php
/**
 * eBay real-time order notifications ka EK BAAR ka setup.
 * Ye eBay ko batata hai: "naya order aaye to https://YOUR-DOMAIN/webhooks/ebay pe bhejo".
 *
 * Pehle:
 *   1) .env mein EBAY_APP_ID, EBAY_CERT_ID, EBAY_RUNAME, EBAY_NOTIFICATION_ENDPOINT,
 *      EBAY_NOTIFICATION_VERIFICATION_TOKEN bharo
 *   2) Website live (https) ho, taake eBay /webhooks/ebay tak pahunch sake
 *   3) Stores page pe "Connect eBay" dabake store connect karo
 * Phir chalao:  C:\xampp\php\php.exe ebay-notifications-setup.php
 * (Dobara chalane se kuch kharab nahi hota — pehle se bana hua ho to skip karta hai.)
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/app/Services/EbayAuthService.php';

$topicId = 'ORDER_CONFIRMATION';
$endpoint = (string) env('EBAY_NOTIFICATION_ENDPOINT', '');
$verificationToken = (string) env('EBAY_NOTIFICATION_VERIFICATION_TOKEN', '');

if ($endpoint === '' || !str_starts_with($endpoint, 'https://')) {
    exit("Error: EBAY_NOTIFICATION_ENDPOINT must be set to your https URL, e.g. https://your-domain.com/webhooks/ebay\n");
}
if (!preg_match('/^[A-Za-z0-9_\-]{32,80}$/', $verificationToken)) {
    exit("Error: EBAY_NOTIFICATION_VERIFICATION_TOKEN must be 32-80 characters (letters, numbers, _ or -).\n"
       . "Example you can use: " . bin2hex(random_bytes(20)) . "\n");
}

$base = EbayAuthService::apiBase() . '/commerce/notification/v1';

function ebayCall(string $method, string $url, string $token, ?array $body = null): array
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
        'Accept: application/json',
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, curlVerifySsl());
    $response = (string) curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    $headers = substr($response, 0, $headerSize);
    $location = preg_match('/^location:\s*(\S+)/mi', $headers, $m) ? $m[1] : '';

    return ['code' => $code, 'data' => json_decode(substr($response, $headerSize), true), 'location' => $location];
}

$appToken = EbayAuthService::getApplicationToken();
if ($appToken === null) {
    exit("Error: could not get eBay application token. Check EBAY_APP_ID / EBAY_CERT_ID / EBAY_ENV.\n");
}

echo "eBay environment: " . (EbayAuthService::isProduction() ? 'PRODUCTION' : 'SANDBOX') . "\n";

// ---------- 1) Topic ka schema version ----------
$topic = ebayCall('GET', "{$base}/topic/{$topicId}", $appToken);
if ($topic['code'] !== 200) {
    exit("Error: topic {$topicId} not available (HTTP {$topic['code']}): " . json_encode($topic['data']) . "\n");
}
$schemaVersion = '1.0';
foreach ($topic['data']['supportedPayloads'] ?? [] as $payload) {
    // Jo deprecated nahi hai, uska schemaVersion lo
    if (empty($payload['deprecated']) && !empty($payload['schemaVersion'])) {
        $schemaVersion = (string) $payload['schemaVersion'];
    }
}
echo "Topic {$topicId}: schema version {$schemaVersion}\n";

// ---------- 2) Destination (humara webhook URL) ----------
$destinationId = null;
$list = ebayCall('GET', "{$base}/destination?limit=100", $appToken);
foreach ($list['data']['destinations'] ?? [] as $d) {
    if (($d['deliveryConfig']['endpoint'] ?? '') === $endpoint) {
        $destinationId = $d['destinationId'];
        echo "Destination already exists: {$destinationId}\n";
    }
}

if ($destinationId === null) {
    // eBay yahan hamare /webhooks/ebay pe challenge_code bhejega — site live honi chahiye
    $created = ebayCall('POST', "{$base}/destination", $appToken, [
        'name' => 'Finovo OMS',
        'status' => 'ENABLED',
        'deliveryConfig' => [
            'endpoint' => $endpoint,
            'verificationToken' => $verificationToken,
        ],
    ]);
    if ($created['code'] !== 201) {
        exit("Error creating destination (HTTP {$created['code']}): " . json_encode($created['data']) . "\n"
           . "Tip: open {$endpoint}?challenge_code=test in a browser — it must return JSON with challengeResponse.\n");
    }
    $destinationId = basename($created['location']);
    echo "Destination created: {$destinationId}\n";
}

// ---------- 3) Har eBay-connected store ke liye subscription ----------
$db = Database::getConnection();
$stores = $db->query("SELECT * FROM stores WHERE ebay_refresh_token IS NOT NULL AND ebay_refresh_token <> ''")->fetchAll();

if (!$stores) {
    exit("No store is connected with 'Connect eBay' yet. Connect a store first, then run this again.\n");
}

foreach ($stores as $store) {
    $name = $store['name'] ?? ('#' . $store['id']);
    $userToken = EbayAuthService::getValidAccessToken($store);
    if ($userToken === null) {
        echo "Store {$name}: eBay token invalid — reconnect with 'Connect eBay'. Skipped.\n";
        continue;
    }

    $existing = ebayCall('GET', "{$base}/subscription?limit=100", $userToken);
    $already = false;
    foreach ($existing['data']['subscriptions'] ?? [] as $sub) {
        if (($sub['topicId'] ?? '') === $topicId && ($sub['destinationId'] ?? '') === $destinationId) {
            $already = true;
            echo "Store {$name}: already subscribed ({$sub['subscriptionId']}, {$sub['status']})\n";
        }
    }
    if ($already) continue;

    $sub = ebayCall('POST', "{$base}/subscription", $userToken, [
        'topicId' => $topicId,
        'status' => 'ENABLED',
        'destinationId' => $destinationId,
        'payload' => [
            'format' => 'JSON',
            'schemaVersion' => $schemaVersion,
            'deliveryProtocol' => 'HTTPS',
        ],
    ]);

    if ($sub['code'] === 201) {
        echo "Store {$name}: subscribed to {$topicId} (" . basename($sub['location']) . ")\n";
    } else {
        echo "Store {$name}: subscription FAILED (HTTP {$sub['code']}): " . json_encode($sub['data']) . "\n";
    }
}

echo "Done. New eBay orders will now arrive at {$endpoint} in real time.\n";
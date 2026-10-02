<?php

function loadEnv(string $path): void
{
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            // Notepad kabhi file ke shuru mein chhupa BOM character daal deta hai — use hatao
            $key = trim(str_replace("\xEF\xBB\xBF", '', $key));
            $value = trim($value);

            // Agar value "quotes" ya 'quotes' mein likhi hai to quotes hata do
            if (strlen($value) >= 2 && ($value[0] === '"' || $value[0] === "'") && substr($value, -1) === $value[0]) {
                $value = substr($value, 1, -1);
            }

            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

function env(string $key, $default = null)
{
    $value = $_ENV[$key] ?? getenv($key);
    return $value !== false ? $value : $default;
}

loadEnv(__DIR__ . '/../.env');

/**
 * true sirf tab jab .env mein APP_DEBUG=true ho.
 * Live server pe APP_DEBUG=false rakho, taake errors users ko screen pe na dikhein.
 */
function appDebug(): bool
{
    return strtolower((string) env('APP_DEBUG', 'false')) === 'true';
}

/**
 * Website ka asli address (jaise https://oms.finovoglobal.com), .env ke APP_URL se.
 * Agar APP_URL set nahi hai to request ke host se bana leta hai.
 * OAuth callbacks, Stripe aur webhooks isi se URL banate hain.
 */
function appUrl(): string
{
    $configured = trim((string) env('APP_URL', ''));
    if ($configured !== '') {
        return rtrim($configured, '/');
    }

    $isHttps = (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

    return ($isHttps ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
}

/**
 * cURL SSL certificate check. Live pe hamesha true rehna chahiye.
 * Sirf local XAMPP pe (jahan certificate bundle set nahi hota) .env mein
 * CURL_VERIFY_SSL=false likh sakte ho.
 */
function curlVerifySsl(): bool
{
    return strtolower((string) env('CURL_VERIFY_SSL', 'true')) !== 'false';
}

/**
 * In sources se aane wale orders ki currency "$" hoti hai, baaki sab "Rs.".
 * Pehle ye list 5 files mein alag-alag likhi thi (kahin 2, kahin 6, kahin 8 platforms),
 * is liye ek hi order ek page pe "$" aur invoice pe "Rs." dikhta tha.
 */
function externalOrderSources(): array
{
    return [
        'shopify_pull', 'woocommerce_pull', 'bigcommerce_pull', 'prestashop_pull',
        'opencart_pull', 'oscommerce_pull', 'wix_pull', 'ebay_pull', 'magento_pull',
    ];
}

return [
    'name' => env('APP_NAME', 'OMS + WMS'),
    'env' => env('APP_ENV', 'local'),
    'session_lifetime' => (int) env('SESSION_LIFETIME', 7200),
];
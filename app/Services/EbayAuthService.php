<?php
require_once __DIR__ . '/../../core/Database.php';

/**
 * eBay OAuth (Production-ready).
 *
 * Pehle: store pe ek "User Token" paste hota tha jo 2 ghante mein expire ho jata tha.
 * Ab:    "Connect eBay" button → seller eBay pe login karke ijazat deta hai →
 *        humein access token (2 ghante) + refresh token (~18 mahine) milte hain →
 *        access token expire hone se pehle khud naya ban jata hai.
 *
 * .env mein chahiye:
 *   EBAY_ENV=sandbox | production
 *   EBAY_APP_ID   (Client ID)
 *   EBAY_CERT_ID  (Client Secret)
 *   EBAY_RUNAME   (eBay Developer portal → User Tokens → "RuName" / redirect URL name)
 *
 * Purana "User Token paste" tareeqa bhi chalta rahega (agar refresh token na ho).
 */
class EbayAuthService
{
    public const USER_SCOPES = [
        'https://api.ebay.com/oauth/api_scope',
        'https://api.ebay.com/oauth/api_scope/sell.inventory',
        'https://api.ebay.com/oauth/api_scope/sell.fulfillment',
        'https://api.ebay.com/oauth/api_scope/commerce.notification.subscription',
    ];

    public static function isProduction(): bool
    {
        return strtolower((string) env('EBAY_ENV', 'sandbox')) === 'production';
    }

    public static function apiBase(): string
    {
        return self::isProduction() ? 'https://api.ebay.com' : 'https://api.sandbox.ebay.com';
    }

    private static function authBase(): string
    {
        return self::isProduction() ? 'https://auth.ebay.com' : 'https://auth.sandbox.ebay.com';
    }

    public static function isConfigured(): bool
    {
        return env('EBAY_APP_ID', '') !== '' && env('EBAY_CERT_ID', '') !== '' && env('EBAY_RUNAME', '') !== '';
    }

    /**
     * Seller ko eBay ke login/consent page pe bhejne wala URL.
     */
    public static function consentUrl(string $state): string
    {
        return self::authBase() . '/oauth2/authorize?' . http_build_query([
            'client_id' => env('EBAY_APP_ID', ''),
            'response_type' => 'code',
            'redirect_uri' => env('EBAY_RUNAME', ''),
            'scope' => implode(' ', self::USER_SCOPES),
            'state' => $state,
        ]);
    }

    /**
     * eBay se wapas aaye "code" ko tokens mein badlo.
     */
    public static function exchangeCode(string $code): array
    {
        return self::tokenRequest([
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => env('EBAY_RUNAME', ''),
        ]);
    }

    /**
     * Store ka valid access token do. Expire hone wala ho to refresh token se naya bana ke save karo.
     * Return null = store eBay se connected hi nahi.
     */
    public static function getValidAccessToken(array $store): ?string
    {
        $accessToken = $store['ebay_user_token'] ?? '';
        $refreshToken = $store['ebay_refresh_token'] ?? '';
        $expiresAt = !empty($store['ebay_token_expires_at']) ? strtotime($store['ebay_token_expires_at']) : 0;

        // Refresh token nahi hai → purana manual token jaisa hai waisa use karo
        if ($refreshToken === '') {
            return $accessToken !== '' ? $accessToken : null;
        }

        // 5 minute se zyada baaki hai → wahi token
        if ($accessToken !== '' && $expiresAt > time() + 300) {
            return $accessToken;
        }

        $result = self::tokenRequest([
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'scope' => implode(' ', self::USER_SCOPES),
        ]);

        if (!$result['ok']) {
            error_log('eBay token refresh failed for store ' . ($store['id'] ?? '?') . ': ' . $result['message']);
            return null;
        }

        $db = Database::getConnection();
        $db->prepare("UPDATE stores SET ebay_user_token = ?, ebay_token_expires_at = ? WHERE id = ?")
           ->execute([
               $result['access_token'],
               date('Y-m-d H:i:s', time() + (int) $result['expires_in']),
               (int) $store['id'],
           ]);

        return $result['access_token'];
    }

    /**
     * "Application token" (seller ke baghair) — webhook ki signature check karne ke liye public key lane mein.
     */
    public static function getApplicationToken(): ?string
    {
        $cacheFile = sys_get_temp_dir() . '/finovo_ebay_app_token_' . md5(env('EBAY_APP_ID', '') . self::apiBase()) . '.json';
        $cached = is_readable($cacheFile) ? json_decode((string) file_get_contents($cacheFile), true) : null;

        if (is_array($cached) && ($cached['expires_at'] ?? 0) > time() + 300) {
            return $cached['token'];
        }

        $result = self::tokenRequest([
            'grant_type' => 'client_credentials',
            'scope' => 'https://api.ebay.com/oauth/api_scope',
        ]);

        if (!$result['ok']) {
            error_log('eBay application token failed: ' . $result['message']);
            return null;
        }

        @file_put_contents($cacheFile, json_encode([
            'token' => $result['access_token'],
            'expires_at' => time() + (int) $result['expires_in'],
        ]));

        return $result['access_token'];
    }

    private static function tokenRequest(array $fields): array
    {
        $clientId = env('EBAY_APP_ID', '');
        $clientSecret = env('EBAY_CERT_ID', '');

        if ($clientId === '' || $clientSecret === '') {
            return ['ok' => false, 'message' => 'EBAY_APP_ID / EBAY_CERT_ID are not set in .env'];
        }

        $ch = curl_init(self::apiBase() . '/identity/v1/oauth2/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Basic ' . base64_encode($clientId . ':' . $clientSecret),
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, curlVerifySsl());
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode((string) $response, true);

        if ($httpCode !== 200 || empty($data['access_token'])) {
            $message = $data['error_description'] ?? $data['error'] ?? $response;
            return ['ok' => false, 'message' => "eBay token error (HTTP {$httpCode}): " . $message];
        }

        return [
            'ok' => true,
            'access_token' => $data['access_token'],
            'expires_in' => (int) ($data['expires_in'] ?? 7200),
            'refresh_token' => $data['refresh_token'] ?? null,
            'refresh_token_expires_in' => (int) ($data['refresh_token_expires_in'] ?? 0),
        ];
    }
}
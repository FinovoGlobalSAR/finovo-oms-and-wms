<?php
require_once __DIR__ . '/../config/app.php';

class SignedToken
{
    public static function secret(): string
    {
        $key = trim((string) env('APP_KEY', ''));
        if (strlen($key) >= 32) {
            return $key;
        }

        $file = __DIR__ . '/../config/.app_key';
        if (is_file($file)) {
            $saved = trim((string) file_get_contents($file));
            if (strlen($saved) >= 32) {
                return $saved;
            }
        }

        $generated = bin2hex(random_bytes(32));
        @file_put_contents($file, $generated);
        @chmod($file, 0600);
        return $generated;
    }

    /** $ttlSeconds = 0 → kabhi expire nahi (jaise PrestaShop connection code) */
    public static function make(array $data, int $ttlSeconds = 0): string
    {
        if ($ttlSeconds > 0) {
            $data['exp'] = time() + $ttlSeconds;
        }
        $body = self::b64(json_encode($data));
        return $body . '.' . self::b64(hash_hmac('sha256', $body, self::secret(), true));
    }

    /** Sahi, expire na hua, aur isi kaam ($purpose) ka token ho to us ka data; warna null */
    public static function verify(string $token, string $purpose): ?array
    {
        $parts = explode('.', trim($token));
        if (count($parts) !== 2) {
            return null;
        }
        [$body, $sig] = $parts;

        $expected = self::b64(hash_hmac('sha256', $body, self::secret(), true));
        if (!hash_equals($expected, $sig)) {
            return null;
        }

        $data = json_decode((string) base64_decode(strtr($body, '-_', '+/')), true);
        if (!is_array($data) || ($data['purpose'] ?? '') !== $purpose) {
            return null;
        }
        if (isset($data['exp']) && (int) $data['exp'] < time()) {
            return null;
        }
        return $data;
    }

    private static function b64(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }
}
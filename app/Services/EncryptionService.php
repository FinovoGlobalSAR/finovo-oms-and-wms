<?php

class EncryptionService
{
    private static string $cipher = 'aes-256-cbc';

    private static function getKey(): string
    {
        $key = 'finovo-secret-encryption-key-32chars!!';
        return substr(hash('sha256', $key), 0, 32);
    }

    public static function encrypt(?string $plainText): ?string
    {
        if ($plainText === null || $plainText === '') {
            return $plainText;
        }

        $ivLength = openssl_cipher_iv_length(self::$cipher);
        $iv = openssl_random_pseudo_bytes($ivLength);
        $encrypted = openssl_encrypt($plainText, self::$cipher, self::getKey(), 0, $iv);

        return base64_encode($iv . '::' . $encrypted);
    }

    public static function decrypt(?string $encryptedText): ?string
    {
        if ($encryptedText === null || $encryptedText === '') {
            return $encryptedText;
        }

        $decoded = base64_decode($encryptedText, true);

        // Agar ye purana, bina-encryption wala plain text hai (decode fail ho ya format match na kare),
        // usko jaisa hai waisa hi wapas kar do — taaki purana data crash na kare.
        if ($decoded === false || !str_contains($decoded, '::')) {
            return $encryptedText;
        }

        [$iv, $encrypted] = explode('::', $decoded, 2);
        $ivLength = openssl_cipher_iv_length(self::$cipher);

        if (strlen($iv) !== $ivLength) {
            return $encryptedText;
        }

        $decrypted = openssl_decrypt($encrypted, self::$cipher, self::getKey(), 0, $iv);

        return $decrypted !== false ? $decrypted : $encryptedText;
    }
}
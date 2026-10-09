<?php

class WooCommerceAuth
{
    public static function url(string $method, string $url, string $consumerKey, string $consumerSecret): string
    {
        if (stripos($url, 'https://') === 0 || $consumerKey === '' || $consumerSecret === '') {
            return $url;
        }

        $parts = parse_url($url);
        $base = ($parts['scheme'] ?? 'http') . '://' . ($parts['host'] ?? '')
            . (isset($parts['port']) ? ':' . $parts['port'] : '')
            . ($parts['path'] ?? '');

        $params = [];
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $params);
        }

        $params['oauth_consumer_key'] = $consumerKey;
        $params['oauth_timestamp'] = (string) time();
        $params['oauth_nonce'] = bin2hex(random_bytes(16));
        $params['oauth_signature_method'] = 'HMAC-SHA256';
        $params['oauth_signature'] = self::signature($method, $base, $params, $consumerSecret);

        return $base . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }

    /** WooCommerce ke check_oauth_signature() ka hi hisaab (public — tests isay check karte hain) */
    public static function signature(string $method, string $baseUrl, array $params, string $consumerSecret): string
    {
        unset($params['oauth_signature']);
        uksort($params, 'strcmp');

        $pairs = [];
        foreach ($params as $key => $value) {
            $pairs[] = self::encode(self::encode((string) $key) . '=' . self::encode((string) $value));
        }

        $stringToSign = strtoupper($method) . '&' . rawurlencode($baseUrl) . '&' . implode('%26', $pairs);
        return base64_encode(hash_hmac('sha256', $stringToSign, $consumerSecret . '&', true));
    }

    /** WooCommerce ka wc_rest_urlencode_rfc3986() */
    private static function encode(string $value): string
    {
        return str_replace(['+', '%7E'], [' ', '~'], rawurlencode($value));
    }
}
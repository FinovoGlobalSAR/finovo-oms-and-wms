<?php

class Csrf
{
    /** In POST URLs pe token check nahi (server-to-server; apna signature/key check hota hai) */
    private const EXEMPT_POST_PREFIXES = [
        '/webhooks/',
        '/api/',
        '/woocommerce-connect/callback',
        '/prestashop-connect/receive-key',
    ];

    /** Login se pehle wale forms (in pe sirf "doosri website se aayi?" wala check) */
    private const PRE_LOGIN_POSTS = ['/login', '/forgot-password', '/reset-password/verify', '/reset-password/set'];

    /** Yeh GET links kaam karte hain (data badalte hain) — doosri website se khulein to rok do */
    private const ACTION_GET_PATTERN = '#^/(logout'
        . '|stores/(switch|exit-management)'
        . '|orders/(push|push-tracking|export-shopify|export-woocommerce|cj-refresh|sync-[a-z-]+)'
        . '|products/(export-shopify|export-woocommerce|sync-[a-z-]+))$#';

    public static function token(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return '';
        }
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    /** Router se har request pe */
    public static function check(string $uri, string $method): void
    {
        $method = strtoupper($method);
        $isPost = $method === 'POST';

        foreach (self::EXEMPT_POST_PREFIXES as $prefix) {
            if ($isPost && str_starts_with($uri, $prefix)) {
                return;
            }
        }

        $crossSite = strtolower($_SERVER['HTTP_SEC_FETCH_SITE'] ?? '') === 'cross-site';

        if ($isPost) {
            if ($crossSite) {
                self::fail();
            }
            if (in_array($uri, self::PRE_LOGIN_POSTS, true)) {
                return;
            }

            $sent = (string) ($_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
            $expected = (string) ($_SESSION['_csrf'] ?? '');
            if ($expected === '' || $sent === '' || !hash_equals($expected, $sent)) {
                self::fail();
            }
            return;
        }

        if ($crossSite && preg_match(self::ACTION_GET_PATTERN, $uri)) {
            self::fail();
        }
    }

    private static function fail(): void
    {
        http_response_code(419);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Session expired</title></head>'
            . '<body style="font-family:Inter,system-ui,sans-serif; background:#f5f7fb; display:flex; align-items:center; justify-content:center; min-height:100vh; margin:0;">'
            . '<div style="background:#fff; border:1px solid #e5e7eb; border-radius:14px; padding:28px 32px; max-width:440px; text-align:center;">'
            . '<h1 style="font-size:18px; margin:0 0 8px; color:#0f172a;">Page expired</h1>'
            . '<p style="font-size:14px; color:#475569; margin:0 0 18px;">This request could not be verified. Please go back, refresh the page and try again.</p>'
            . '<a href="/dashboard" style="display:inline-block; background:#1d4ed8; color:#fff; padding:8px 16px; border-radius:8px; text-decoration:none; font-size:14px;">Go to Dashboard</a>'
            . '</div></body></html>';
        exit;
    }
}
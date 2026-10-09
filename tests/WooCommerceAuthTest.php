<?php
require_once __DIR__ . '/../app/Services/WooCommerceAuth.php';

use PHPUnit\Framework\TestCase;

/**
 * http:// WooCommerce pe OAuth 1.0a signature lagta hai, https:// pe URL waisa hi rehta hai.
 */
class WooCommerceAuthTest extends TestCase
{
    public function testHttpsUrlIsLeftUnchanged(): void
    {
        $url = 'https://shop.example.com/wp-json/wc/v3/orders?per_page=20';
        $this->assertSame($url, WooCommerceAuth::url('GET', $url, 'ck_1', 'cs_1'));
    }

    public function testHttpUrlGetsOauthParametersAndKeepsQuery(): void
    {
        $signed = WooCommerceAuth::url('GET', 'http://localhost/wordpress/wp-json/wc/v3/orders?per_page=20', 'ck_1', 'cs_1');
        parse_str((string) parse_url($signed, PHP_URL_QUERY), $q);

        $this->assertStringStartsWith('http://localhost/wordpress/wp-json/wc/v3/orders?', $signed);
        $this->assertSame('20', $q['per_page']);
        $this->assertSame('ck_1', $q['oauth_consumer_key']);
        $this->assertSame('HMAC-SHA256', $q['oauth_signature_method']);
        $this->assertNotEmpty($q['oauth_nonce']);
        $this->assertNotEmpty($q['oauth_signature']);
    }

    public function testSignatureMatchesWooCommerceFormula(): void
    {
        // WooCommerce: METHOD & rawurlencode(base) & sorted, double-encoded "key=value" pairs joined by %26; key = secret + "&"
        $params = ['per_page' => '20', 'oauth_consumer_key' => 'ck_1', 'oauth_nonce' => 'abc', 'oauth_signature_method' => 'HMAC-SHA256', 'oauth_timestamp' => '1700000000'];
        $base = 'http://localhost/wordpress/wp-json/wc/v3/orders';

        $toSign = 'GET&' . rawurlencode($base) . '&'
            . 'oauth_consumer_key%3Dck_1%26oauth_nonce%3Dabc%26oauth_signature_method%3DHMAC-SHA256%26oauth_timestamp%3D1700000000%26per_page%3D20';
        $expected = base64_encode(hash_hmac('sha256', $toSign, 'cs_1&', true));

        $this->assertSame($expected, WooCommerceAuth::signature('GET', $base, $params, 'cs_1'));
    }
}
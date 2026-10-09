<?php
require_once __DIR__ . '/../app/Services/ProductPushService.php';

use PHPUnit\Framework\TestCase;

class ProductPushTest extends TestCase
{
    private function sampleProduct(array $overrides = []): array
    {
        return array_merge(['id' => 7, 'name' => 'Blue Shirt', 'sku' => 'BS-01', 'price' => '15.5'], $overrides);
    }

    public function testEveryPushPlatformHasLabelAndColumn(): void
    {
        foreach (ProductPushService::PLATFORMS as $platform => $info) {
            $this->assertNotEmpty($info['label'], $platform);
            $this->assertMatchesRegularExpression('/^external_[a-z_]+_id$/', $info['column'], $platform);
        }
        // Database se jude stores pe push nahi hota
        $this->assertArrayNotHasKey('opencart', ProductPushService::PLATFORMS);
        $this->assertArrayHasKey('opencart', ProductPushService::NOT_SUPPORTED);
    }

    public function testSkuFallsBackToFinovoId(): void
    {
        $this->assertSame('BS-01', ProductPushService::skuFor($this->sampleProduct()));
        $this->assertSame('FINOVO-7', ProductPushService::skuFor($this->sampleProduct(['sku' => '  '])));
        $this->assertSame('FINOVO-7', ProductPushService::skuFor($this->sampleProduct(['sku' => null])));
    }

    public function testSlug(): void
    {
        $this->assertSame('blue-shirt-large', ProductPushService::slug('Blue Shirt (Large)!'));
        $this->assertSame('product', ProductPushService::slug('!!!'));
    }

    public function testShopifyPayload(): void
    {
        $p = ProductPushService::buildShopifyPayload($this->sampleProduct());
        $this->assertSame('Blue Shirt', $p['product']['title']);
        $this->assertSame('15.50', $p['product']['variants'][0]['price']);
        $this->assertSame('BS-01', $p['product']['variants'][0]['sku']);
        $this->assertSame('shopify', $p['product']['variants'][0]['inventory_management']);

        // SKU khali ho to bheja hi nahi jata
        $p = ProductPushService::buildShopifyPayload($this->sampleProduct(['sku' => '']));
        $this->assertArrayNotHasKey('sku', $p['product']['variants'][0]);
    }

    public function testWooCommerceAndBigCommercePayloadsCarryStock(): void
    {
        $woo = ProductPushService::buildWooCommercePayload($this->sampleProduct(), 12);
        $this->assertSame('15.50', $woo['regular_price']);
        $this->assertSame(12, $woo['stock_quantity']);
        $this->assertTrue($woo['manage_stock']);

        $bc = ProductPushService::buildBigCommercePayload($this->sampleProduct(['sku' => '']), 3);
        $this->assertSame('physical', $bc['type']);
        $this->assertSame(15.5, $bc['price']);
        $this->assertSame(3, $bc['inventory_level']);
        $this->assertArrayNotHasKey('sku', $bc);
    }

    public function testWixPayloadHasOneVariantWithStock(): void
    {
        $p = ProductPushService::buildWixPayload($this->sampleProduct(), 4);
        $variants = $p['product']['variantsInfo']['variants'];
        $this->assertCount(1, $variants);
        $this->assertSame('15.50', $variants[0]['price']['actualPrice']['amount']);
        $this->assertSame(4, $variants[0]['inventoryItem']['quantity']);
        $this->assertStringContainsString('"physicalProperties":{}', json_encode($p));
    }

    public function testMagentoPayload(): void
    {
        $p = ProductPushService::buildMagentoPayload($this->sampleProduct(['sku' => '']), 0)['product'];
        $this->assertSame('FINOVO-7', $p['sku']);
        $this->assertSame('simple', $p['type_id']);
        $this->assertSame(0, $p['extension_attributes']['stock_item']['qty']);
        $this->assertFalse($p['extension_attributes']['stock_item']['is_in_stock']);
    }

    public function testEbayTitleIsCutTo80Characters(): void
    {
        $p = ProductPushService::buildEbayPayload($this->sampleProduct(['name' => str_repeat('a', 100)]), 2);
        $this->assertSame(80, mb_strlen($p['product']['title']));
        $this->assertSame(2, $p['availability']['shipToLocationAvailability']['quantity']);
        $this->assertSame('NEW', $p['condition']);
    }

    public function testPrestaShopXmlIsValidAndUsesShopLanguage(): void
    {
        $xml = ProductPushService::buildPrestaShopXml($this->sampleProduct(['name' => 'Shirt & Tie <XL>']), 3);
        $doc = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        $this->assertNotFalse($doc);
        $this->assertSame('Shirt & Tie <XL>', (string) $doc->product->name->language);
        $this->assertSame('3', (string) $doc->product->name->language['id']);
        $this->assertSame('shirt-tie-xl', (string) $doc->product->link_rewrite->language);
        $this->assertSame('15.50', (string) $doc->product->price);
        $this->assertSame('BS-01', (string) $doc->product->reference);
    }
        // ---------------- Variants (size / color) ----------------

    public function testVariantOptionsFromFinovoLabels(): void
    {
        $opts = ProductPushService::variantOptions([
            ['id' => 1, 'label' => 'S / Red', 'attributes' => 'Size, Color'],
            ['id' => 2, 'label' => 'M / Blue', 'attributes' => 'Size, Color'],
        ]);
        $this->assertSame(['Size', 'Color'], $opts['names']);
        $this->assertSame(['M', 'Blue'], $opts['rows'][1]['values']);

        // Naam aur values match nahi karte → ek hi option "Variant"
        $opts = ProductPushService::variantOptions([['id' => 3, 'label' => 'Gift Box', 'attributes' => null]]);
        $this->assertSame(['Variant'], $opts['names']);
        $this->assertSame(['Gift Box'], $opts['rows'][0]['values']);
    }

    public function testShopifyPayloadWithVariants(): void
    {
        $payload = ProductPushService::buildShopifyPayload(['name' => 'Tee', 'price' => 20, 'sku' => ''], [
            ['id' => 1, 'label' => 'S', 'attributes' => 'Size', 'price' => 0, 'sku' => 'TEE-S'],
            ['id' => 2, 'label' => 'L', 'attributes' => 'Size', 'price' => 25, 'sku' => ''],
        ])['product'];

        $this->assertSame([['name' => 'Size']], $payload['options']);
        $this->assertCount(2, $payload['variants']);
        $this->assertSame(['price' => '20.00', 'inventory_management' => 'shopify', 'option1' => 'S', 'sku' => 'TEE-S'], $payload['variants'][0]);
        $this->assertSame('25.00', $payload['variants'][1]['price']); // variant ka apna price
    }

    public function testWooCommerceVariableProductAndVariation(): void
    {
        $variants = [
            ['id' => 1, 'label' => 'S / Red', 'attributes' => 'Size, Color', 'price' => 0, 'sku' => 'A', 'stock_quantity' => 4],
            ['id' => 2, 'label' => 'S / Blue', 'attributes' => 'Size, Color', 'price' => 0, 'sku' => 'B', 'stock_quantity' => 0],
        ];
        $product = ['name' => 'Tee', 'price' => 20, 'sku' => 'TEE'];

        $payload = ProductPushService::buildWooCommerceVariablePayload($product, $variants);
        $this->assertSame('variable', $payload['type']);
        $this->assertSame(['S'], $payload['attributes'][0]['options']);           // "S" sirf ek dafa
        $this->assertSame(['Red', 'Blue'], $payload['attributes'][1]['options']);

        $opts = ProductPushService::variantOptions($variants);
        $variation = ProductPushService::buildWooCommerceVariation($product, $opts['rows'][0], $opts['names']);
        $this->assertSame(4, $variation['stock_quantity']);
        $this->assertTrue($variation['manage_stock']);
        $this->assertSame([['name' => 'Size', 'option' => 'S'], ['name' => 'Color', 'option' => 'Red']], $variation['attributes']);
    }
}
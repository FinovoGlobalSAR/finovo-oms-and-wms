<?php
require_once __DIR__ . '/../app/Services/OrderPushService.php';

use PHPUnit\Framework\TestCase;

/**
 * Order Push ke payload (jo data platform ko jata hai) sahi bante hain ya nahi.
 * Yeh tests internet pe kuch nahi bhejte — sirf data ki shakal check karte hain.
 */
class OrderPushTest extends TestCase
{
    private function sampleOrder(array $overrides = []): array
    {
        return array_merge([
            'id' => 15,
            'customer_name' => 'Ali Raza Khan',
            'customer_email' => 'ali@example.com',
            'customer_phone' => '0300-1234567',
            'shipping_address' => 'House 5, DHA Phase 6',
            'billing_address' => null,
            'payment_status' => 'unpaid',
            'payment_method' => 'COD',
            'shipping_cost' => 0,
            'tax' => 0,
            'discount' => 0,
        ], $overrides);
    }

    /** Do products wala order: 2 × Blue Shirt (15.50) + 1 × Cap (5.00) */
    private function sampleLines(array $shirtProduct = [], array $capProduct = []): array
    {
        return [
            ['name' => 'Blue Shirt', 'quantity' => 2, 'unit_price' => 15.5, 'product' => $shirtProduct ?: null],
            ['name' => 'Cap', 'quantity' => 1, 'unit_price' => 5.0, 'product' => $capProduct ?: null],
        ];
    }

    public function testSplitNameHandlesSingleAndMultipleWords(): void
    {
        $this->assertSame(['Ali', 'Raza Khan'], OrderPushService::splitName('Ali Raza Khan'));
        $this->assertSame(['Ali', '-'], OrderPushService::splitName('Ali'));
        $this->assertSame(['Customer', '-'], OrderPushService::splitName('   '));
    }

    public function testMissingEmailFallsBackToDefault(): void
    {
        $this->assertSame(OrderPushService::DEFAULT_EMAIL, OrderPushService::orderEmail(['customer_email' => 'not-an-email']));
        $this->assertSame('a@b.com', OrderPushService::orderEmail(['customer_email' => 'a@b.com']));
    }

    public function testRowsSplitAcrossWarehousesAreMergedIntoOneLine(): void
    {
        // Ek product 2 warehouses se nikla (2 rows) + doosra product
        $rows = [
            ['product_id' => 7, 'variant_id' => null, 'product_name' => 'Blue Shirt', 'quantity' => 3, 'price' => '15.50'],
            ['product_id' => 7, 'variant_id' => null, 'product_name' => 'Blue Shirt', 'quantity' => 2, 'price' => '15.50'],
            ['product_id' => 8, 'variant_id' => null, 'product_name' => 'Cap', 'quantity' => 1, 'price' => '5.00'],
        ];
        $lines = OrderPushService::linesFromRows($rows, [7 => ['id' => 7, 'sku' => 'SH-1']]);

        $this->assertCount(2, $lines);
        $this->assertSame(5, $lines[0]['quantity']);
        $this->assertSame('SH-1', $lines[0]['product']['sku']);
        $this->assertNull($lines[1]['product']);
    }

    public function testOrderTotalUsesAllLinesPlusCharges(): void
    {
        $order = $this->sampleOrder(['shipping_cost' => 5, 'tax' => 2, 'discount' => 1]);
        // 15.50×2 + 5×1 + 5 + 2 − 1 = 42
        $this->assertSame(42.0, OrderPushService::orderTotal($order, $this->sampleLines()));
    }

    public function testShopifyPayloadSendsEveryLine(): void
    {
        $payload = OrderPushService::buildShopifyPayload($this->sampleOrder(), $this->sampleLines());

        $this->assertCount(2, $payload['order']['line_items']);
        $this->assertSame('15.50', $payload['order']['line_items'][0]['price']);
        $this->assertSame('Cap', $payload['order']['line_items'][1]['title']);
        $this->assertSame('pending', $payload['order']['financial_status']);
    }

    public function testBigCommerceMixesLinkedAndCustomProducts(): void
    {
        $payload = OrderPushService::buildBigCommercePayload($this->sampleOrder(), $this->sampleLines(['external_bc_product_id' => '88']));

        $this->assertSame(['product_id' => 88, 'quantity' => 2], $payload['products'][0]);
        $this->assertSame('Cap', $payload['products'][1]['name']);
        $this->assertSame(1, $payload['status_id'], 'Unpaid order should be Pending (1)');
        $this->assertSame('ali@example.com', $payload['billing_address']['email']);
    }

    public function testBigCommercePaidOrderIsAwaitingFulfillment(): void
    {
        $payload = OrderPushService::buildBigCommercePayload($this->sampleOrder(['payment_status' => 'paid']), $this->sampleLines());
        $this->assertSame(11, $payload['status_id']);
    }

    public function testWixPayloadHasAllLinesTotalsAndCatalogReference(): void
    {
        $payload = OrderPushService::buildWixPayload($this->sampleOrder(), $this->sampleLines(['external_wix_product_id' => 'abc-guid']));
        $items = $payload['order']['lineItems'];

        $this->assertCount(2, $items);
        $this->assertSame('abc-guid', $items[0]['catalogReference']['catalogItemId']);
        $this->assertArrayNotHasKey('catalogReference', $items[1]);
        $this->assertSame('36.00', $payload['order']['priceSummary']['total']['amount']);
        $this->assertSame('finovo-15', $payload['order']['channelInfo']['externalOrderId']);
    }

    public function testMagentoAddressHasAllRequiredFields(): void
    {
        $address = OrderPushService::buildMagentoAddress($this->sampleOrder());

        foreach (['firstname', 'lastname', 'street', 'city', 'postcode', 'country_id', 'telephone', 'email'] as $field) {
            $this->assertNotEmpty($address[$field], "Magento address field {$field} is empty");
        }
        $this->assertSame(['House 5, DHA Phase 6'], $address['street']);
    }

    public function testPrestaShopNameCleaningRemovesDigitsAndSymbols(): void
    {
        $this->assertSame('Ali nd', OrderPushService::cleanPrestaShopName('Ali 2nd'));
        $this->assertSame('Customer', OrderPushService::cleanPrestaShopName('12345'));
        $this->assertSame('House DHA', OrderPushService::cleanPrestaShopAddress('House @ DHA'));
    }

    public function testEbayFulfillmentPayloadMapsCarrierAndLineItems(): void
    {
        $payload = OrderPushService::buildEbayFulfillmentPayload(
            [['lineItemId' => 'LI-1', 'quantity' => 2], ['quantity' => 1]],
            'AWB123',
            'DHL Express',
            '2026-10-05 10:00:00'
        );

        $this->assertSame([['lineItemId' => 'LI-1', 'quantity' => 2]], $payload['lineItems'], 'Line items without an ID are skipped');
        $this->assertSame('DHL', $payload['shippingCarrierCode']);
        $this->assertSame('AWB123', $payload['trackingNumber']);
        $this->assertSame('Other', OrderPushService::ebayCarrierCode('Leopards Courier'));
    }
}
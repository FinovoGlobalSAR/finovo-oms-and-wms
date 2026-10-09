<?php
/**
 * Har platform ka order alag shakal mein aata hai. Yeh class us mein se Finovo ko jo chahiye
 * woh nikaalti hai — sab se zaroori: order ki SAARI lines (products), ek hi format mein:
 *    ['name' => .., 'quantity' => .., 'unit_price' => (EK item ki price), 'external_product_id' => ..]
 *
 * Sync button, Auto-Sync aur webhooks teeno yahi functions use karte hain, taake teeno ek jaisa kaam karein.
 */
class PlatformOrderMapper
{
    /** Shopify order ki lines */
    public static function shopifyLines(array $order): array
    {
        $lines = [];
        foreach (($order['line_items'] ?? []) as $li) {
            $lines[] = [
                'name' => $li['title'] ?? $li['name'] ?? '',
                'quantity' => (int) ($li['quantity'] ?? 0),
                'unit_price' => (float) ($li['price'] ?? 0),
                'external_product_id' => isset($li['product_id']) ? (string) $li['product_id'] : null,
            ];
        }
        return $lines;
    }

    /** WooCommerce order ki lines */
    public static function wooLines(array $order): array
    {
        $lines = [];
        foreach (($order['line_items'] ?? []) as $li) {
            $qty = (int) ($li['quantity'] ?? 0);
            // "price" ek item ki price hai; na ho to line ka total / quantity
            $unit = isset($li['price']) ? (float) $li['price'] : ($qty > 0 ? (float) ($li['total'] ?? 0) / $qty : 0);
            $lines[] = [
                'name' => $li['name'] ?? '',
                'quantity' => $qty,
                'unit_price' => $unit,
                'external_product_id' => !empty($li['product_id']) ? (string) $li['product_id'] : null,
            ];
        }
        return $lines;
    }

    /** BigCommerce status → [Finovo status, payment status] */
    public static function bigCommerceStatus(string $bcStatus): array
    {
        $orderStatus = 'pending';
        if (str_contains($bcStatus, 'Shipped') || str_contains($bcStatus, 'Completed')) {
            $orderStatus = 'delivered';
        } elseif (str_contains($bcStatus, 'Cancelled') || str_contains($bcStatus, 'Declined') || str_contains($bcStatus, 'Refunded')) {
            $orderStatus = 'cancelled';
        } elseif (in_array($bcStatus, ['Awaiting Fulfillment', 'Partially Shipped'], true)) {
            $orderStatus = 'processing';
        }

        $paymentStatus = in_array($bcStatus, ['Awaiting Fulfillment', 'Shipped', 'Partially Shipped', 'Completed'], true) ? 'paid' : 'unpaid';
        return [$orderStatus, $paymentStatus];
    }

    /** BigCommerce /orders/{id}/products ki lines */
    public static function bigCommerceLines(array $products): array
    {
        $lines = [];
        foreach ($products as $p) {
            if (!is_array($p)) continue;
            $lines[] = [
                'name' => $p['name'] ?? '',
                'quantity' => (int) ($p['quantity'] ?? 0),
                'unit_price' => (float) ($p['price_inc_tax'] ?? $p['base_price'] ?? 0),
                'external_product_id' => !empty($p['product_id']) ? (string) $p['product_id'] : null,
            ];
        }
        return $lines;
    }

    /** PrestaShop order (display=full) ki lines — associations → order_rows */
    public static function prestaShopLines(array $order): array
    {
        $rows = $order['associations']['order_rows'] ?? [];
        if (isset($rows['product_id'])) {
            $rows = [$rows]; // sirf ek row ho to kabhi kabhi array ki jagah seedha object aata hai
        }

        $lines = [];
        foreach ((is_array($rows) ? $rows : []) as $r) {
            $lines[] = [
                'name' => $r['product_name'] ?? '',
                'quantity' => (int) ($r['product_quantity'] ?? 0),
                'unit_price' => (float) ($r['unit_price_tax_incl'] ?? $r['product_price'] ?? 0),
                'external_product_id' => !empty($r['product_id']) ? (string) $r['product_id'] : null,
            ];
        }
        return $lines;
    }

    /** Wix order → [Finovo status, payment status] */
    public static function wixStatus(array $order): array
    {
        $orderStatus = match ($order['status'] ?? 'APPROVED') {
            'FULFILLED' => 'delivered',
            'CANCELED' => 'cancelled',
            'APPROVED', 'PARTIALLY_FULFILLED' => 'processing',
            default => 'pending',
        };
        $paymentStatus = ($order['paymentStatus'] ?? '') === 'PAID' ? 'paid' : 'unpaid';
        return [$orderStatus, $paymentStatus];
    }

    public static function wixCustomerName(array $order, string $externalId): string
    {
        $buyer = $order['buyerInfo'] ?? [];
        $contact = $order['billingInfo']['contactDetails'] ?? [];
        $name = trim(($buyer['firstName'] ?? $contact['firstName'] ?? '') . ' ' . ($buyer['lastName'] ?? $contact['lastName'] ?? ''));
        if ($name === '') {
            $name = $buyer['email'] ?? ('Order #' . $externalId);
        }
        return $name;
    }

    /** Wix order ki lines */
    public static function wixLines(array $order): array
    {
        $lines = [];
        foreach (($order['lineItems'] ?? []) as $li) {
            $lines[] = [
                'name' => $li['productName']['original'] ?? '',
                'quantity' => (int) ($li['quantity'] ?? 0),
                'unit_price' => (float) ($li['price']['amount'] ?? 0),
                'external_product_id' => $li['catalogReference']['catalogItemId'] ?? null,
            ];
        }
        return $lines;
    }

    /**
     * Magento order ki lines. Configurable product mein Magento do rows deta hai:
     * parent (price wali) + child (asli simple product). Child ka product, parent ki price/quantity.
     */
    public static function magentoLines(array $order): array
    {
        $items = $order['items'] ?? [];
        $childrenByParent = [];
        foreach ($items as $item) {
            if (!empty($item['parent_item_id'])) {
                $childrenByParent[(string) $item['parent_item_id']] = $item;
            }
        }

        $lines = [];
        foreach ($items as $item) {
            if (!empty($item['parent_item_id'])) {
                continue; // child — parent ke saath handle ho gaya
            }
            $product = $childrenByParent[(string) ($item['item_id'] ?? '')] ?? $item;
            $lines[] = [
                'name' => $product['name'] ?? $item['name'] ?? '',
                'quantity' => (int) ($item['qty_ordered'] ?? 0),
                'unit_price' => (float) ($item['price_incl_tax'] ?? $item['price'] ?? 0),
                'external_product_id' => !empty($product['product_id']) ? (string) $product['product_id'] : null,
            ];
        }
        return $lines;
    }

    /** eBay order ki lines — eBay "lineItemCost" poori line ka hota hai, is liye quantity se divide */
    public static function ebayLines(array $order): array
    {
        $lines = [];
        foreach (($order['lineItems'] ?? []) as $li) {
            $qty = (int) ($li['quantity'] ?? 0);
            $lineTotal = (float) ($li['lineItemCost']['value'] ?? 0);
            $lines[] = [
                'name' => $li['title'] ?? '',
                'quantity' => $qty,
                'unit_price' => $qty > 0 ? round($lineTotal / $qty, 2) : 0,
                'external_product_id' => !empty($li['sku']) ? (string) $li['sku'] : null,
            ];
        }
        return $lines;
    }

    // =====================================================================
    //  Customer details (kharidne wala) — har platform ke order se
    // =====================================================================

    /** Address ke hisse jor ke ek line: "House 5, Street 9, DHA, Karachi 75500" (khali hisse chhod do) */
    public static function joinAddress(array $parts): string
    {
        $clean = [];
        foreach ($parts as $part) {
            $part = trim((string) $part);
            if ($part !== '' && !in_array($part, $clean, true)) {
                $clean[] = $part;
            }
        }
        return implode(', ', $clean);
    }

    /**
     * Platform ke order se customer ki details:
     *   ['name', 'email', 'phone', 'address', 'city', 'country', 'external_customer_id']
     * Jo cheez platform na de woh khali string. ($platform = 'shopify', 'woocommerce', 'bigcommerce',
     * 'wix', 'magento', 'ebay', 'bridge')
     */
    public static function customer(string $platform, array $order): array
    {
        $c = ['name' => '', 'email' => '', 'phone' => '', 'address' => '', 'city' => '', 'country' => '', 'external_customer_id' => ''];

        switch ($platform) {
            case 'shopify':
                $ship = $order['shipping_address'] ?? $order['billing_address'] ?? [];
                $cust = $order['customer'] ?? [];
                $c['name'] = trim(($cust['first_name'] ?? '') . ' ' . ($cust['last_name'] ?? '')) ?: (string) ($ship['name'] ?? '');
                $c['email'] = (string) ($order['email'] ?? $order['contact_email'] ?? $cust['email'] ?? '');
                $c['phone'] = (string) ($order['phone'] ?? $ship['phone'] ?? $cust['phone'] ?? '');
                $c['address'] = self::joinAddress([$ship['address1'] ?? '', $ship['address2'] ?? '', $ship['city'] ?? '', $ship['province'] ?? '', $ship['zip'] ?? '']);
                $c['city'] = (string) ($ship['city'] ?? '');
                $c['country'] = (string) ($ship['country'] ?? $ship['country_code'] ?? '');
                $c['external_customer_id'] = (string) ($cust['id'] ?? '');
                break;

            case 'woocommerce':
                $bill = $order['billing'] ?? [];
                $ship = $order['shipping'] ?? [];
                $addr = !empty($ship['address_1']) ? $ship : $bill;
                $c['name'] = trim(($bill['first_name'] ?? '') . ' ' . ($bill['last_name'] ?? ''));
                $c['email'] = (string) ($bill['email'] ?? '');
                $c['phone'] = (string) ($bill['phone'] ?? $ship['phone'] ?? '');
                $c['address'] = self::joinAddress([$addr['address_1'] ?? '', $addr['address_2'] ?? '', $addr['city'] ?? '', $addr['state'] ?? '', $addr['postcode'] ?? '']);
                $c['city'] = (string) ($addr['city'] ?? '');
                $c['country'] = (string) ($addr['country'] ?? '');
                // 0 = guest checkout (WooCommerce account nahi)
                $c['external_customer_id'] = !empty($order['customer_id']) ? (string) $order['customer_id'] : '';
                break;

            case 'bigcommerce':
                $bill = $order['billing_address'] ?? [];
                $c['name'] = trim(($bill['first_name'] ?? '') . ' ' . ($bill['last_name'] ?? ''));
                $c['email'] = (string) ($bill['email'] ?? '');
                $c['phone'] = (string) ($bill['phone'] ?? '');
                $c['address'] = self::joinAddress([$bill['street_1'] ?? '', $bill['street_2'] ?? '', $bill['city'] ?? '', $bill['state'] ?? '', $bill['zip'] ?? '']);
                $c['city'] = (string) ($bill['city'] ?? '');
                $c['country'] = (string) ($bill['country'] ?? '');
                $c['external_customer_id'] = !empty($order['customer_id']) ? (string) $order['customer_id'] : '';
                break;

            case 'wix':
                $buyer = $order['buyerInfo'] ?? [];
                $contact = $order['billingInfo']['contactDetails'] ?? [];
                $shipDest = $order['shippingInfo']['logistics']['shippingDestination'] ?? [];
                $addr = $shipDest['address'] ?? ($order['billingInfo']['address'] ?? []);
                $shipContact = $shipDest['contactDetails'] ?? [];
                $c['name'] = trim(($contact['firstName'] ?? $shipContact['firstName'] ?? '') . ' ' . ($contact['lastName'] ?? $shipContact['lastName'] ?? ''));
                $c['email'] = (string) ($buyer['email'] ?? '');
                $c['phone'] = (string) ($contact['phone'] ?? $shipContact['phone'] ?? '');
                $c['address'] = self::joinAddress([$addr['addressLine1'] ?? $addr['addressLine'] ?? '', $addr['addressLine2'] ?? '', $addr['city'] ?? '', $addr['subdivision'] ?? '', $addr['postalCode'] ?? '']);
                $c['city'] = (string) ($addr['city'] ?? '');
                $c['country'] = (string) ($addr['country'] ?? '');
                $c['external_customer_id'] = (string) ($buyer['contactId'] ?? $buyer['memberId'] ?? '');
                break;

            case 'magento':
                $bill = $order['billing_address'] ?? [];
                $ship = $order['extension_attributes']['shipping_assignments'][0]['shipping']['address'] ?? $bill;
                $street = is_array($ship['street'] ?? null) ? $ship['street'] : [(string) ($ship['street'] ?? '')];
                $c['name'] = trim(($order['customer_firstname'] ?? $bill['firstname'] ?? '') . ' ' . ($order['customer_lastname'] ?? $bill['lastname'] ?? ''));
                $c['email'] = (string) ($order['customer_email'] ?? $bill['email'] ?? '');
                $c['phone'] = (string) ($ship['telephone'] ?? $bill['telephone'] ?? '');
                $c['address'] = self::joinAddress(array_merge($street, [$ship['city'] ?? '', $ship['region'] ?? '', $ship['postcode'] ?? '']));
                $c['city'] = (string) ($ship['city'] ?? '');
                $c['country'] = (string) ($ship['country_id'] ?? '');
                $c['external_customer_id'] = !empty($order['customer_id']) ? (string) $order['customer_id'] : '';
                break;

            case 'ebay':
                $shipTo = $order['fulfillmentStartInstructions'][0]['shippingStep']['shipTo'] ?? [];
                $addr = $shipTo['contactAddress'] ?? [];
                $c['name'] = (string) ($shipTo['fullName'] ?? '');
                $c['email'] = (string) ($shipTo['email'] ?? $order['buyer']['buyerRegistrationAddress']['email'] ?? '');
                $c['phone'] = (string) ($shipTo['primaryPhone']['phoneNumber'] ?? '');
                $c['address'] = self::joinAddress([$addr['addressLine1'] ?? '', $addr['addressLine2'] ?? '', $addr['city'] ?? '', $addr['stateOrProvince'] ?? '', $addr['postalCode'] ?? '']);
                $c['city'] = (string) ($addr['city'] ?? '');
                $c['country'] = (string) ($addr['countryCode'] ?? '');
                $c['external_customer_id'] = (string) ($order['buyer']['username'] ?? '');
                break;

            case 'bridge':
                $c['name'] = (string) ($order['customer_name'] ?? '');
                $c['email'] = (string) ($order['customer_email'] ?? '');
                $c['phone'] = (string) ($order['customer_phone'] ?? '');
                $c['address'] = (string) ($order['shipping_address'] ?? $order['customer_address'] ?? '');
                $c['city'] = (string) ($order['customer_city'] ?? '');
                $c['country'] = (string) ($order['customer_country'] ?? '');
                $c['external_customer_id'] = (string) ($order['customer_id'] ?? '');
                break;
        }

        return array_map(fn($v) => trim((string) $v), $c);
    }

    /**
     * Custom Bridge (customer ki apni website) — naya format: "items": [{product_id, name, quantity, price}, ...]
     * Purana format (ek product: product_id + quantity + total_price) bhi chalta hai.
     */
    public static function bridgeLines(array $data): array
    {
        if (!empty($data['items']) && is_array($data['items'])) {
            $lines = [];
            foreach ($data['items'] as $item) {
                $externalProductId = (string) ($item['product_id'] ?? '');
                $lines[] = [
                    'name' => trim((string) ($item['name'] ?? '')) ?: 'Bridge Product #' . $externalProductId,
                    'quantity' => (int) ($item['quantity'] ?? 0),
                    'unit_price' => (float) ($item['price'] ?? 0),
                    'external_product_id' => $externalProductId !== '' ? $externalProductId : null,
                ];
            }
            return $lines;
        }

        $externalProductId = (string) ($data['product_id'] ?? '');
        $qty = max(1, (int) ($data['quantity'] ?? 1));
        return [[
            'name' => 'Bridge Product #' . $externalProductId,
            'quantity' => $qty,
            'unit_price' => round((float) ($data['total_price'] ?? 0) / $qty, 2),
            'external_product_id' => $externalProductId !== '' ? $externalProductId : null,
        ]];
    }
}
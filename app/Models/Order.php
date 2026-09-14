<?php
// app/Models/Order.php
require_once __DIR__ . '/../../core/Model.php';

class Order extends Model
{
    public function all(): array
    {
        $stmt = $this->query("SELECT * FROM orders ORDER BY id DESC");
        return $stmt->fetchAll();
    }

    public function filterBySource(string $source): array
    {
        $stmt = $this->query("SELECT * FROM orders WHERE source = ? ORDER BY id DESC", [$source]);
        return $stmt->fetchAll();
    }

    public function create(string $customerName, string $productName, int $quantity, float $price, string $source = 'manual'): int
    {
        $this->query(
            "INSERT INTO orders (customer_name, product_name, quantity, price, source) VALUES (?, ?, ?, ?, ?)",
            [$customerName, $productName, $quantity, $price, $source]
        );
        return (int) $this->db->lastInsertId();
    }

    // Invoice ke liye — order + uske saare line items (order_group se) ek saath, currency ke sath
    public function findWithItems(int $orderId): ?array
    {
        $stmt = $this->query("SELECT * FROM orders WHERE id = ?", [$orderId]);
        $main = $stmt->fetch();

        if (!$main) {
            return null;
        }

        if (!empty($main['order_group'])) {
            $groupStmt = $this->query(
                "SELECT * FROM orders WHERE order_group = ? ORDER BY id ASC",
                [$main['order_group']]
            );
            $rows = $groupStmt->fetchAll();
        } else {
            $rows = [$main];
        }

        $storeStmt = $this->query("SELECT name FROM stores WHERE id = ?", [$main['store_id']]);
        $storeRow = $storeStmt->fetch();

        // Currency 2 tarike se decide hoti hai (jo bhi sach ho use lo):
        // 1. Order khud Shopify/WooCommerce se pull hua ho, YA
        // 2. Order ke andar jo product hai, uski apni origin Shopify/WooCommerce ho
        //    (chahe order manual/CSV/API push ke through banaya gaya ho)
        $externalSources = ['shopify_pull', 'woocommerce_pull'];
        $currency = in_array($main['source'] ?? 'manual', $externalSources, true) ? '$' : 'Rs.';

        $items = [];
        $subtotal = 0;

        foreach ($rows as $row) {
            $lineTotal = (float) $row['price'] * (int) $row['quantity'];
            $subtotal += $lineTotal;

            $productSku = null;
            if (!empty($row['product_id'])) {
                $pStmt = $this->query(
                    "SELECT sku, external_product_id, external_wc_product_id FROM products WHERE id = ?",
                    [$row['product_id']]
                );
                $p = $pStmt->fetch();
                $productSku = $p['sku'] ?? null;

                // Agar is item ka product khud Shopify/WooCommerce se pull hua hai,
                // to invoice ki currency $ ho jaye — order kahin se bhi bana ho.
                if (!empty($p['external_product_id']) || !empty($p['external_wc_product_id'])) {
                    $currency = '$';
                }
            }

            $items[] = [
                'product_name' => $row['product_name'],
                'sku' => $productSku,
                'variant' => $row['variant_label'] ?? null,
                'quantity' => (int) $row['quantity'],
                'unit_price' => (float) $row['price'],
                'line_total' => $lineTotal,
            ];
        }

        $discount = (float) ($main['discount'] ?? 0);
        $shipping = (float) ($main['shipping_cost'] ?? 0);
        $tax = (float) ($main['tax'] ?? 0);
        $grandTotal = $subtotal - $discount + $shipping + $tax;

        return [
            'id' => $main['id'],
            'invoice_number' => 'INV-' . str_pad((string) $main['id'], 5, '0', STR_PAD_LEFT),
            'order_number' => $main['order_group'] ?: ('#' . $main['id']),
            'order_date' => $main['created_at'],
            'store_name' => $storeRow['name'] ?? 'Finovo',
            'currency' => $currency,
            'customer' => [
                'name' => $main['customer_name'],
                'email' => $main['customer_email'] ?? null,
                'phone' => $main['customer_phone'] ?? null,
            ],
            'billing_address' => $main['billing_address'] ?? null,
            'shipping_address' => $main['shipping_address'] ?? null,
            'items' => $items,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'shipping' => $shipping,
            'tax' => $tax,
            'grand_total' => $grandTotal,
            'payment_method' => $main['payment_method'] ?? null,
            'payment_status' => $main['payment_status'] ?? 'unpaid',
            'source' => $main['source'] ?? 'manual',
        ];
    }
}
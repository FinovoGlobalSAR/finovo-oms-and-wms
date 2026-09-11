<?php

require_once __DIR__ . '/../../core/Model.php';

class Order extends Model
{
    public function all(): array
    {
        $stmt = $this->query('SELECT * FROM orders ORDER BY id DESC');
        return $stmt->fetchAll();
    }

    public function filterBySource(string $source): array
    {
        $stmt = $this->query(
            'SELECT * FROM orders WHERE source = ? ORDER BY id DESC',
            [$source]
        );
        return $stmt->fetchAll();
    }

    public function create(
        string $customerName,
        string $productName,
        int $quantity,
        float $price,
        string $source = 'manual',
        array $details = []
    ): int {
        $quantity = max(1, $quantity);
        $subtotal = $price;

        $this->query(
            'INSERT INTO orders (
                store_id,
                customer_id,
                product_id,
                customer_name,
                product_name,
                quantity,
                price,
                source,
                customer_email,
                customer_phone,
                billing_address,
                shipping_address,
                sku,
                variant,
                discount,
                shipping_cost,
                tax,
                payment_method,
                payment_status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $details['store_id'] ?? $this->firstStoreId(),
                $details['customer_id'] ?? null,
                $details['product_id'] ?? null,
                $customerName,
                $productName,
                $quantity,
                $subtotal,
                $source,
                $details['customer_email'] ?? null,
                $details['customer_phone'] ?? null,
                $details['billing_address'] ?? null,
                $details['shipping_address'] ?? null,
                $details['sku'] ?? null,
                $details['variant'] ?? null,
                max(0, (float) ($details['discount'] ?? 0)),
                max(0, (float) ($details['shipping_cost'] ?? 0)),
                max(0, (float) ($details['tax'] ?? 0)),
                $details['payment_method'] ?? null,
                $details['payment_status'] ?? 'Pending',
            ]
        );

        $orderId = (int) $this->db->lastInsertId();
        $invoiceNumber = 'INV-' . str_pad((string) $orderId, 5, '0', STR_PAD_LEFT);

        $this->query(
            'UPDATE orders SET invoice_number = ? WHERE id = ?',
            [$invoiceNumber, $orderId]
        );

        if ($this->tableExists('order_items')) {
            $unitPrice = $quantity > 0 ? $subtotal / $quantity : $subtotal;
            $this->query(
                'INSERT INTO order_items
                    (order_id, product_id, product_name, sku, variant, quantity, unit_price, line_total)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $orderId,
                    $details['product_id'] ?? null,
                    $productName,
                    $details['sku'] ?? null,
                    $details['variant'] ?? null,
                    $quantity,
                    $unitPrice,
                    $subtotal,
                ]
            );
        }

        return $orderId;
    }

    public function findWithItems(int $orderId, int $companyId = 0): ?array
    {
        $stmt = $this->query(
            'SELECT
                o.*,
                c.email AS linked_customer_email,
                p.sku AS linked_product_sku,
                s.name AS store_name
             FROM orders o
             LEFT JOIN customers c ON c.id = o.customer_id
             LEFT JOIN products p ON p.id = o.product_id
             LEFT JOIN stores s ON s.id = o.store_id
             WHERE o.id = ?
             LIMIT 1',
            [$orderId]
        );

        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        $items = [];
        if ($this->tableExists('order_items')) {
            $itemsStmt = $this->query(
                'SELECT
                    oi.*,
                    p.sku AS product_sku
                 FROM order_items oi
                 LEFT JOIN products p ON p.id = oi.product_id
                 WHERE oi.order_id = ?
                 ORDER BY oi.id ASC',
                [$orderId]
            );

            foreach ($itemsStmt->fetchAll() as $item) {
                $quantity = max(1, (int) ($item['quantity'] ?? 1));
                $unitPrice = (float) ($item['unit_price'] ?? 0);
                $lineTotal = (float) ($item['line_total'] ?? ($unitPrice * $quantity));

                $items[] = [
                    'product_name' => $item['product_name'] ?? 'Product',
                    'sku' => $item['sku'] ?: ($item['product_sku'] ?? '-'),
                    'variant' => $item['variant'] ?: '-',
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                ];
            }
        }

        if (empty($items)) {
            $quantity = max(1, (int) ($row['quantity'] ?? 1));
            $lineTotal = (float) ($row['price'] ?? 0);
            $items[] = [
                'product_name' => $row['product_name'] ?? 'Product',
                'sku' => $row['sku'] ?: ($row['linked_product_sku'] ?? '-'),
                'variant' => $row['variant'] ?: '-',
                'quantity' => $quantity,
                'unit_price' => $quantity > 0 ? $lineTotal / $quantity : $lineTotal,
                'line_total' => $lineTotal,
            ];
        }

        $subtotal = array_sum(array_map(
            fn(array $item) => (float) $item['line_total'],
            $items
        ));

        $discount = max(0, (float) ($row['discount'] ?? 0));
        $shipping = max(0, (float) ($row['shipping_cost'] ?? 0));
        $tax = max(0, (float) ($row['tax'] ?? 0));
        $grandTotal = max(0, $subtotal - $discount + $shipping + $tax);

        return [
            'id' => (int) $row['id'],
            'invoice_number' => $row['invoice_number'] ?: 'INV-' . str_pad((string) $row['id'], 5, '0', STR_PAD_LEFT),
            'order_number' => $row['external_order_id'] ?: 'ORD-' . str_pad((string) $row['id'], 5, '0', STR_PAD_LEFT),
            'order_date' => $row['created_at'] ?? date('Y-m-d H:i:s'),
            'store_name' => $row['store_name'] ?: 'Finovo OMS/WMS',
            'source' => $row['source'] ?? 'manual',
            'customer' => [
                'name' => $row['customer_name'] ?? 'Customer',
                'email' => $row['customer_email'] ?: ($row['linked_customer_email'] ?? '-'),
                'phone' => $row['customer_phone'] ?: '-',
            ],
            'billing_address' => $row['billing_address'] ?: '-',
            'shipping_address' => $row['shipping_address'] ?: '-',
            'items' => $items,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'shipping' => $shipping,
            'tax' => $tax,
            'grand_total' => $grandTotal,
            'payment_method' => $row['payment_method'] ?: 'Not specified',
            'payment_status' => $row['payment_status'] ?: 'Pending',
        ];
    }

    private function firstStoreId(): ?int
    {
        if (!$this->tableExists('stores')) {
            return null;
        }

        $stmt = $this->query('SELECT id FROM stores ORDER BY id ASC LIMIT 1');
        $id = $stmt->fetchColumn();
        return $id !== false ? (int) $id : null;
    }

    private function tableExists(string $table): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
        );
        $stmt->execute([$table]);
        return (int) $stmt->fetchColumn() > 0;
    }
}

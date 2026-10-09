<?php
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../Models/Product.php';
require_once __DIR__ . '/../Models/Warehouse.php';
require_once __DIR__ . '/../Models/Customer.php';
require_once __DIR__ . '/InventoryService.php';
require_once __DIR__ . '/OrderStockService.php';
require_once __DIR__ . '/IntegrationErrorService.php';
require_once __DIR__ . '/../Middlewares/ProductAvailabilityGuard.php';

/**
 * Platform se aaya order Finovo mein daalna — SAB products ke saath.
 *
 * Pehle har sync sirf pehla product (line_items[0]) uthata tha; 3 cheezon wale order mein
 * Finovo mein 1 hi dikhti thi aur stock bhi sirf 1 ka kam hota tha. Ab:
 *   - order ki har line (product) ki apni row banti hai, sab ek hi order_group mein
 *     (bilkul manual "Add Order" jaisa — invoice / shipment / picking sab group se kaam karte hain)
 *   - price = EK item ki price (Dashboard revenue price × quantity jorta hai)
 *   - stock har line ka kam hota hai (ek warehouse kam pade to doosre linked warehouses se — Multi-Warehouse Allocation)
 *
 * Do mode:
 *   'strict'  (Sync button / Auto-Sync): product Finovo mein na ho ya stock kam ho → poora order skip,
 *             aur wajah "Integration Errors" page pe likhi jati hai. (Aadha order kabhi nahi aata.)
 *   'webhook' (real-time webhooks — pehle jaisa): product na ho to ban jata hai, stock kam ho to
 *             order phir bhi aata hai lekin "stock warning" ke saath.
 */
class OrderImportService
{
    /** source → orders table ka external order ID column */
    public const ORDER_COLUMNS = [
        'shopify_pull'       => 'external_order_id',
        'woocommerce_pull'   => 'external_wc_order_id',
        'bigcommerce_pull'   => 'external_bc_order_id',
        'prestashop_pull'    => 'external_ps_order_id',
        'opencart_pull'      => 'external_ocart_order_id',
        'oscommerce_pull'    => 'external_osc_order_id',
        'wix_pull'           => 'external_wix_order_id',
        'ebay_pull'          => 'external_ebay_order_id',
        'magento_pull'       => 'external_magento_order_id',
        'custom_bridge_pull' => 'external_order_id',
    ];

    /** source → products table ka external product ID column (product pehle ID se dhoondte hain, phir naam se) */
    public const PRODUCT_COLUMNS = [
        'shopify_pull'       => 'external_product_id',
        'woocommerce_pull'   => 'external_wc_product_id',
        'bigcommerce_pull'   => 'external_bc_product_id',
        'prestashop_pull'    => 'external_ps_product_id',
        'opencart_pull'      => 'external_ocart_product_id',
        'oscommerce_pull'    => 'external_osc_product_id',
        'wix_pull'           => 'external_wix_product_id',
        'ebay_pull'          => 'external_ebay_product_id',
        'magento_pull'       => 'external_magento_product_id',
        'custom_bridge_pull' => 'external_product_id',
    ];

    private PDO $db;
    private Product $productModel;
    private Warehouse $warehouseModel;
    private InventoryService $inventoryService;
    private ProductAvailabilityGuard $availabilityGuard;
    private IntegrationErrorService $errorService;
    private Customer $customerModel;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->productModel = new Product();
        $this->warehouseModel = new Warehouse();
        $this->inventoryService = new InventoryService();
        $this->availabilityGuard = new ProductAvailabilityGuard();
        $this->errorService = new IntegrationErrorService();
        $this->customerModel = new Customer();
    }

    private static function orderColumn(string $source): string
    {
        if (!isset(self::ORDER_COLUMNS[$source])) {
            throw new InvalidArgumentException("Unknown order source: {$source}");
        }
        return self::ORDER_COLUMNS[$source];
    }

    /** Yeh platform order pehle se Finovo mein hai? */
    public function exists(int $storeId, string $source, string $externalId): bool
    {
        $column = self::orderColumn($source);
        $stmt = $this->db->prepare("SELECT 1 FROM orders WHERE {$column} = ? AND store_id = ? LIMIT 1");
        $stmt->execute([$externalId, $storeId]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Pehle se aaye order ki SAARI rows ka status update.
     * Platform pe order cancel hua → Finovo mein us ka stock warehouse mein wapas (OrderStockService).
     */
    public function updateStatus(int $storeId, string $source, string $externalId, string $status, string $paymentStatus): void
    {
        $column = self::orderColumn($source);
        (new OrderStockService())->changeStatusWhere(
            "{$column} = ? AND store_id = ?",
            [$externalId, $storeId],
            $status,
            $paymentStatus,
            str_replace('_pull', '', $source) . '_sync'
        );
    }

    /** Ek order group ka naam — store ID bhi saath, taake do stores ke same order number aapas mein na milein */
    public static function groupFor(string $source, int $storeId, string $externalId): string
    {
        $prefix = strtoupper(str_replace('_pull', '', $source));
        return mb_substr("{$prefix}-{$storeId}-{$externalId}", 0, 64);
    }

    /**
     * Platform ki lines ko saaf karo: khali naam ya 0 quantity wali lines hata do.
     * Har line: ['name' => .., 'quantity' => .., 'unit_price' => .., 'external_product_id' => ..(optional)]
     */
    public static function normalizeLines(array $lines): array
    {
        $clean = [];
        foreach ($lines as $line) {
            $name = trim((string) ($line['name'] ?? ''));
            $qty = (int) ($line['quantity'] ?? 0);
            if ($name === '' || $qty <= 0) {
                continue;
            }
            $clean[] = [
                'name' => $name,
                'quantity' => $qty,
                'unit_price' => round((float) ($line['unit_price'] ?? 0), 2),
                'external_product_id' => isset($line['external_product_id']) && $line['external_product_id'] !== ''
                    ? (string) $line['external_product_id'] : null,
                'external_variant_id' => isset($line['external_variant_id']) && $line['external_variant_id'] !== ''
                    ? (string) $line['external_variant_id'] : null,
            ];
        }
        return $clean;
    }

    /**
     * Order Finovo mein daalo.
     *
     * $header: customer_name, status, payment_status, payment_method (optional),
     *         customer (optional) = PlatformOrderMapper::customer() wali details: email, phone, address, city,
     *         country, external_customer_id — in se Customers page ka customer banta / update hota hai
     * @return array{ok: bool, reason?: string, rows?: int}
     */
    public function import(int $storeId, string $source, string $externalId, array $header, array $lines, string $mode = 'strict', string $actor = 'sync'): array
    {
        // Ek hi order do raaste se ek saath aa sakta hai (webhook + Sync button / auto-sync). Dono "abhi nahi hai"
        // dekh kar import karte the → order do dafa aur stock do dafa kat-ta. Ab MySQL lock: ek waqt mein ek hi
        // request yeh order daal sakti hai, aur lock milne ke baad dobara check hota hai ke pehle se to nahi aa gaya.
        $db = Database::getConnection();
        $lockName = 'finovo_import_' . md5(self::groupFor($source, $storeId, $externalId));
        $lock = $db->prepare("SELECT GET_LOCK(?, 15)");
        $lock->execute([$lockName]);
        if ((int) $lock->fetchColumn() !== 1) {
            return ['ok' => false, 'reason' => 'Another import of this order is running. Try again.'];
        }

        try {
            if ($this->exists($storeId, $source, $externalId)) {
                return ['ok' => false, 'reason' => 'Order already imported.'];
            }
            return $this->importUnlocked($storeId, $source, $externalId, $header, $lines, $mode, $actor);
        } finally {
            $db->prepare("SELECT RELEASE_LOCK(?)")->execute([$lockName]);
        }
    }

    private function importUnlocked(int $storeId, string $source, string $externalId, array $header, array $lines, string $mode, string $actor): array
    {
        $orderColumn = self::orderColumn($source);
        $lines = self::normalizeLines($lines);

        if (empty($lines)) {
            return $this->skip($storeId, $source, $externalId, 'Order has no products.', $mode);
        }

        // 1) Har line ka Finovo product dhoondo — ek bhi na mile to poora order skip (strict mode)
        foreach ($lines as $i => $line) {
            $productId = $this->resolveProduct($storeId, $source, $line, $mode);
            if ($productId === null) {
                return $this->skip($storeId, $source, $externalId, "Product \"{$line['name']}\" is not in Finovo. Sync products first.", $mode);
            }
            $lines[$i]['product_id'] = $productId;

            // Order mein Finovo wala naam; platform ka naam alag ho (jaise "Mug - Color : White") to woh variant ki jagah dikhe
            $finovoName = (string) ($this->productModel->find($productId)['name'] ?? $line['name']);
            $lines[$i]['finovo_name'] = $finovoName;
            $lines[$i]['variant_label'] = strcasecmp($finovoName, $line['name']) === 0 ? null : mb_substr($line['name'], 0, 150);

            // Variant wala product (size / color): stock us variant ka kat-ta hai. Pehle hamesha product ka
            // kat-ta tha — variant products ka product-level stock 0 hota hai, is liye order "stock nahi" keh
            // ke chhoot jata tha (ya webhook mein bina stock kaate aata tha).
            $lines[$i]['variant_id'] = null;
            if ($this->productModel->hasVariants($productId)) {
                $variant = $this->resolveVariant($productId, $source, $line);
                if ($variant === null && $mode === 'strict') {
                    return $this->skip($storeId, $source, $externalId, "Variant \"{$line['name']}\" is not in Finovo. Sync products first.", $mode);
                }
                if ($variant !== null) {
                    $lines[$i]['variant_id'] = (int) $variant['id'];
                    $lines[$i]['variant_label'] = mb_substr((string) $variant['label'], 0, 150);
                }
            }
        }

        // 2) Stock reserve karo — ek bhi line fail ho to pehle wali lines ka stock wapas
        $this->warehouseModel->getDefault($storeId); // store ka kam se kam ek warehouse ho
        $reference = "external:{$externalId}";
        $reserved = [];

        // Order platform pe pehle hi cancel ho chuka hai → stock kaatna hi nahi (record ke liye aata hai)
        $alreadyCancelled = ($header['status'] ?? '') === OrderStockService::CANCELLED;

        foreach ($lines as $i => $line) {
            if ($alreadyCancelled) {
                $default = $this->warehouseModel->getDefault($storeId);
                $lines[$i]['allocations'] = [['warehouse_id' => (int) $default['id'], 'quantity' => $line['quantity']]];
                $lines[$i]['stock_warning'] = 0;
                continue;
            }

            $result = $this->inventoryService->reserveAcrossWarehouses(
                (int) $line['product_id'], $line['variant_id'], $storeId, $line['quantity'], $source, $reference, $actor
            );

            if ($result['ok']) {
                $lines[$i]['allocations'] = $result['allocations'];
                $lines[$i]['stock_warning'] = 0;
                $reserved[] = $lines[$i];
                continue;
            }

            if ($mode === 'webhook') {
                // Webhook: order phir bhi aaye (stock warning ke saath), stock kam nahi hota
                $default = $this->warehouseModel->getDefault($storeId);
                $lines[$i]['allocations'] = [['warehouse_id' => (int) $default['id'], 'quantity' => $line['quantity']]];
                $lines[$i]['stock_warning'] = 1;
                continue;
            }

            $this->rollback($reserved, $reference, $actor);
            $available = (int) ($result['available'] ?? 0);
            return $this->skip($storeId, $source, $externalId, "Not enough stock for \"{$line['name']}\" — needs {$line['quantity']}, only {$available} available.", $mode);
        }

        // 3) Customer (kharidne wala) — platform ki details se dhoondo ya banao (Customers page)
        $details = is_array($header['customer'] ?? null) ? $header['customer'] : [];
        $customerName = trim((string) ($header['customer_name'] ?? '')) ?: trim((string) ($details['name'] ?? '')) ?: 'Unknown';
        $customerId = $this->customerModel->upsertFromOrder($storeId, $customerName, $details, str_replace('_pull', '', $source));
        $email = Customer::cleanEmail($details['email'] ?? null);
        $phone = mb_substr(trim((string) ($details['phone'] ?? '')), 0, 50) ?: null;
        $address = trim((string) ($details['address'] ?? ''));
        if ($address !== '' && !empty($details['country']) && stripos($address, (string) $details['country']) === false) {
            $address .= ', ' . $details['country'];
        }

        // 4) Rows banao — har line (aur har warehouse allocation) ki ek row, sab ek order_group mein
        $group = self::groupFor($source, $storeId, $externalId);
        $insert = $this->db->prepare(
            "INSERT INTO orders (store_id, customer_name, product_name, variant_id, variant_label, quantity, price, source, {$orderColumn},
                                 status, payment_status, product_id, warehouse_id, payment_method, order_group, stock_warning,
                                 customer_id, customer_email, customer_phone, shipping_address)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $rows = 0;
        foreach ($lines as $line) {
            foreach ($line['allocations'] as $alloc) {
                $insert->execute([
                    $storeId,
                    mb_substr($customerName, 0, 150),
                    $line['finovo_name'],
                    $line['variant_id'],
                    $line['variant_label'],
                    (int) $alloc['quantity'],
                    $line['unit_price'],
                    $source,
                    $externalId,
                    $header['status'] ?? 'pending',
                    $header['payment_status'] ?? 'unpaid',
                    (int) $line['product_id'],
                    (int) $alloc['warehouse_id'],
                    $header['payment_method'] ?? null,
                    $group,
                    $line['stock_warning'],
                    $customerId,
                    $email,
                    $phone,
                    $address !== '' ? $address : null,
                ]);
                $rows++;
            }
        }

        $connector = str_replace('_pull', '', $source);
        $this->errorService->markResolved($storeId, $connector, 'import_order', $externalId);

        return ['ok' => true, 'rows' => $rows];
    }

    /** Product: pehle platform ke product ID se, phir naam se. Webhook mode mein na mile to bana do. */
    private function resolveProduct(int $storeId, string $source, array $line, string $mode): ?int
    {
        $column = self::PRODUCT_COLUMNS[$source] ?? null;
        if ($column !== null && $line['external_product_id'] !== null) {
            $stmt = $this->db->prepare("SELECT id FROM products WHERE store_id = ? AND {$column} = ? LIMIT 1");
            $stmt->execute([$storeId, $line['external_product_id']]);
            $id = $stmt->fetchColumn();
            if ($id !== false) {
                return (int) $id;
            }
        }

        $guard = $this->availabilityGuard->check($storeId, $line['name']);
        if ($guard['allowed']) {
            return (int) $guard['product_id'];
        }

        if ($mode === 'webhook') {
            // Custom Bridge ka product ID "external_product_id" column mein hi rehta hai; baaki platforms ka naam se
            $externalForCreate = $source === 'custom_bridge_pull' ? $line['external_product_id'] : null;
            return $this->productModel->findOrCreate($storeId, $line['name'], $line['unit_price'], $externalForCreate);
        }

        return null;
    }

    /**
     * Platform line ka Finovo variant: pehle platform ke variant ID se (Shopify variant_id /
     * WooCommerce variation_id), phir naam se (line ke naam mein variant ka label, jaise "Tee - M / Red").
     */
    private function resolveVariant(int $productId, string $source, array $line): ?array
    {
        $column = match ($source) {
            'shopify_pull' => 'external_variant_id',
            'woocommerce_pull' => 'external_wc_variant_id',
            default => null,
        };
        if ($column !== null && !empty($line['external_variant_id'])) {
            $stmt = $this->db->prepare("SELECT * FROM product_variants WHERE product_id = ? AND {$column} = ? LIMIT 1");
            $stmt->execute([$productId, $line['external_variant_id']]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                return $row;
            }
        }

        // Naam se: sab se lamba label jo line ke naam ke aakhir mein ho ("Tee - M / Red" → "M / Red")
        $stmt = $this->db->prepare("SELECT * FROM product_variants WHERE product_id = ? ORDER BY CHAR_LENGTH(label) DESC");
        $stmt->execute([$productId]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $variant) {
            $label = trim((string) $variant['label']);
            if ($label !== '' && (strcasecmp($label, $line['name']) === 0 || str_ends_with(mb_strtolower($line['name']), mb_strtolower($label)))) {
                return $variant;
            }
        }
        return null;
    }

    private function rollback(array $reservedLines, string $reference, string $actor): void
    {
        foreach ($reservedLines as $line) {
            foreach ($line['allocations'] as $alloc) {
                $this->inventoryService->release(
                    (int) $line['product_id'], $line['variant_id'] ?? null, (int) $alloc['warehouse_id'], (int) $alloc['quantity'],
                    'order_import_rollback', $reference, $actor
                );
            }
        }
    }

    /** Order chhoda — wajah Integration Errors page pe (sirf strict mode; webhook kabhi skip nahi karta) */
    private function skip(int $storeId, string $source, string $externalId, string $reason, string $mode): array
    {
        if ($mode === 'strict') {
            $connector = str_replace('_pull', '', $source);
            $this->errorService->logFailure($storeId, $connector, 'import_order', $externalId, $externalId, null, "Order {$externalId} skipped: {$reason}");
        }
        return ['ok' => false, 'reason' => $reason];
    }
}
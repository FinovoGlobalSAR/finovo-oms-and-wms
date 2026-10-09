<?php
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/InventoryService.php';

/**
 * Order ka status badle to stock ka hisaab — sab jagah ek hi qaida.
 *
 * Qaida: order ki row ka stock tabhi "pakda hua" (reserved) hota hai jab
 *   - status "cancelled" NA ho, aur
 *   - stock_warning = 0 ho (stock_warning = 1 matlab order aate waqt stock tha hi nahi, kuch kata hi nahi)
 *
 * Is liye:
 *   koi bhi status  → cancelled : stock warehouse mein WAPAS
 *   cancelled       → koi aur   : stock DOBARA kat-ta hai (na ho to manual edit pe error, sync pe "stock warning")
 *
 * Pehle order cancel hone pe stock wapas nahi aata tha — maal reserved hi pada rehta tha.
 * Yeh service har jagah se use hoti hai: order Edit, platform sync (Shopify, WooCommerce...), shipment "returned".
 */
class OrderStockService
{
    public const CANCELLED = 'cancelled';

    private PDO $db;
    private InventoryService $inventory;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->inventory = new InventoryService();
    }

    /** Is row ka stock abhi pakda hua hai? */
    public static function holdsStock(array $row): bool
    {
        return ($row['status'] ?? '') !== self::CANCELLED
            && empty($row['stock_warning'])
            && !empty($row['product_id']);
    }

    /**
     * Is order row ka kitna maal "return complete" ho kar wapas aa chuka hai (returns table).
     * Return complete hote hi woh maal order se nikal jata hai — warna baad mein order cancel / delete
     * hone pe poori quantity DOBARA warehouse mein jud jati (ek hi maal do dafa).
     */
    public function returnedQuantity(int $orderId): int
    {
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(quantity), 0) FROM returns WHERE order_id = ? AND status = 'completed'");
        $stmt->execute([$orderId]);
        return (int) $stmt->fetchColumn();
    }

    /** Order ka jitna stock abhi bhi pakda hua hai = quantity − complete hue returns (kabhi minus nahi) */
    public function heldQuantity(array $row, ?int $quantity = null): int
    {
        $quantity ??= (int) ($row['quantity'] ?? 0);
        return max(0, $quantity - $this->returnedQuantity((int) ($row['id'] ?? 0)));
    }

    /** Row ka warehouse (purani rows mein khali ho sakta hai → store ka default warehouse) */
    private function warehouseFor(array $row): int
    {
        $warehouseId = (int) ($row['warehouse_id'] ?? 0);
        if ($warehouseId > 0) {
            return $warehouseId;
        }
        require_once __DIR__ . '/../Models/Warehouse.php';
        return (int) ((new Warehouse())->getDefault((int) $row['store_id'])['id'] ?? 0);
    }

    /**
     * Ek row ka status badalne se PEHLE stock theek karo.
     *
     * $strict = true  (manual Edit): stock na ho to kuch nahi badalta, ['ok' => false, 'message' => ...]
     * $strict = false (sync / shipment): stock na ho to row "stock warning" ban jati hai, status phir bhi badalta hai
     *
     * @return array{ok: bool, message?: string, stock_warning?: int}
     */
    public function beforeStatusChange(array $row, string $newStatus, string $actor, bool $strict): array
    {
        $oldStatus = (string) ($row['status'] ?? 'pending');
        $warning = (int) ($row['stock_warning'] ?? 0);
        $reference = 'order:' . (int) $row['id'];
        $variantId = !empty($row['variant_id']) ? (int) $row['variant_id'] : null;

        if (empty($row['product_id']) || $oldStatus === $newStatus) {
            return ['ok' => true, 'stock_warning' => $warning];
        }

        // Cancel → stock wapas
        if ($newStatus === self::CANCELLED) {
            $held = $this->heldQuantity($row);
            if (self::holdsStock($row) && $held > 0) {
                $this->inventory->release((int) $row['product_id'], $variantId, $this->warehouseFor($row), $held, 'order_cancel', $reference, $actor);
            }
            // Cancel order mein "stock nahi tha" ki warning ka koi matlab nahi
            return ['ok' => true, 'stock_warning' => 0];
        }

        // Cancel se wapas (jaise galti se cancel kiya tha) → stock dobara kato
        if ($oldStatus === self::CANCELLED) {
            $held = $this->heldQuantity($row);
            if ($held <= 0) {
                return ['ok' => true, 'stock_warning' => 0];
            }
            $result = $this->inventory->reserve((int) $row['product_id'], $variantId, $this->warehouseFor($row), $held, 'order_uncancel', $reference, $actor);
            if ($result['ok']) {
                return ['ok' => true, 'stock_warning' => 0];
            }
            if ($strict) {
                return ['ok' => false, 'message' => "Not enough stock to re-open this order — needs {$held}, only " . (int) ($result['available'] ?? 0) . ' available.'];
            }
            return ['ok' => true, 'stock_warning' => 1];
        }

        return ['ok' => true, 'stock_warning' => $warning];
    }

    /**
     * Kai rows ka status ek saath (platform sync, shipment) — har row ka stock theek karke status likho.
     * $where / $params = kaun si rows (jaise "external_order_id = ? AND store_id = ?")
     */
    public function changeStatusWhere(string $where, array $params, string $newStatus, ?string $paymentStatus, string $actor): void
    {
        $stmt = $this->db->prepare("SELECT * FROM orders WHERE {$where}");
        $stmt->execute($params);

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $result = $this->beforeStatusChange($row, $newStatus, $actor, false);

            if ($paymentStatus !== null) {
                $this->db->prepare("UPDATE orders SET status = ?, payment_status = ?, stock_warning = ? WHERE id = ?")
                    ->execute([$newStatus, $paymentStatus, (int) $result['stock_warning'], (int) $row['id']]);
            } else {
                $this->db->prepare("UPDATE orders SET status = ?, stock_warning = ? WHERE id = ?")
                    ->execute([$newStatus, (int) $result['stock_warning'], (int) $row['id']]);
            }
        }
    }
}
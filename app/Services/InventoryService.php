<?php
require_once __DIR__ . '/../../core/Database.php';

/**
 * Har stock-dependent decision ISI service se guzarti hai — browser/controller
 * seedha stock decide nahi kar sakta. Har change ledger mein log hota hai.
 */
class InventoryService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Available stock check karta hai — bina kisi change ke, sirf padhta hai.
     */
    public function checkAvailability(int $productId, ?int $variantId, int $warehouseId): int
    {
        if ($variantId) {
            $stmt = $this->db->prepare("SELECT stock_quantity FROM variant_warehouse_stock WHERE variant_id = ? AND warehouse_id = ?");
            $stmt->execute([$variantId, $warehouseId]);
        } else {
            $stmt = $this->db->prepare("SELECT stock_quantity FROM product_warehouse_stock WHERE product_id = ? AND warehouse_id = ?");
            $stmt->execute([$productId, $warehouseId]);
        }
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) ($row['stock_quantity'] ?? 0);
    }

    /**
     * Stock reserve/deduct karneе se pahle validate karta hai. Agar kam hai,
     * structured error return karta hai (koi silent/galat deduction nahi hota).
     *
     * @return array{ok: bool, available?: int, requested?: int, sku?: string}
     */
    public function reserve(int $productId, ?int $variantId, int $warehouseId, int $quantity, string $source, ?string $reference = null, ?string $actor = null): array
    {
        $this->db->beginTransaction();

        try {
            // Row lock — do simultaneous orders same stock ke liye compete na karein
            if ($variantId) {
                $stmt = $this->db->prepare("SELECT vws.stock_quantity, pv.sku FROM variant_warehouse_stock vws INNER JOIN product_variants pv ON pv.id = vws.variant_id WHERE vws.variant_id = ? AND vws.warehouse_id = ? FOR UPDATE");
                $stmt->execute([$variantId, $warehouseId]);
            } else {
                $stmt = $this->db->prepare("SELECT pws.stock_quantity, p.sku FROM product_warehouse_stock pws INNER JOIN products p ON p.id = pws.product_id WHERE pws.product_id = ? AND pws.warehouse_id = ? FOR UPDATE");
                $stmt->execute([$productId, $warehouseId]);
            }
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $available = (int) ($row['stock_quantity'] ?? 0);
            $sku = $row['sku'] ?? null;

            if ($available < $quantity) {
                $this->db->rollBack();
                return [
                    'ok' => false,
                    'code' => 'INVENTORY_UNAVAILABLE',
                    'sku' => $sku,
                    'requested' => $quantity,
                    'available' => $available,
                ];
            }

            $newQuantity = $available - $quantity;

            if ($variantId) {
                $this->db->prepare("UPDATE variant_warehouse_stock SET stock_quantity = ? WHERE variant_id = ? AND warehouse_id = ?")
                    ->execute([$newQuantity, $variantId, $warehouseId]);
            } else {
                $this->db->prepare("UPDATE product_warehouse_stock SET stock_quantity = ? WHERE product_id = ? AND warehouse_id = ?")
                    ->execute([$newQuantity, $productId, $warehouseId]);
            }

            $this->logLedger($productId, $variantId, $warehouseId, $available, -$quantity, $newQuantity, $source, $reference, $actor);

            $this->db->prepare("UPDATE products SET stock_quantity = (SELECT COALESCE(SUM(stock_quantity),0) FROM product_warehouse_stock WHERE product_id = ?) WHERE id = ?")
                ->execute([$productId, $productId]);

            $this->db->commit();
            return ['ok' => true, 'available' => $newQuantity];
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Stock wapas add karta hai (order cancel/return/restock ke waqt).
     */
    public function release(int $productId, ?int $variantId, int $warehouseId, int $quantity, string $source, ?string $reference = null, ?string $actor = null): void
    {
        $this->db->beginTransaction();

        try {
            if ($variantId) {
                $stmt = $this->db->prepare("SELECT stock_quantity FROM variant_warehouse_stock WHERE variant_id = ? AND warehouse_id = ? FOR UPDATE");
                $stmt->execute([$variantId, $warehouseId]);
            } else {
                $stmt = $this->db->prepare("SELECT stock_quantity FROM product_warehouse_stock WHERE product_id = ? AND warehouse_id = ? FOR UPDATE");
                $stmt->execute([$productId, $warehouseId]);
            }
            $before = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['stock_quantity'] ?? 0);
            $after = $before + $quantity;

            if ($variantId) {
                $this->db->prepare("UPDATE variant_warehouse_stock SET stock_quantity = ? WHERE variant_id = ? AND warehouse_id = ?")
                    ->execute([$after, $variantId, $warehouseId]);
            } else {
                $this->db->prepare("UPDATE product_warehouse_stock SET stock_quantity = ? WHERE product_id = ? AND warehouse_id = ?")
                    ->execute([$after, $productId, $warehouseId]);
            }

            $this->logLedger($productId, $variantId, $warehouseId, $before, $quantity, $after, $source, $reference, $actor);

            $this->db->prepare("UPDATE products SET stock_quantity = (SELECT COALESCE(SUM(stock_quantity),0) FROM product_warehouse_stock WHERE product_id = ?) WHERE id = ?")
                ->execute([$productId, $productId]);

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function logLedger(int $productId, ?int $variantId, int $warehouseId, int $before, int $change, int $after, string $source, ?string $reference, ?string $actor): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO inventory_ledger (product_id, variant_id, warehouse_id, quantity_before, quantity_change, quantity_after, source, reference, actor)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$productId, $variantId, $warehouseId, $before, $change, $after, $source, $reference, $actor]);
    }

    public function ledgerFor(int $productId, int $limit = 50): array
    {
        $stmt = $this->db->prepare("SELECT * FROM inventory_ledger WHERE product_id = ? ORDER BY id DESC LIMIT ?");
        $stmt->bindValue(1, $productId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
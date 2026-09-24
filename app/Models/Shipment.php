<?php
require_once __DIR__ . '/../../core/Model.php';

class Shipment extends Model
{
    public static array $couriers = ['TCS', 'Leopards Courier', 'M&P', 'DHL', 'Trax', 'Other'];

    public static array $statuses = ['pending', 'packed', 'dispatched', 'in_transit', 'delivered', 'returned'];

    public function all(int $storeId): array
    {
        $stmt = $this->query(
            "SELECT s.*, COUNT(o.id) as item_count,
                    MIN(o.customer_name) as customer_name
             FROM shipments s
             LEFT JOIN orders o ON o.shipment_id = s.id
             WHERE s.store_id = ?
             GROUP BY s.id
             ORDER BY s.id DESC",
            [$storeId]
        );
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->query("SELECT * FROM shipments WHERE id = ?", [$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    // AWB number se shipment dhundta hai — 3PL handover scan ke liye
    public function findByTrackingNumber(string $trackingNumber, int $storeId): ?array
    {
        $stmt = $this->query(
            "SELECT * FROM shipments WHERE tracking_number = ? AND store_id = ?",
            [$trackingNumber, $storeId]
        );
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(int $storeId, string $courierName, ?string $trackingNumber, ?string $notes): int
    {
        if (!$trackingNumber) {
            $trackingNumber = $this->generateTrackingNumber();
        }

        $this->query(
            "INSERT INTO shipments (store_id, courier_name, tracking_number, notes) VALUES (?, ?, ?, ?)",
            [$storeId, $courierName, $trackingNumber, $notes ?: null]
        );
        return (int) $this->db->lastInsertId();
    }

    public function generateTrackingNumber(): string
    {
        return 'FIN-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    }

    // Sirf wo orders yahan aayenge jo Picking page se "Packed" mark ho chuke hain.
    public function unassignedOrderUnits(int $storeId): array
    {
        $stmt = $this->query(
            "SELECT COALESCE(order_group, CONCAT('single-', id)) as unit_key,
                    MIN(id) as first_id, MIN(customer_name) as customer_name,
                    COUNT(*) as item_count,
                    GROUP_CONCAT(product_name SEPARATOR ', ') as products
             FROM orders
             WHERE store_id = ? AND shipment_id IS NULL AND picking_status = 'packed'
             GROUP BY unit_key
             ORDER BY first_id DESC",
            [$storeId]
        );
        return $stmt->fetchAll();
    }

    public function assignOrderUnit(int $shipmentId, string $unitKey): void
    {
        if (str_starts_with($unitKey, 'single-')) {
            $orderId = (int) substr($unitKey, 7);
            $this->query("UPDATE orders SET shipment_id = ? WHERE id = ?", [$shipmentId, $orderId]);
        } else {
            $this->query("UPDATE orders SET shipment_id = ? WHERE order_group = ?", [$shipmentId, $unitKey]);
        }
    }

    public function ordersForShipment(int $shipmentId): array
    {
        $stmt = $this->query("SELECT * FROM orders WHERE shipment_id = ? ORDER BY id ASC", [$shipmentId]);
        return $stmt->fetchAll();
    }

    public function updateStatus(int $id, string $status): void
    {
        $status = in_array($status, self::$statuses, true) ? $status : 'pending';

        $dispatchedAt = null;
        $deliveredAt = null;

        if (in_array($status, ['dispatched', 'in_transit', 'delivered'], true)) {
            $existing = $this->find($id);
            $dispatchedAt = $existing['dispatched_at'] ?? date('Y-m-d H:i:s');
        }
        if ($status === 'delivered') {
            $deliveredAt = date('Y-m-d H:i:s');
        }

        $this->query(
            "UPDATE shipments SET status = ?, dispatched_at = COALESCE(?, dispatched_at), delivered_at = COALESCE(?, delivered_at) WHERE id = ?",
            [$status, $dispatchedAt, $deliveredAt, $id]
        );

        // Order status ko shipment status ke sath sync karo
        $orderStatus = match ($status) {
            'dispatched', 'in_transit' => 'processing',
            'delivered' => 'delivered',
            'returned' => 'cancelled',
            default => 'pending',
        };
        $this->query("UPDATE orders SET status = ? WHERE shipment_id = ?", [$orderStatus, $id]);
    }

    public function shipmentsByOrderIds(array $orderIds): array
    {
        if (empty($orderIds)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
        $stmt = $this->query(
            "SELECT o.id as order_id, s.id as shipment_id, s.courier_name, s.tracking_number, s.status
             FROM orders o
             INNER JOIN shipments s ON s.id = o.shipment_id
             WHERE o.id IN ({$placeholders})",
            $orderIds
        );
        $rows = $stmt->fetchAll();
        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['order_id']] = $row;
        }
        return $map;
    }

    // ---------- 3PL Handover Scan ----------

    // Jab warehouse staff shipment ko 3PL rider ke haath mein physically deta
    // hai, AWB scan karke ye confirm hota hai. Status automatically "dispatched"
    // bhi ho jata hai — courier ko de dena hi dispatch ka moment hai.
    public function markHandedOver(int $id, string $scannedBy): void
    {
        $this->query(
            "UPDATE shipments SET handover_status = 'handed_over', handover_scanned_at = NOW(), handover_scanned_by = ? WHERE id = ?",
            [$scannedBy, $id]
        );
        $this->updateStatus($id, 'dispatched');
    }

    // ---------- 3PL Remittance ----------

    public function allForRemittance(int $storeId): array
    {
        $stmt = $this->query(
            "SELECT s.*, COUNT(o.id) as item_count, MIN(o.customer_name) as customer_name,
                    COALESCE(SUM(o.price * o.quantity), 0) as order_total
             FROM shipments s
             LEFT JOIN orders o ON o.shipment_id = s.id
             WHERE s.store_id = ? AND s.handover_status = 'handed_over'
             GROUP BY s.id
             ORDER BY s.id DESC",
            [$storeId]
        );
        return $stmt->fetchAll();
    }

    public function updateRemittance(int $id, string $status, ?float $amount): void
    {
        $status = in_array($status, ['not_remitted', 'remitted'], true) ? $status : 'not_remitted';
        $remittedAt = $status === 'remitted' ? date('Y-m-d H:i:s') : null;

        $this->query(
            "UPDATE shipments SET remittance_status = ?, remittance_amount = ?, remitted_at = COALESCE(?, remitted_at) WHERE id = ?",
            [$status, $amount, $remittedAt, $id]
        );
    }
}
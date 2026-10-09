<?php
require_once __DIR__ . '/OrderImportService.php';
require_once __DIR__ . '/PlatformOrderMapper.php';

/**
 * eBay orders Finovo mein (Sync button, Auto-Sync aur eBay real-time notification teeno yahi use karte hain).
 * Order ke SAARE products aate hain — pehle sirf pehla product (lineItems[0]) aata tha.
 */
class EbayOrderImporter
{
    private OrderImportService $importer;

    public function __construct()
    {
        $this->importer = new OrderImportService();
    }

    /**
     * @return int kitne naye orders aaye
     */
    public function importOrders(array $store, array $orders): int
    {
        $storeId = (int) $store['id'];
        $importedCount = 0;

        foreach ($orders as $order) {
            $externalId = (string) ($order['orderId'] ?? '');
            if ($externalId === '') continue;

            $orderStatus = match ($order['orderFulfillmentStatus'] ?? 'NOT_STARTED') {
                'FULFILLED' => 'delivered',
                'IN_PROGRESS' => 'processing',
                default => 'pending',
            };
            $paymentStatus = ($order['orderPaymentStatus'] ?? '') === 'PAID' ? 'paid' : 'unpaid';

            // Pehle se aaya hua order → sirf status update
            if ($this->importer->exists($storeId, 'ebay_pull', $externalId)) {
                $this->importer->updateStatus($storeId, 'ebay_pull', $externalId, $orderStatus, $paymentStatus);
                continue;
            }

            // Customer: shipping wala asli naam (na ho to eBay username)
            $customer = PlatformOrderMapper::customer('ebay', $order);

            $result = $this->importer->import($storeId, 'ebay_pull', $externalId, [
                'customer_name' => $customer['name'] !== '' ? $customer['name'] : ($order['buyer']['username'] ?? ('Order #' . $externalId)),
                'status' => $orderStatus,
                'payment_status' => $paymentStatus,
                'payment_method' => 'eBay',
                'customer' => $customer,
            ], PlatformOrderMapper::ebayLines($order), 'strict', 'ebay_sync');

            if ($result['ok']) {
                $importedCount++;
            }
        }

        return $importedCount;
    }
}
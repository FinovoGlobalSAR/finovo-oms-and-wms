<?php
/**
 * DHL "Shipment Tracking - Unified" API se live status fetch karta hai.
 * Free tier hai, koi business account nahi chahiye — sirf tracking (Shipment
 * creation ke liye alag, business-account-wala API chahiye hoga).
 */
class DhlConnector
{
    private string $apiKey;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * @return array{ok: bool, status?: string, description?: string, message?: string}
     */
    public function trackShipment(string $trackingNumber): array
    {
        $url = 'https://api-eu.dhl.com/track/shipments?trackingNumber=' . urlencode($trackingNumber);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'DHL-API-Key: ' . $this->apiKey,
            'Accept: application/json',
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return ['ok' => false, 'message' => "DHL API error (HTTP {$httpCode}): " . $response];
        }

        $data = json_decode($response, true);
        $shipment = $data['shipments'][0] ?? null;

        if (!$shipment) {
            return ['ok' => false, 'message' => 'No tracking data found for this AWB.'];
        }

        $status = $shipment['status']['statusCode'] ?? 'unknown';
        $description = $shipment['status']['description'] ?? '';

        return ['ok' => true, 'status' => $status, 'description' => $description];
    }

    /**
     * DHL ke status codes ko Finovo ke apne shipment statuses mein map karta hai.
     */
    public static function mapToFinovoStatus(string $dhlStatus): string
    {
        return match (strtolower($dhlStatus)) {
            'delivered' => 'delivered',
            'transit' => 'in_transit',
            'pre-transit' => 'dispatched',
            'failure', 'returned' => 'returned',
            default => 'dispatched',
        };
    }
}
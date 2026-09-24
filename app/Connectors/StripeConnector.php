<?php

class StripeConnector
{
    private string $secretKey;

    public function __construct(string $secretKey)
    {
        $this->secretKey = $secretKey;
    }

    /**
     * @return array{ok: bool, checkout_url?: string, session_id?: string, message?: string}
     */
    public function createCheckoutSession(string $productName, float $amount, string $currency, int $orderId, string $successUrl, string $cancelUrl): array
    {
        // Stripe amount ko "cents" (smallest currency unit) mein chahta hai
        $amountInCents = (int) round($amount * 100);

        $payload = http_build_query([
            'mode' => 'payment',
            'line_items[0][price_data][currency]' => strtolower($currency),
            'line_items[0][price_data][product_data][name]' => $productName,
            'line_items[0][price_data][unit_amount]' => $amountInCents,
            'line_items[0][quantity]' => 1,
            'success_url' => $successUrl . '?session_id={CHECKOUT_SESSION_ID}&order_id=' . $orderId,
            'cancel_url' => $cancelUrl,
            'metadata[order_id]' => $orderId,
        ]);

        $ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->secretKey,
            'Content-Type: application/x-www-form-urlencoded',
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return ['ok' => false, 'message' => "Stripe error (HTTP {$httpCode}): " . $response];
        }

        $data = json_decode($response, true);

        return [
            'ok' => true,
            'checkout_url' => $data['url'],
            'session_id' => $data['id'],
        ];
    }

    /**
     * Payment complete hone ke baad, session ka payment_status check karta
     * hai ("paid" ya nahi) — Stripe redirect trust nahi karte, seedha
     * Stripe se confirm karte hain ke genuinely paisa aaya.
     */
    public function verifySession(string $sessionId): array
    {
        $ch = curl_init('https://api.stripe.com/v1/checkout/sessions/' . urlencode($sessionId));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $this->secretKey]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return ['ok' => false, 'message' => "Stripe error (HTTP {$httpCode})"];
        }

        $data = json_decode($response, true);

        return ['ok' => true, 'paid' => ($data['payment_status'] ?? '') === 'paid'];
    }
}
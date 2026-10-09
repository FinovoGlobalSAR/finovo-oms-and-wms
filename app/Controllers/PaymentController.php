<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../Connectors/StripeConnector.php';

class PaymentController extends Controller
{
    private StripeConnector $stripe;

    public function __construct()
    {
        parent::__construct();
        $this->requireRole(['admin', 'manager', 'sales staff']);
        $apiKey = $_ENV['STRIPE_SECRET_KEY'] ?? '';
        $this->stripe = new StripeConnector($apiKey);
    }

    /**
     * Order ki saari rows (ek order group = ek customer order, kai products) — sirf current store ki.
     * Cancelled rows ka paisa nahi lagta.
     */
    private function orderRows(int $orderId): array
    {
        $db = Database::getConnection();
        $store = $this->getCurrentStore();

        $stmt = $db->prepare("SELECT * FROM orders WHERE id = ? AND store_id = ?");
        $stmt->execute([$orderId, (int) $store['id']]);
        $order = $stmt->fetch();
        if (!$order) {
            return [];
        }
        if (empty($order['order_group'])) {
            return [$order];
        }

        $stmt = $db->prepare("SELECT * FROM orders WHERE order_group = ? AND store_id = ? AND status <> 'cancelled' ORDER BY id");
        $stmt->execute([$order['order_group'], (int) $store['id']]);
        return $stmt->fetchAll() ?: [$order];
    }

    // Order ke "Pay Now" button se yahan aata hai — Stripe checkout session
    // banake, customer ko Stripe ke hosted payment page pe bhej deta hai.
    public function initiate(): void
    {
        $orderId = (int) ($_GET['order_id'] ?? 0);
        $db = Database::getConnection();

        $rows = $this->orderRows($orderId);
        if (empty($rows)) {
            $this->redirect('/orders?error=' . urlencode('Order not found.'));
            return;
        }
        if (($rows[0]['status'] ?? '') === 'cancelled') {
            $this->redirect('/orders?error=' . urlencode('This order is cancelled.'));
            return;
        }
        if (($rows[0]['payment_status'] ?? '') === 'paid') {
            $this->redirect('/orders?error=' . urlencode('This order is already paid.'));
            return;
        }

        // Poore order ka total (pehle sirf pehle product ka paisa lagta tha)
        $total = 0.0;
        foreach ($rows as $row) {
            $total += (float) $row['price'] * (int) $row['quantity'];
        }
        $label = count($rows) > 1
            ? 'Order #' . $orderId . ' (' . count($rows) . ' items)'
            : $rows[0]['product_name'];

        $baseUrl = $this->getBaseUrl();

        $result = $this->stripe->createCheckoutSession(
            $label,
            $total,
            'usd',
            $orderId,
            $baseUrl . '/payment/success',
            $baseUrl . '/orders?error=' . urlencode('Payment cancelled.')
        );

        if (!$result['ok']) {
            $this->redirect('/orders?error=' . urlencode($result['message']));
            return;
        }

        // Session ID order ki har row pe — success pe yahi milaya jata hai
        $update = $db->prepare("UPDATE orders SET stripe_session_id = ? WHERE id = ?");
        foreach ($rows as $row) {
            $update->execute([$result['session_id'], (int) $row['id']]);
        }

        // Customer ko Stripe ke apne secure payment page pe bhej do
        header('Location: ' . $result['checkout_url']);
        exit;
    }

    /**
     * Stripe payment ke baad wapis yahan redirect karta hai.
     * Hifazat: session wahi hona chahiye jo is order ke liye banaya tha, aur Stripe ke metadata
     * mein bhi yahi order ho. Pehle koi bhi paid session kisi bhi order_id ke saath bhej kar
     * har order "paid" kar sakta tha.
     */
    public function success(): void
    {
        $sessionId = (string) ($_GET['session_id'] ?? '');
        $orderId = (int) ($_GET['order_id'] ?? 0);

        if ($sessionId === '' || $orderId <= 0) {
            $this->redirect('/orders?error=' . urlencode('Invalid payment confirmation.'));
            return;
        }

        $rows = $this->orderRows($orderId);
        $savedSession = (string) ($rows[0]['stripe_session_id'] ?? '');
        if (empty($rows) || $savedSession === '' || !hash_equals($savedSession, $sessionId)) {
            $this->redirect('/orders?error=' . urlencode('This payment does not belong to this order.'));
            return;
        }

        $result = $this->stripe->verifySession($sessionId);

        if (!$result['ok'] || !$result['paid'] || (int) $result['order_id'] !== $orderId) {
            $this->redirect('/orders?error=' . urlencode('Payment could not be confirmed.'));
            return;
        }

        // Poore order (saari rows) ko paid
        $db = Database::getConnection();
        $update = $db->prepare("UPDATE orders SET payment_status = 'paid' WHERE id = ? AND stripe_session_id = ?");
        foreach ($rows as $row) {
            $update->execute([(int) $row['id'], $sessionId]);
        }

        $this->redirect('/orders?updated=1');
    }

    private function getBaseUrl(): string
    {
        // .env ke APP_URL se (config/app.php → appUrl())
        return appUrl();
    }
}
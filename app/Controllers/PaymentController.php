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

    // Order ke "Pay Now" button se yahan aata hai — Stripe checkout session
    // banake, customer ko Stripe ke hosted payment page pe bhej deta hai.
    public function initiate(): void
    {
        $orderId = (int) ($_GET['order_id'] ?? 0);
        $db = Database::getConnection();

        $stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();

        if (!$order) {
            $this->redirect('/orders?error=' . urlencode('Order not found.'));
            return;
        }

        $baseUrl = $this->getBaseUrl();

        $result = $this->stripe->createCheckoutSession(
            $order['product_name'],
            (float) $order['price'] * (int) $order['quantity'],
            'usd',
            $orderId,
            $baseUrl . '/payment/success',
            $baseUrl . '/orders?error=' . urlencode('Payment cancelled.')
        );

        if (!$result['ok']) {
            $this->redirect('/orders?error=' . urlencode($result['message']));
            return;
        }

        $update = $db->prepare("UPDATE orders SET stripe_session_id = ? WHERE id = ?");
        $update->execute([$result['session_id'], $orderId]);

        // Customer ko Stripe ke apne secure payment page pe bhej do
        header('Location: ' . $result['checkout_url']);
        exit;
    }

    // Stripe payment ke baad wapis yahan redirect karta hai
    public function success(): void
    {
        $sessionId = $_GET['session_id'] ?? '';
        $orderId = (int) ($_GET['order_id'] ?? 0);

        if ($sessionId === '' || $orderId <= 0) {
            $this->redirect('/orders?error=' . urlencode('Invalid payment confirmation.'));
            return;
        }

        $result = $this->stripe->verifySession($sessionId);

        if (!$result['ok'] || !$result['paid']) {
            $this->redirect('/orders?error=' . urlencode('Payment could not be confirmed.'));
            return;
        }

        $db = Database::getConnection();
        $update = $db->prepare("UPDATE orders SET payment_status = 'paid' WHERE id = ?");
        $update->execute([$orderId]);

        $this->redirect('/orders?updated=1');
    }

    private function getBaseUrl(): string
    {
        $isHttps = (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
            || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        $scheme = $isHttps ? 'https' : 'http';
        return $scheme . '://' . $_SERVER['HTTP_HOST'];
    }
}
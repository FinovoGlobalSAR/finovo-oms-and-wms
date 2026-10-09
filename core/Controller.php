<?php
require_once __DIR__ . '/../config/app.php';

/**
 * Jab koi sync background (auto-sync / worker.php) se chalta hai, to browser nahi hota,
 * is liye "redirect + exit" ki jagah yeh exception phenka jata hai — taake worker band
 * na ho aur redirect ke URL (jaise "/orders?synced=3") se pata chal jaye kya hua.
 */
class CliRedirect extends Exception
{
    public string $location;

    public function __construct(string $location)
    {
        parent::__construct('Redirect to ' . $location);
        $this->location = $location;
    }
}

abstract class Controller
{
    public function __construct()
    {
        // Background sync (worker) mein browser/cookie nahi hota, is liye wahan asli session start nahi karte
        if (session_status() === PHP_SESSION_NONE && !defined('FINOVO_BACKGROUND_SYNC')) {
            session_start();
        }
    }

    protected function view(string $viewPath, array $data = []): void
    {
        extract($data);
        ob_start();
        require __DIR__ . "/../app/Views/{$viewPath}.php";
        $content = ob_get_clean();
        require __DIR__ . '/../app/Views/layout/layout.php';
    }

    protected function redirect(string $path): void
    {
        $this->stopAndGo($path);
    }

    /**
     * Browser mein: header + exit (pehle jaisa).
     * Background sync (StoreSyncRunner) mein: CliRedirect exception, taake worker chalta rahe.
     */
    private function stopAndGo(string $location): void
    {
        if (defined('FINOVO_BACKGROUND_SYNC')) {
            throw new CliRedirect($location);
        }

        header("Location: {$location}");
        exit;
    }

    protected function getCurrentStore(): array
    {
        $store = $this->pickCurrentStore();

        // Sales / Warehouse Staff ke paas Stores page ("Manage" button) nahi hota,
        // is liye un ka store khud hi "manage" mode mein khol do — warna sidebar khaali rehta
        if (!$this->isAdminOrManager()) {
            $_SESSION['store_context_active'] = true;
        }

        return $store;
    }

    private function pickCurrentStore(): array
    {
        require_once __DIR__ . '/../app/Models/Store.php';
        $storeModel = new Store();

        if (!empty($_SESSION['current_store_id'])) {
            $store = $storeModel->find((int) $_SESSION['current_store_id']);
            if ($store) {
                return $store;
            }
        }

        $store = $storeModel->first();
        $_SESSION['current_store_id'] = $store['id'];
        return $store;
    }

    /** Admin ya Manager? (yehi do Stores page khol kar store "Manage" kar sakte hain) */
    protected function isAdminOrManager(): bool
    {
        return in_array($this->currentRole(), ['admin', 'manager'], true);
    }

    protected function requireLogin(): void
    {
        if (empty($_SESSION['user']['id'])) {
            $_SESSION['error'] = 'Please login first.';
            $this->stopAndGo('/login');
        }
    }

    protected function requireRole(array $allowedRoles): void
    {
        $this->requireLogin();

        $role = strtolower(trim($_SESSION['user']['role'] ?? ''));
        $allowed = array_map('strtolower', $allowedRoles);

        if (!in_array($role, $allowed, true)) {
            $_SESSION['error'] = 'You do not have permission to access that page.';
            $this->stopAndGo('/dashboard');
        }
    }

    // Naya: Orders/Products/Shipments waghera pages sirf tabhi khulenge
    // jab user ne kisi store pe "Manage" dabaya ho. Bina isk, direct URL
    // se bhi block ho jayega, sirf sidebar chhupana kaafi nahi hai.
    protected function requireStoreContext(): void
    {
        $this->requireLogin();

        if (empty($_SESSION['store_context_active'])) {
            // Sales / Warehouse Staff ke paas Stores page nahi hota — un ka store khud chun lo
            if (!$this->isAdminOrManager()) {
                $this->getCurrentStore();
                return;
            }
            $_SESSION['error'] = 'Please select a store to manage first.';
            $this->stopAndGo('/stores');
        }
    }

    protected function currentRole(): string
    {
        return strtolower(trim($_SESSION['user']['role'] ?? ''));
    }

    protected function requireSuperAdmin(): void
    {
        $validUser = env('SUPER_ADMIN_USER', 'superadmin');
        $validPass = env('SUPER_ADMIN_PASSWORD', '');
        $providedUser = $_SERVER['PHP_AUTH_USER'] ?? '';
        $providedPass = $_SERVER['PHP_AUTH_PW'] ?? '';

        if ($validPass === '' || $providedUser !== $validUser || $providedPass !== $validPass) {
            header('WWW-Authenticate: Basic realm="Super Admin Area"');
            http_response_code(401);
            echo "Access denied. Super Admin credentials required.";
            exit;
        }
    }
}
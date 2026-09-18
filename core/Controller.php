<?php
require_once __DIR__ . '/../config/app.php';

abstract class Controller
{
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
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
        header("Location: {$path}");
        exit;
    }

    protected function getCurrentStore(): array
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

    protected function requireLogin(): void
    {
        if (empty($_SESSION['user']['id'])) {
            $_SESSION['error'] = 'Please login first.';
            header('Location: /login');
            exit;
        }
    }

    protected function requireRole(array $allowedRoles): void
    {
        $this->requireLogin();

        $role = strtolower(trim($_SESSION['user']['role'] ?? ''));
        $allowed = array_map('strtolower', $allowedRoles);

        if (!in_array($role, $allowed, true)) {
            $_SESSION['error'] = 'You do not have permission to access that page.';
            header('Location: /dashboard');
            exit;
        }
    }

    // Naya: Orders/Products/Shipments waghera pages sirf tabhi khulenge
    // jab user ne kisi store pe "Manage" dabaya ho. Bina isk, direct URL
    // se bhi block ho jayega, sirf sidebar chhupana kaafi nahi hai.
    protected function requireStoreContext(): void
    {
        $this->requireLogin();

        if (empty($_SESSION['store_context_active'])) {
            $_SESSION['error'] = 'Please select a store to manage first.';
            header('Location: /stores');
            exit;
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
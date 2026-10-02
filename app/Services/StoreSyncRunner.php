<?php

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Database.php';

class StoreSyncRunner
{
    public static function jobMap(): array
    {
        return [
            // ---------- Orders ----------
            'shopify_orders_sync'     => ['OrderController', 'syncShopify'],
            'woocommerce_orders_sync' => ['OrderController', 'syncWooCommerce'],
            'bigcommerce_orders_sync' => ['OrderController', 'syncBigCommerce'],
            'prestashop_orders_sync'  => ['OrderController', 'syncPrestaShop'],
            'opencart_orders_sync'    => ['OrderController', 'syncOpenCart'],
            'oscommerce_orders_sync'  => ['OrderController', 'syncOsCommerce'],
            'wix_orders_sync'         => ['OrderController', 'syncWix'],
            'ebay_orders_sync'        => ['OrderController', 'syncEbay'],
            'magento_orders_sync'     => ['MagentoController', 'syncOrders'],

            // ---------- Products ----------
            'wix_products_sync'       => ['ProductController', 'syncWix'],
            'ebay_products_sync'      => ['ProductController', 'syncEbay'],
            'magento_products_sync'   => ['MagentoController', 'syncProducts'],
            'custom_products_sync'    => ['ProductController', 'syncCustomBridge'],

            // ---------- CJdropshipping: bheje gaye orders ka status + tracking ----------
            'cj_orders_status_sync'   => ['CjFulfillmentController', 'syncAll'],
        ];
    }

    public static function handles(string $type): bool
    {
        return isset(self::jobMap()[$type]);
    }

    public static function run(string $type, array $store): string
    {
        if (!defined('FINOVO_BACKGROUND_SYNC')) {
            define('FINOVO_BACKGROUND_SYNC', true);
        }

        [$controllerClass, $method] = self::jobMap()[$type];

        $_SESSION = [
            'user' => self::adminUser(),
            'current_store_id' => (int) $store['id'],
            'store_context_active' => true,
        ];
        $_GET = [];
        $_POST = [];

        require_once __DIR__ . '/../Controllers/' . $controllerClass . '.php';

        $location = null;
        ob_start(); 
        try {
            $controller = new $controllerClass();
            $controller->$method();
        } catch (CliRedirect $redirect) {
            $location = $redirect->location;
        } finally {
            ob_end_clean();
        }

        if ($location === null) {
            return 'Done.';
        }

        $query = [];
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        if (!empty($query['error'])) {
            throw new Exception($query['error']);
        }
        if (isset($query['synced'])) {
            return (int) $query['synced'] . ' new record(s) synced.';
        }
        if (isset($query['cj_synced'])) {
            return (int) $query['cj_synced'] . ' CJ order(s) updated.';
        }
        if (in_array(parse_url($location, PHP_URL_PATH), ['/login', '/dashboard', '/stores'], true)) {
            throw new Exception('Not allowed to run this sync (redirected to ' . $location . ').');
        }

        return 'Done (' . $location . ').';
    }

    private static function adminUser(): array
    {
        static $user = null;
        if ($user !== null) {
            return $user;
        }

        $row = Database::getConnection()->query(
            "SELECT u.id, u.role_id, u.name, u.email
             FROM users u
             LEFT JOIN roles r ON r.id = u.role_id
             ORDER BY (LOWER(TRIM(COALESCE(r.name, ''))) = 'admin') DESC,
                      (u.status = 'active') DESC,
                      u.id ASC
             LIMIT 1"
        )->fetch();

        if (!$row) {
            throw new Exception('No user accounts found — create at least one Admin from the Employees page.');
        }

        $user = [
            'id' => (int) $row['id'],
            'role_id' => (int) $row['role_id'],
            'name' => $row['name'],
            'email' => $row['email'],
            'role' => 'admin',
        ];

        return $user;
    }
}
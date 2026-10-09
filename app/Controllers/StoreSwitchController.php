<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../Models/Store.php';

/**
 * Sidebar ka "Store" dropdown — ek store se doosre store pe jana.
 *
 * Sab 4 roles ke liye (Admin, Manager, Sales Staff, Warehouse Staff). Pehle Sales / Warehouse Staff
 * hamesha pehle store pe atke rehte the — Stores page un ke paas nahi hai, is liye doosre store ke
 * orders / products dekh hi nahi sakte the.
 */
class StoreSwitchController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->requireRole(['admin', 'manager', 'sales staff', 'warehouse staff']);
    }

    public function switchStore(): void
    {
        $id = (int) ($_GET['store_id'] ?? 0);
        if ($id > 0 && (new Store())->find($id)) {
            $_SESSION['current_store_id'] = $id;
            $_SESSION['store_context_active'] = true;
        }

        // Wapas usi page pe (sirf apni website ke andar ka path — bahar ki website pe redirect na ho)
        $redirect = (string) ($_GET['redirect'] ?? '/dashboard');
        if (!str_starts_with($redirect, '/') || str_starts_with($redirect, '//') || str_contains($redirect, '\\')) {
            $redirect = '/dashboard';
        }
        // Doosre store mein purane store ka "?page=3" / "?id=..." matlab nahi rakhta — sirf path
        $path = parse_url($redirect, PHP_URL_PATH) ?: '/dashboard';
        // Edit / view pages (kisi ek order ya product ke) doosre store mein nahi chalte — list pe bhejo
        $path = preg_replace('#^/(orders|products|customers|warehouses|shipments|returns|purchase-orders)/.+$#', '/$1', $path);
        $this->redirect($path);
    }
}
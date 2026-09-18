<?php
// routes/web.php

$router->get('/login', ['AuthController', 'showLogin']);
$router->post('/login', ['AuthController', 'login']);
$router->get('/logout', ['AuthController', 'logout'], ['AuthMiddleware']);
$router->get('/forgot-password', ['AuthController', 'showForgotPassword']);
$router->post('/forgot-password', ['AuthController', 'sendResetOtp']);
$router->get('/reset-password', ['AuthController', 'showResetPassword']);
$router->post('/reset-password/verify', ['AuthController', 'verifyResetOtp']);
$router->post('/reset-password/set', ['AuthController', 'setNewPassword']);

$router->get('/dashboard', ['DashboardController', 'index']);

$router->get('/employees', ['EmployeeController', 'index']);
$router->post('/employees/store', ['EmployeeController', 'store']);
$router->post('/employees/update', ['EmployeeController', 'update']);
$router->post('/employees/delete', ['EmployeeController', 'delete']);

$router->get('/orders', ['OrderController', 'index']);
$router->get('/orders/create', ['OrderController', 'showCreateForm']);
$router->post('/orders/create', ['OrderController', 'handleCreate']);
$router->post('/api/orders', ['OrderController', 'apiCreate']);
$router->get('/orders/edit', ['OrderController', 'editForm']);
$router->post('/orders/update', ['OrderController', 'update']);
$router->post('/orders/delete', ['OrderController', 'delete']);

$router->get('/orders/settings', ['OrderController', 'showSettingsForm']);
$router->post('/orders/settings', ['OrderController', 'saveSettings']);
$router->get('/orders/sync-shopify', ['OrderController', 'syncShopify']);
$router->get('/orders/export-shopify', ['OrderController', 'exportToShopify']);
$router->get('/orders/invoice', ['InvoiceController', 'download']);
$router->get('/orders/export-csv', ['OrderController', 'exportCsv']);
$router->get('/orders/sync-woocommerce', ['OrderController', 'syncWooCommerce']);
$router->get('/orders/export-woocommerce', ['OrderController', 'exportToWooCommerce']);
$router->get('/products/sync-woocommerce', ['ProductController', 'syncWooCommerce']);
$router->get('/products/export-woocommerce', ['ProductController', 'exportToWooCommerce']);

$router->get('/orders/import', ['OrderController', 'showImportForm']);
$router->post('/orders/import', ['OrderController', 'handleImport']);

$router->get('/products', ['ProductController', 'index']);
$router->get('/products/edit', ['ProductController', 'editForm']);
$router->post('/products/update', ['ProductController', 'update']);
$router->post('/products/delete', ['ProductController', 'delete']);
$router->get('/products/import', ['ProductController', 'showImportForm']);
$router->post('/products/import', ['ProductController', 'handleImport']);
$router->get('/products/export-csv', ['ProductController', 'exportCsv']);
$router->get('/products/sync-shopify', ['ProductController', 'syncShopify']);
$router->get('/products/export-shopify', ['ProductController', 'exportToShopify']);
$router->post('/products/update-stock', ['ProductController', 'updateStock']);

$router->get('/warehouses', ['WarehouseController', 'index']);
$router->post('/warehouses/create', ['WarehouseController', 'create']);
$router->get('/warehouses/view', ['WarehouseController', 'showWarehouse']);
$router->post('/warehouses/receive-stock', ['WarehouseController', 'receiveStock']);
$router->get('/warehouses/product-stock', ['WarehouseController', 'productStock']);
$router->post('/warehouses/update-stock', ['WarehouseController', 'updateStock']);
$router->post('/warehouses/update-variant-stock', ['WarehouseController', 'updateVariantStock']);

$router->get('/stores', ['StoreController', 'index']);
$router->post('/stores/create', ['StoreController', 'create']);
$router->post('/stores/update', ['StoreController', 'update']);
$router->post('/stores/delete', ['StoreController', 'delete']);
$router->get('/stores/switch', ['StoreController', 'switchStore']);
$router->get('/stores/manage', ['StoreController', 'manage']);
$router->get('/stores/exit-management', ['StoreController', 'exitManagement']);

$router->get('/shipments', ['ShipmentController', 'index']);
$router->post('/shipments/create', ['ShipmentController', 'create']);
$router->post('/shipments/update-status', ['ShipmentController', 'updateStatus']);

$router->get('/suppliers', ['SupplierController', 'index']);
$router->post('/suppliers/create', ['SupplierController', 'create']);
$router->post('/suppliers/delete', ['SupplierController', 'delete']);

$router->get('/purchase-orders', ['PurchaseOrderController', 'index']);
$router->post('/purchase-orders/create', ['PurchaseOrderController', 'create']);
$router->post('/purchase-orders/update-status', ['PurchaseOrderController', 'updateStatus']);

$router->get('/stock', ['StockController', 'index']);
$router->post('/stock/transfer', ['StockController', 'transfer']);
$router->post('/stock/adjust', ['StockController', 'adjust']);

$router->get('/returns', ['ReturnController', 'index']);
$router->post('/returns/create', ['ReturnController', 'create']);
$router->post('/returns/update-status', ['ReturnController', 'updateStatus']);

$router->get('/customers', ['CustomerController', 'index']);
$router->get('/customers/view', ['CustomerController', 'show']);

$router->get('/picking', ['PickingController', 'index']);
$router->post('/picking/update-status', ['PickingController', 'updateStatus']);

$router->post('/webhooks/shopify', ['WebhookController', 'shopify']);
$router->post('/webhooks/woocommerce', ['WebhookController', 'woocommerce']);

$router->get('/integration-errors', ['IntegrationErrorController', 'index']);
$router->post('/integration-errors/resolve', ['IntegrationErrorController', 'resolve']);

$router->get('/sku-mappings', ['SkuMappingController', 'index']);
$router->post('/sku-mappings/create', ['SkuMappingController', 'create']);

$router->get('/audit-log', ['AuditLogController', 'index']);

$router->post('/orders/test-shopify-connection', ['OrderController', 'testShopifyConnection']);
$router->post('/orders/test-woocommerce-connection', ['OrderController', 'testWooCommerceConnection']);

$router->get('/products/sync-custom-bridge', ['ProductController', 'syncCustomBridge']);

$router->post('/webhooks/custom-bridge', ['WebhookController', 'customBridge']);

$router->post('/woocommerce-connect/connect', ['WooCommerceConnectController', 'connect']);
$router->post('/woocommerce-connect/callback', ['WooCommerceConnectController', 'callback']);

$router->get('/shopify-connect/connect', ['ShopifyConnectController', 'connect']);
$router->post('/shopify-connect/connect', ['ShopifyConnectController', 'connect']);
$router->get('/shopify-connect/callback', ['ShopifyConnectController', 'callback']);
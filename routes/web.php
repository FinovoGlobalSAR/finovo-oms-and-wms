<?php
// routes/web.php

$router->get('/dashboard', ['DashboardController', 'index']);

$router->get('/employees', ['EmployeeController', 'index']);
$router->post('/employees/store', ['EmployeeController', 'store']);
$router->post('/employees/update', ['EmployeeController', 'update']);
$router->post('/employees/delete', ['EmployeeController', 'delete']);

$router->get('/orders', ['OrderController', 'index']);
$router->get('/orders/create', ['OrderController', 'showCreateForm']);
$router->post('/orders/create', ['OrderController', 'handleCreate']);
$router->post('/api/orders', ['OrderController', 'apiCreate']);

$router->get('/orders/settings', ['OrderController', 'showSettingsForm']);
$router->post('/orders/settings', ['OrderController', 'saveSettings']);
$router->get('/orders/sync-shopify', ['OrderController', 'syncShopify']);
$router->get('/orders/export-shopify', ['OrderController', 'exportToShopify']);
$router->get('/orders/export-csv', ['OrderController', 'exportCsv']);

$router->get('/orders/import', ['OrderController', 'showImportForm']);
$router->post('/orders/import', ['OrderController', 'handleImport']);

$router->get('/products', ['ProductController', 'index']);
$router->get('/products/import', ['ProductController', 'showImportForm']);
$router->post('/products/import', ['ProductController', 'handleImport']);
$router->get('/products/export-csv', ['ProductController', 'exportCsv']);
$router->get('/products/sync-shopify', ['ProductController', 'syncShopify']);
$router->get('/products/export-shopify', ['ProductController', 'exportToShopify']);
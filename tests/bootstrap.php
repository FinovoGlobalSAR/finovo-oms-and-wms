<?php
// Tests ke dauran stock kisi platform pe push na ho (StockPushService ke apne tests transport se chalte hain)
$_ENV['STOCK_PUSH'] = 'false';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../app/Services/InventoryService.php';
require_once __DIR__ . '/../app/Models/Product.php';
require_once __DIR__ . '/../app/Models/Warehouse.php';
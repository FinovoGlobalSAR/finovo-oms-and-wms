markdown
# Finovo OMS + WMS

Order Management System + Warehouse Management System for Finovo. Built in Pure PHP (no framework) + MySQL, using a custom lightweight MVC structure.

## Local Setup

**Requirements:** XAMPP (Apache + MySQL + PHP)

1. Clone the repo into `C:\xampp\htdocs\finovo-oms-and-wms`
2. Start MySQL manually (recommended, avoids XAMPP's bundled MariaDB conflicts):

cd C:\xampp\mysql\bin
.\mysqld.exe --console

3. Create a database in phpMyAdmin (`finovo_oms_wms`), update `.env` with your DB credentials.
4. Run migrations:

C:\xampp\php\php.exe migrate.php

5. Start the PHP built-in server:

C:\xampp\php\php.exe -S localhost:8000 -t public public/index.php

6. Visit `http://localhost:8000`

## Folder Structure

app/
Controllers/ -- one controller per feature area (Orders, Products, Stores, Warehouses...)
Models/ -- one model per DB table, handles all SQL for that table
Views/ -- PHP templates, grouped by feature (orders/, products/, stores/...)
Middlewares/ -- cross-cutting logic (e.g. CanonicalMapper for status translation)
Services/ -- business logic shared across controllers (InventoryService, AuditLogService...)
Connectors/ -- third-party API wrapper classes (BridgeConnector for custom stores)
core/ -- framework internals: Router, Controller base class, Database connection, Model base class
database/
migrations/ -- one file per schema change, run in order via migrate.php
routes/
web.php -- all URL routes mapped to Controller@method
public/
index.php -- single entry point, all requests go through here
.env -- DB credentials, platform API secrets (Shopify/BigCommerce client ID+secret)


## Core Concepts

**Store context:** Every logged-in user operates within one "current store" at a time (`$_SESSION['current_store_id']`). Controllers extend a base `Controller` class with `getCurrentStore()` to fetch it. Multi-store support means one warehouse can serve multiple stores, and one store can pull stock from multiple warehouses (`stock_availability` table links product + warehouse + quantity).

**Roles:** Company Admin, Manager, Sales Staff, Warehouse Staff — checked via `requireRole([...])` in each controller's constructor.

**Inventory:** All stock changes go through `InventoryService::reserve()` / `::release()` — never update `stock_quantity` directly. This keeps every stock movement logged in `inventory_ledger` (who changed it, why, when) and prevents overselling by checking availability before committing.

**Canonical status mapping:** Each platform (Shopify, WooCommerce, BigCommerce, PrestaShop) has its own order status names. `app/Middlewares/CanonicalMapper.php` translates all of them into one internal set (`pending`, `processing`, `delivered`, `cancelled`) so the rest of the app never needs to know which platform an order came from.

## Integrations (Products + Orders sync)

| Platform | Auth method | Products pull | Orders pull |
|---|---|---|---|
| Shopify | One-click OAuth ("Connect Shopify") | `ProductController::syncShopify()` | `OrderController::syncShopify()` |
| WooCommerce | One-click OAuth ("Connect WooCommerce") | `syncWooCommerce()` | `syncWooCommerce()` |
| BigCommerce | One-click OAuth ("Connect" via app install) | `syncBigCommerce()` | `syncBigCommerce()` |
| PrestaShop | Manual Webservice API key | `syncPrestaShop()` | `syncPrestaShop()` |
| Custom Bridge | Manual API key + shared secret (HMAC signed) | `syncCustomBridge()` via `BridgeConnector` | via webhook, `WebhookController::customBridge()` |

All sync methods follow the same pattern: fetch from the platform's API, match against `external_<platform>_product_id` / `external_<platform>_order_id` columns to avoid duplicates, insert new records, update status on existing ones.

**Webhooks** (real-time, instead of manual sync button): `WebhookController.php` handles incoming `orders/create` events from Shopify/WooCommerce, verifies HMAC signature, and calls the same canonical-mapping + inventory-reserve logic as the manual sync methods.

## Known Platforms Not Yet Integrated

- **Amazon, Noon, Daraz** — blocked on business registration / commercial documents (NTN or equivalent), not a code issue.
- **Magento, Etsy, Wix, Shopee, Lazada, etc.** — not started (P2/P3 priority per team lead).

## Still Manual / Not Automated

- Sync is triggered by a button click per store, per platform — no scheduled background job. A basic client-side "Auto-sync" toggle exists on the Products page (`localStorage` + `setInterval`, reloads the page every 5 minutes) as a lightweight substitute.
- No automated test suite — all testing is manual, in-browser.
- SKU/field mapping between platforms and internal products is not user-configurable (hardcoded in each `sync*()` method).
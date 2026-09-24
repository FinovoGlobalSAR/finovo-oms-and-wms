# Finovo OMS + WMS

Order Management System + Warehouse Management System for Finovo. Built in Pure PHP (no framework) + MySQL, using a custom lightweight MVC structure.

## Local Setup

**Requirements:** XAMPP (Apache + MySQL + PHP), Composer (for PHPUnit and DHL/Stripe integration)

1. Clone the repo into `C:\xampp\htdocs\finovo-oms-and-wms`
2. Start MySQL manually (recommended, avoids XAMPP's bundled MariaDB conflicts):

cd C:\xampp\mysql\bin
.\mysqld.exe --console

3. Create a database in phpMyAdmin (`finovo_oms_wms`), update `.env` with your DB credentials.
4. Run migrations:

C:\xampp\php\php.exe migrate.php

5. Install Composer dependencies (PHPUnit test suite):

composer install

6. Start the PHP built-in server:

C:\xampp\php\php.exe -S localhost:8000 -t public public/index.php

7. Visit `http://localhost:8000`

## Folder Structure

app/
Controllers/ -- one controller per feature area (Orders, Products, Stores, Warehouses, Returns, 3PL, Payments, Field Mapping...)
Models/ -- one model per DB table, handles all SQL for that table
Views/ -- PHP templates, grouped by feature (orders/, products/, stores/, returns/, threepl/, field-mappings/...)
Middlewares/ -- cross-cutting logic (CanonicalMapper for status translation, ProductAvailabilityGuard for sync validation)
Services/ -- business logic shared across controllers (InventoryService, AuditLogService...)
Connectors/ -- third-party API wrapper classes (BridgeConnector, OpenCartConnector, DhlConnector, StripeConnector)
core/ -- framework internals: Router, Controller base class, Database connection, Model base class
database/
migrations/ -- one file per schema change, run in order via migrate.php
routes/
web.php -- all URL routes mapped to Controller@method
public/
index.php -- single entry point, all requests go through here
tests/ -- PHPUnit automated test suite (18 tests)
worker.php -- background job worker (async sync queue)
load-test.php -- concurrency/load testing script
.env -- DB credentials, platform API secrets (Shopify/BigCommerce client ID+secret, DHL/Stripe keys, external DB credentials for OpenCart/osCommerce)

## Core Concepts

**Store context:** Every logged-in user operates within one "current store" at a time (`$_SESSION['current_store_id']`). Controllers extend a base `Controller` class with `getCurrentStore()` to fetch it. Multi-store support means one warehouse can serve multiple stores, and one store can pull stock from multiple warehouses (`product_warehouse_stock` table links product + warehouse + quantity).

**Roles:** Company Admin, Manager, Sales Staff, Warehouse Staff — checked via `requireRole([...])` in each controller's constructor.

**Inventory:** All stock changes go through `InventoryService::reserve()` / `::release()` — never update `stock_quantity` directly. This keeps every stock movement logged in `inventory_ledger` (who changed it, why, when) and prevents overselling by checking availability before committing.

**Multi-Warehouse Allocation:** `InventoryService::reserveAcrossWarehouses()` — when one warehouse doesn't have enough stock for an order, it automatically splits the reservation across all warehouses linked to that store. Does a dry-run check first (to confirm total availability across warehouses), then commits per-warehouse; rolls back any partial reservations if a later warehouse fails.

**Canonical status mapping:** Each platform (Shopify, WooCommerce, BigCommerce, PrestaShop, OpenCart, osCommerce) has its own order status names/codes. `app/Middlewares/CanonicalMapper.php` translates them into one internal set (`pending`, `processing`, `delivered`, `cancelled`) so the rest of the app never needs to know which platform an order came from.

**Product Availability Guard:** `app/Middlewares/ProductAvailabilityGuard.php` — during order sync, if the incoming product doesn't already exist in Finovo (or doesn't have enough stock), the order is silently skipped rather than auto-creating a new product. Keeps synced orders limited to products we actually manage.

**Field Mapping:** `app/Models/FieldMapping.php` — lets each store configure which platform-specific field (e.g. Shopify's `title` vs a custom field) maps to each internal product field, via the `/field-mappings` admin page. Falls back to sensible per-platform defaults if not configured. Supports dotted paths for nested JSON (e.g. `variants.0.price`).

**Async Job Queue:** `app/Models/JobQueue.php` + `worker.php` — instead of syncs blocking the page while waiting on an external API, a "Sync (Async/Queue)" button pushes a job row into the `jobs` table and redirects immediately. `worker.php` (run manually or via a scheduled task) picks up pending jobs, processes them, and records success/failure with a result message.

## Integrations (Products + Orders sync)

| Platform | Auth method | Sync mechanism |
|---|---|---|
| Shopify | One-click OAuth ("Connect Shopify") | REST API (`ProductController::syncShopify()`, `OrderController::syncShopify()`) — also has an async variant (`syncShopifyAsync()`) |
| WooCommerce | One-click OAuth ("Connect WooCommerce") | REST API (`syncWooCommerce()`) |
| BigCommerce | One-click OAuth (app install via Developer Portal) | REST API (`syncBigCommerce()`) |
| PrestaShop | One-click — custom PrestaShop module (`finovoconnect`) auto-generates a webservice API key on install and sends it to Finovo | Webservice API (`syncPrestaShop()`) |
| OpenCart | Manual — store's database name entered in Finovo | Direct external MySQL read (`syncOpenCart()`, via `externalDbConnection()`, credentials from `.env`) |
| osCommerce | Manual — store's database name entered in Finovo | Direct external MySQL read (`syncOsCommerce()`) |
| Custom Bridge | Manual API key + shared secret (HMAC signed) | `syncCustomBridge()` via `BridgeConnector`, plus real-time via webhook (`WebhookController::customBridge()`) |

All sync methods follow the same pattern: fetch from the platform's API/DB, match against `external_<platform>_product_id` / `external_<platform>_order_id` columns to avoid duplicates, insert new records, update status on existing ones. Order syncs additionally run through `ProductAvailabilityGuard` before reserving stock.

**Webhooks** (real-time, instead of manual sync button): `WebhookController.php` handles incoming `orders/create` events from Shopify/WooCommerce, verifies HMAC signature, and calls the same canonical-mapping + inventory-reserve logic as the manual sync methods.

**External DB credentials:** OpenCart and osCommerce connect directly to their own MySQL databases (not via API, since neither platform offers a sufficient public API). Host/user/password for these come from `.env` (`DB_EXTERNAL_HOST`, `DB_EXTERNAL_USER`, `DB_EXTERNAL_PASSWORD`) — only the database name is store-specific and stored per-store in Finovo's own DB.

## Returns

`app/Controllers/ReturnController.php` + `app/Models/ReturnRequest.php` — a return state machine: `requested → approved → completed` (or `rejected`), with a `condition_status` of `resellable` or `damaged`. Completing a resellable return restores stock via `InventoryService::release()` (logged in `inventory_ledger` as `return_completed`); a damaged return does not restore stock. Rejected returns don't block the same order from getting a new return record.

## 3PL / Courier Integration

`app/Controllers/ThreePlController.php` — handles handover-to-courier and remittance tracking on top of the existing `shipments` table:

- **LM Inventory Scan** (`/3pl/scan`) — scan/paste AWB numbers to mark shipments as handed over to the courier; automatically moves shipment status to `dispatched`.
- **3PL Remittance** (`/3pl/remittance`) — tracks COD payment status (`not_remitted` / `remitted`) and amount received back from the courier, per shipment.
- **Live tracking** (`/3pl/track?id=`) — currently wired for DHL only, via `app/Connectors/DhlConnector.php` (DHL's free Shipment Tracking - Unified API). Other couriers (TCS, SMSA, Aramex, etc.) all require a verified business/courier account to get API access — not a code limitation.

## Payments

`app/Controllers/PaymentController.php` + `app/Connectors/StripeConnector.php` — Stripe Checkout (test mode) integration for "Card" payment method orders. Generates a raw-cURL hosted Checkout session (no Composer SDK needed), redirects the customer to Stripe, and on successful payment marks the order `payment_status = 'paid'` after verifying the session server-side.

## Performance

- 19 database indexes added across `orders`, `products`, `product_warehouse_stock`, `inventory_ledger`, `stores`, `returns`, `shipments`.
- Fixed N+1 queries in Products page (`variantCountsForProducts()` batches all variant counts into one query) and Orders page (store/platform filtering moved into SQL via `allByStore()` / `filterBySourceAndStore()`, instead of loading all orders and filtering in PHP).
- Load tested up to 100,000 requests (100 concurrent) on the local dev server (`php -S`) — 99.35% success rate. Lower request-volume tests (up to 10,000) had zero failures.

## Automated Tests

18 PHPUnit tests in `tests/` covering: inventory reserve/release, multi-warehouse allocation (including rollback on partial failure), order creation/filtering, product creation/duplication prevention, returns (resellable vs. damaged stock restoration, rejected-return exclusion), and the product-availability sync guard. Run with:

vendor\bin\phpunit

## Known Platforms Not Yet Integrated

- **Amazon, Noon, Daraz, Etsy, Walmart, TCS, SMSA Express, Aramex** — all blocked on business/seller account verification (not a code issue; none offer a public developer sandbox without an active seller/courier account).
- **eBay** — sandbox account created, approval pending.
- **DHL** — tracking API integrated and functional; API key activation has been inconsistent on DHL's side (a first key was auto-revoked a few hours after approval), currently on a second key awaiting activation.
- **Magento, Shopee, Lazada, etc.** — not started (lower priority).

## Still Manual / Not Automated

- Only Shopify has an async (queued) sync option so far; other platforms still sync synchronously on button click. The `worker.php` pattern is in place to extend to other platforms.
- SKU mapping (matching an external product to an internal one) is separate from field mapping (which external field maps to which internal field) — both exist as separate admin pages (`/sku-mappings`, `/field-mappings`).
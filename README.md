# Finovo OMS + WMS

Order Management System + Warehouse Management System for Finovo. Built in Pure PHP (no framework) + MySQL, using a custom lightweight MVC structure.

Single-tenant (one organization), with multiple stores. Each store connects to one sales channel (Shopify, eBay, Magento, etc.) and can be linked to one or more warehouses.

## Local Setup

**Requirements:** XAMPP (Apache + MySQL + PHP 8), Composer (for the PHPUnit test suite)

1. Clone the repo into `C:\xampp\htdocs\finovo-oms-and-wms` (or any folder).
2. Start MySQL from the XAMPP Control Panel (or manually):

```
   cd C:\xampp\mysql\bin
   .\mysqld.exe --console
```

3. Create a database in phpMyAdmin named `finovo_oms_wms`.
4. Create a `.env` file in the project root. **`.env` and `.env.example` are not in the repo** (they hold secrets) — ask the team lead for a copy. See [Environment Variables](#environment-variables) for the keys it needs.
5. Run migrations (this builds every table — there is no SQL dump in the repo):

```
   C:\xampp\php\php.exe migrate.php
```

6. Create the first Admin account (command line only, blocked from the browser):

```
   C:\xampp\php\php.exe create-admin.php "Admin Name" admin@company.com "StrongPassword123"
```

7. Install Composer dependencies (PHPUnit test suite):

```
   composer install
```

8. Start the PHP built-in server:

```
   C:\xampp\php\php.exe -S localhost:8000 -t public public/index.php
```

9. Visit `http://localhost:8000`

## What Is Not in the Repo (and Why)

| File | Why it is ignored | How to get it |
|---|---|---|
| `.env` | Database password and every API secret (Shopify, eBay, DHL, Stripe, SMTP) | Ask the team lead |
| `.env.example` | Kept out by team decision | Ask the team lead |
| `*.sql` (database dumps) | Contain real data and stored API keys | Not needed — run `migrate.php` |

**Rule:** every database change goes through a migration file in `database/migrations/`. Never run SQL directly against the database.

## Environment Variables

Keys the `.env` file needs (values are not listed here — get them from the team lead):

| Group | Keys |
|---|---|
| App | `APP_NAME`, `APP_ENV`, `APP_DEBUG`, `APP_URL`, `APP_BASE_PATH`, `SESSION_LIFETIME`, `CURL_VERIFY_SSL` |
| Database | `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` |
| External DB (OpenCart / osCommerce) | `DB_EXTERNAL_HOST`, `DB_EXTERNAL_USER`, `DB_EXTERNAL_PASSWORD` |
| Email (password reset OTP) | `SMTP_HOST`, `SMTP_PORT`, `SMTP_USERNAME`, `SMTP_PASSWORD`, `SMTP_FROM_EMAIL`, `SMTP_FROM_NAME` |
| Shopify | `SHOPIFY_CLIENT_ID`, `SHOPIFY_CLIENT_SECRET` |
| BigCommerce | `BIGCOMMERCE_CLIENT_ID`, `BIGCOMMERCE_CLIENT_SECRET`, `BIGCOMMERCE_ACCOUNT_UUID` |
| eBay | `EBAY_ENV` (`sandbox` or `production`), `EBAY_APP_ID`, `EBAY_DEV_ID`, `EBAY_CERT_ID`, `EBAY_RUNAME`, `EBAY_NOTIFICATION_ENDPOINT`, `EBAY_NOTIFICATION_VERIFICATION_TOKEN` |
| Wix | `WIX_API_KEY` |
| CJdropshipping | `CJ_API_KEY` |
| DHL | `DHL_API_KEY`, `DHL_API_SECRET` |
| Stripe | `STRIPE_SECRET_KEY` |
| Super Admin area | `SUPER_ADMIN_USER`, `SUPER_ADMIN_PASSWORD` |

`CURL_VERIFY_SSL=false` is only for local XAMPP (no certificate bundle). Keep it `true` on the live server.

## Folder Structure

```
app/
  Controllers/   -- one controller per feature area (Orders, Products, Stores, Warehouses, Returns, 3PL, Payments, Magento, eBay, CJ...)
  Models/        -- one model per DB table, handles all SQL for that table
  Views/         -- PHP templates, grouped by feature (orders/, products/, stores/, returns/, threepl/, field-mappings/...)
  Middlewares/   -- cross-cutting logic (AuthMiddleware, CanonicalMapper, ProductAvailabilityGuard)
  Services/      -- shared business logic (InventoryService, AuditLogService, EbayAuthService, StoreSyncRunner...)
  Connectors/    -- third-party API wrappers (Bridge, CJdropshipping, DHL, eBay, Magento, Stripe, Wix)
core/            -- framework internals: Router, Controller base class, Database connection, Model base class
database/
  migrations/    -- one file per schema change, run in order via migrate.php
routes/
  web.php        -- all URL routes mapped to Controller@method
public/
  index.php      -- single entry point, all requests go through here
tests/           -- PHPUnit automated test suite (18 tests)

migrate.php                -- runs pending migrations
create-admin.php           -- creates the first Admin user (CLI only)
auto-sync.php              -- queues sync jobs for every store (see Auto-Sync)
auto-sync-and-process.php  -- queues jobs + runs the worker in one go (used by Task Scheduler / cron)
worker.php                 -- processes queued sync jobs
register-webhooks.php      -- registers Shopify / WooCommerce / BigCommerce order webhooks (run once)
ebay-notifications-setup.php -- subscribes eBay order notifications (run once)
load-test.php              -- concurrency/load testing script
```

All CLI scripts above are blocked from the browser by `.htaccess`.

## Core Concepts

**Store context:** Every logged-in user operates within one "current store" at a time (`$_SESSION['current_store_id']`), selected by clicking **Manage** on the Stores page. Orders, Products, Shipments, etc. only open when a store is being managed (`requireStoreContext()`). Multi-store support means one warehouse can serve multiple stores, and one store can pull stock from multiple warehouses (`product_warehouse_stock` table links product + warehouse + quantity).

**Roles:** Admin, Manager, Sales Staff, Warehouse Staff — checked via `requireRole([...])` in each controller's constructor. There is no public registration page; the Admin creates staff accounts from the Employees page.

**Inventory:** All stock changes go through `InventoryService::reserve()` / `::release()` — never update `stock_quantity` directly. This keeps every stock movement logged in `inventory_ledger` (who changed it, why, when) and prevents overselling by checking availability before committing.

**Multi-Warehouse Allocation:** `InventoryService::reserveAcrossWarehouses()` — when one warehouse doesn't have enough stock for an order, it automatically splits the reservation across all warehouses linked to that store. Does a dry-run check first (to confirm total availability across warehouses), then commits per-warehouse; rolls back any partial reservations if a later warehouse fails.

**Canonical status mapping:** Each platform has its own order status names/codes. `app/Middlewares/CanonicalMapper.php` translates them into one internal set (`pending`, `processing`, `delivered`, `cancelled`) so the rest of the app never needs to know which platform an order came from.

**Product Availability Guard:** `app/Middlewares/ProductAvailabilityGuard.php` — during order sync, if the incoming product doesn't already exist in Finovo (or doesn't have enough stock), the order is skipped rather than auto-creating a new product. Keeps synced orders limited to products we actually manage. This is why products must be synced before orders.

**Field Mapping:** `app/Models/FieldMapping.php` — lets each store configure which platform-specific field maps to each internal product field, via the `/field-mappings` admin page. Falls back to sensible per-platform defaults if not configured. Supports dotted paths for nested JSON (e.g. `variants.0.price`).

## Integrations (Products + Orders sync)

| Platform | Connect method | Products | Orders | Real-time |
|---|---|---|---|---|
| Shopify | One-click OAuth ("Connect Shopify") | REST API | REST API | Webhook (`orders/create`) |
| WooCommerce | One-click OAuth ("Connect WooCommerce") | REST API | REST API | Webhook |
| BigCommerce | One-click OAuth (app install) | REST API | REST API | Webhook |
| PrestaShop | One-click — custom module (`finovoconnect`) sends its webservice key to Finovo | Webservice API | Webservice API | — |
| OpenCart | Store's database name entered in Finovo | Direct MySQL read | Direct MySQL read | — |
| osCommerce | Store's database name entered in Finovo | Direct MySQL read | Direct MySQL read | — |
| Wix | API key + Site ID | REST API | REST API | Webhook |
| eBay | One-click OAuth ("Connect eBay"), token auto-refresh | Inventory API | Fulfillment API | Notification API (`ORDER_CONFIRMATION`) |
| Magento 2 | Integration Access Token (System → Integrations) | REST `/V1/products` + `/V1/stockItems` | REST `/V1/orders` | — |
| Custom Store | API key + shared secret (HMAC signed) | `BridgeConnector` | Pushed by the store | Webhook |

All sync methods follow the same pattern: fetch from the platform's API/DB, match against the `external_<platform>_*_id` columns to avoid duplicates, insert new records, update status on existing ones. Order syncs additionally run through `ProductAvailabilityGuard` before reserving stock.

**eBay:** `EbayAuthService` handles OAuth (consent → code → refresh token) and refreshes the access token automatically before it expires; the older manual "User Token" still works as a fallback. Real-time orders arrive at `/webhooks/ebay` (`EbayWebhookController`), which answers eBay's challenge and verifies the `x-ebay-signature` header. In the eBay Developer Portal, the RuName's "Auth accepted URL" must be `https://<your-domain>/ebay-connect/callback`.

**Magento:** Needs Magento 2.4.4+ setting *Stores → Configuration → Services → OAuth → "Allow OAuth Access Tokens to be used as standalone Bearer tokens" = Yes*. Configurable "parent" products are skipped (they have no own stock/price).

**External DB credentials:** OpenCart and osCommerce connect directly to their own MySQL databases (neither offers a sufficient public API). Host/user/password come from `.env` (`DB_EXTERNAL_*`) — only the database name is stored per store.

**Webhooks need a public URL** (live domain or ngrok) — platforms cannot reach `localhost`. Locally, Auto-Sync covers the same orders every few minutes.

### Order Push (Finovo → platform)

Orders created in Finovo (manual / CSV) can be pushed to **Shopify** and **WooCommerce**. The Orders page only shows the push option matching the managed store's platform, and shows "Already in Shopify/WooCommerce" for orders that came from (or were already pushed to) that platform, to avoid duplicates. The push endpoints also refuse orders from another store or a non-matching platform.

## Auto-Sync (no Sync button needed)

`auto-sync.php` queues sync jobs for every store according to its platform — **products first, then orders** — and `worker.php` processes them. `auto-sync-and-process.php` does both in one command.

| Platform | Products | Orders |
|---|---|---|
| Shopify, WooCommerce, BigCommerce, PrestaShop, OpenCart, osCommerce | ✅ | ✅ |
| Wix, eBay, Magento | ✅ | ✅ |
| Custom Store | ✅ | (pushed by the store) |
| CJdropshipping | — | Status + tracking of orders sent to CJ |

New job types are run by `app/Services/StoreSyncRunner.php`, which calls **the same controller method as the Sync button**, so manual and automatic sync always behave the same. In background mode (`FINOVO_BACKGROUND_SYNC`), `Controller::redirect()` throws `CliRedirect` instead of `exit`, and the runner reads the result from the redirect URL (`?synced=3` / `?error=...`). Results are stored per job in the `jobs` table.

Run once manually:

```
C:\xampp\php\php.exe auto-sync-and-process.php
```

Run every 5 minutes on Windows (Command Prompt as Administrator):

```
schtasks /create /tn "Finovo Auto Sync" /tr "C:\xampp\php\php.exe C:\path\to\finovo-oms-and-wms\auto-sync-and-process.php" /sc minute /mo 5
```

On a Linux server, use a cron job instead:

```
*/5 * * * * php /path/to/finovo-oms-and-wms/auto-sync-and-process.php
```

## CJdropshipping (Fulfillment)

`CjFulfillmentController` + `CjDropshippingConnector` — an order can be sent to CJdropshipping from the Orders page ("Send to CJdropshipping"). Finovo creates the CJ order (shipping quote first; a phone number is required by CJ), stores the CJ order ID, and later pulls its status and tracking number ("Sync CJ" or Auto-Sync). Payment for the CJ order is completed in the CJ account. CJ's API allows 1 request per second, so the connector throttles calls.

## Returns

`app/Controllers/ReturnController.php` + `app/Models/ReturnRequest.php` — a return state machine: `requested → approved → completed` (or `rejected`), with a `condition_status` of `resellable` or `damaged`. Completing a resellable return restores stock via `InventoryService::release()` (logged in `inventory_ledger` as `return_completed`); a damaged return does not restore stock. Rejected returns don't block the same order from getting a new return record.

## 3PL / Courier Integration

`app/Controllers/ThreePlController.php` — handles handover-to-courier and remittance tracking on top of the existing `shipments` table:

- **LM Inventory Scan** (`/3pl/scan`) — scan/paste AWB numbers to mark shipments as handed over to the courier; automatically moves shipment status to `dispatched`.
- **3PL Remittance** (`/3pl/remittance`) — tracks COD payment status (`not_remitted` / `remitted`) and amount received back from the courier, per shipment.
- **Live tracking** (`/3pl/track?id=`) — DHL only, via `app/Connectors/DhlConnector.php` (DHL Shipment Tracking - Unified API). Other couriers (TCS, SMSA, Aramex, etc.) require a verified business/courier account for API access — not a code limitation.

## Payments

`app/Controllers/PaymentController.php` + `app/Connectors/StripeConnector.php` — Stripe Checkout (test mode) for "Card" payment method orders. Creates a hosted Checkout session with raw cURL (no Composer SDK), redirects the customer to Stripe, and on successful payment marks the order `payment_status = 'paid'` after verifying the session server-side.

## Performance

- 19 database indexes across `orders`, `products`, `product_warehouse_stock`, `inventory_ledger`, `stores`, `returns`, `shipments`.
- Fixed N+1 queries on the Products page (`variantCountsForProducts()` batches variant counts into one query) and Orders page (store/platform filtering done in SQL via `allByStore()` / `filterBySourceAndStore()`).
- Load tested up to 100,000 requests (100 concurrent) on the local dev server (`php -S`) — 99.35% success rate. Lower request-volume tests (up to 10,000) had zero failures.

## Automated Tests

18 PHPUnit tests in `tests/` covering: inventory reserve/release, multi-warehouse allocation (including rollback on partial failure), order creation/filtering, product creation/duplication prevention, returns (resellable vs. damaged stock restoration, rejected-return exclusion), and the product-availability sync guard. Run with:

```
vendor\bin\phpunit
```

## Known Platforms Not Yet Integrated

- **Amazon, Noon, Daraz, Etsy, Walmart, TCS, SMSA Express, Aramex** — blocked on business/seller account verification (not a code issue; none offer a public developer sandbox without an active seller/courier account).
- **DHL** — tracking API integrated; key activation on DHL's side has been inconsistent.
- **Magento** — integration is complete and tested against a mock Magento REST API; a real Magento install needs a server (it is too heavy for an 8 GB laptop with Docker).
- **Shopee, Lazada, etc.** — not started (lower priority).

## Still Manual / Not Automated

- Order **push** (Finovo → platform) exists only for Shopify and WooCommerce.
- Auto-Sync must be scheduled (Task Scheduler / cron); without it, syncs run only on button click or webhook.
- SKU mapping (matching an external product to an internal one) is separate from field mapping (which external field maps to which internal field) — both exist as separate admin pages (`/sku-mappings`, `/field-mappings`).
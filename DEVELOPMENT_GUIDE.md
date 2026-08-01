# Development Guide

## Architecture

Laravel exposes JSON routes from `routes/api.php`; all business pages are served by the Vue single-page shell. Sanctum bearer tokens protect every business API, while Spatie Laravel Permission enforces route-level capabilities. Controllers validate input and delegate inventory work to `PurchaseService`, `SaleService`, and `StockService`. Eloquent models represent the normalized schema.

Backend code is under `app/Http`, `app/Models`, `app/Services`, `app/Enums`, and `app/Support`. Frontend code is under `resources/js` with pages, router, API client, and reusable application layout; global Tailwind-backed styling is in `resources/css/app.css`.

## Schema and relationships

Products own stock movements and transaction items. Suppliers own purchases and supplier payments; customers own sales and customer payments. Purchases/sales have item rows. Stock adjustments are immutable reasons for manual movements. Payments may link to a party and transaction. Settings hold simple key/value configuration. Financial values use `decimal(15,2)` and quantities use `decimal(15,3)`.

## Inventory transactions

`PurchaseService` starts a database transaction, locks each product, computes totals and weighted-average cost, creates items, updates cached stock, writes movements, and writes an initial payment. The weighted average is:

`((old stock × old average cost) + (purchase quantity × price)) / (old stock + quantity)`.

`SaleService` locks products before validating availability, stores the current average cost on each sale item, decreases cached stock, writes movements, and records payment. Gross profit is sale subtotal less `quantity × saved unit cost`.

Cancellation locks the transaction and its products. Purchase cancellation requires enough remaining stock, writes stock-out reversals, restores the recorded pre-purchase average cost, reverses linked payments, and changes status. Sale cancellation writes stock-in reversals and reverses payments. Repeated cancellation is rejected.

Stock adjustments run through `StockService`; every cached-stock update has a matching ledger row. Negative results raise a validation exception and roll back.

## Balances and reports

Supplier outstanding equals opening balance plus completed purchases minus non-reversed supplier payments. Customer outstanding uses completed sales and customer payments. Reports are provided by `OperationsController`; add a report by adding a new match branch, route constraint, test, and Vue option.

## API

Authentication: `POST /api/login`, `POST /api/logout`, `GET /api/user`.

Resources: `/api/products`, `/api/suppliers`, `/api/customers`; party ledgers use `/{id}/ledger`. Transactions: `/api/purchases`, `/api/sales`, plus `POST /{id}/cancel`. Inventory: `GET /api/stock-movements`, `POST /api/stock-adjustments`. Payments: `GET|POST /api/payments`. Dashboard: `GET /api/dashboard`. Reports: `GET /api/reports/{stock|low-stock|purchases|sales|profit}`. User administration: `GET|POST /api/users`, `PUT /api/users/{user}`, and `GET /api/users/roles`.

Successful responses contain `success`, `message`, and `data`. Validation failures contain `success=false`, `message`, and `errors`. Form requests validate transaction payloads; database constraints enforce identity and relationships.

## Authentication and frontend state

Login exchanges credentials for a Sanctum token stored in browser local storage. The response also contains role and permission names. The Axios interceptor attaches the token and clears the full session after an unauthorized response. Vue Router checks authentication and page permissions; the API remains authoritative through Spatie middleware. Roles and permissions use the `sanctum` guard. Seed changes with `php artisan db:seed`.

Company profile values are stored as keyed rows in `settings` and managed through `GET|PUT /api/company-settings` with `settings.manage`. `Setting::company()` provides defaults and is the shared source for all FPDF document branding. Seeder defaults use insert-only behavior so later seeding does not overwrite administrator changes.

## Tests and standards

`tests/Feature/InventoryFlowTest.php` covers core purchasing, sale, rollback, cancellation, costing, profit, and payment behavior with an in-memory database. Run `php artisan test` and `npm run build` before every release. Keep controllers thin, validate all input, keep stock changes in services, use transactions and row locks, and never edit `current_stock` from generic product updates.

To add a module: create migration/model, request, service for business rules, controller, routes, Vue page, and feature tests. To troubleshoot, inspect `storage/logs/laravel.log`, confirm PHP 8.3+, run `php artisan optimize:clear`, check MySQL credentials, confirm `public/build/manifest.json`, and reinstall dependencies from lock files.

# Stock Manager

A single-user procurement, sales, payment, and inventory application. Laravel 13 provides a Sanctum-protected REST API; Vue 3, Vite, Pinia-ready state infrastructure, Axios, and Tailwind CSS provide the responsive interface.

Project behavior and implementation progress are defined in `APPLICATION_FLOWS.md`. Operational instructions are in `USER_MANUAL.md`, and individual development tasks are tracked in `TASKS.md`.

## Features

- Products, suppliers, customers, purchases, sales, payments, and stock ledger
- Transactional inventory updates with row locking and negative-stock prevention
- Weighted-average costing, saved sale cost, gross-profit reporting
- Safe purchase and sale cancellation with reversing movements and payments
- Dashboard, stock/low-stock/purchase/sales/profit reports, search and pagination
- Server-generated FPDF Receipt Vouchers and Payment Vouchers
- Configurable admin seed and walk-in customer

## Installation

Requirements: PHP 8.3+, Composer, Node 20+, npm, and MySQL 8+.

```bash
composer install
copy .env.example .env
php artisan key:generate
```

Create the MySQL database named in `.env`, set its credentials, and set a strong `DEFAULT_USER_PASSWORD`. Then:

```bash
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

For development, run `npm run dev` and `php artisan serve` in separate terminals. Demo records are created only when `SEED_DEMO_DATA=true`.

To load the complete demonstration dataset:

```powershell
$env:SEED_DEMO_DATA='true'
C:\php83\php.exe artisan db:seed
```

The dataset includes eight products, four suppliers, walk-in plus five named customers, five purchases, six sales, partial balances, and stock adjustments. It is safe to rerun: seeded directory records are reused and demonstration transactions are not duplicated.

## Testing

```bash
php artisan test
npm run build
```

Tests use an in-memory SQLite database and never touch the configured MySQL database.

## Production

Set `APP_ENV=production`, `APP_DEBUG=false`, a production `APP_URL`, secure credentials, and an HTTPS-aware Sanctum/session configuration. Run `composer install --no-dev --optimize-autoloader`, `npm ci && npm run build`, `php artisan migrate --force`, and `php artisan optimize`. Point the web server document root at `public/`, schedule backups, and protect `.env`.

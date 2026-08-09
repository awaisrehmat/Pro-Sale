# Project Task List

## Phase 1: Project Setup

- [x] Create Laravel project
  - Laravel 13 installed and verified with PHP 8.3.
- [x] Configure MySQL
  - MySQL defaults and an isolated SQLite test connection are documented.
- [x] Install Vue.js
  - Vue 3, Router, Pinia, Axios, Vite, and Tailwind installed; production build passed.
- [x] Configure Laravel Sanctum
- [x] Configure Spatie roles and permissions
- [x] Create application layout

## Phase 2: Database

- [x] Create users table
- [x] Create products table
- [x] Create suppliers table
- [x] Create customers table
- [x] Create purchases tables
- [x] Create sales tables
- [x] Create stock movements table
- [x] Create stock adjustments table
- [x] Create payments table
- [x] Create settings table
  - All domain migrations pass from an empty in-memory database.

## Phase 3: Backend

- [x] Create models
- [x] Create migrations
- [x] Create form requests
- [x] Create services
  - Transaction services use database transactions, row locks, stock ledger entries, and reversal rules.
- [x] Create controllers
- [x] Create API resources
- [x] Create API routes
- [x] Create seeders
  - Includes an idempotent realistic demo dataset with products, parties, purchases, sales, payments, balances, stock movements, and adjustments.
- [x] Create feature and unit tests
  - 8 tests and 27 assertions pass.

## Phase 4: Frontend

- [x] Create authentication screens
- [x] Create User Administration
  - Administrators can create, edit, assign roles, and activate/deactivate users with API-level permission enforcement.
- [x] Create Company Settings
  - Administrators can edit company identity and contact details used across all FPDF printables.
- [x] Create Product Category Configuration
  - Unified Product Settings lets administrators manage UOMs, categories, and dependent subcategories; products use configured units and safe in-use deletion rules.
- [x] Create dashboard
  - Includes KPI cards, seven-day activity, receivables/payables, low-stock alerts, and recent activity.
- [x] Create product screens
- [x] Create purchase screens
  - Includes complete purchase detail modal and FPDF Purchase Voucher.
- [x] Create sales screens
  - Includes complete sales detail modal and FPDF Sales Invoice.
- [x] Create stock screens
  - Ledger displays product name/SKU and user-facing serial numbers without exposing database IDs.
- [x] Create supplier screens
- [x] Create customer screens
- [x] Create payment screens
  - Includes linked party/transaction detail and printable payment receipt.
  - Supports Receipt Vouchers, Payment Vouchers, outstanding-document allocation, partial installments, and transaction payment history.
  - Receipt and Payment Vouchers are generated as printable FPDF documents.
- [x] Create reports
  - Includes report-specific summaries and filters, status indicators, CSV export, print styling, payment-channel cash flow, receivables, and payables.
- [x] Verify responsive layout
  - Compact desktop layout collapses to a single-column mobile layout.
  - UX pass includes responsive login, paginated lists, formatted values, guided transaction entry, loading states, empty states, success feedback, and keyboard-friendly detail dismissal.

## Phase 5: Documentation

- [x] Create README.md
- [x] Create APPLICATION_FLOWS.md
  - Documents the intended authentication, inventory, purchase, sale, cancellation, payment, reporting, and rollback flows.
- [x] Create USER_MANUAL.md
- [x] Create DEVELOPMENT_GUIDE.md
- [x] Verify the complete system
  - Database migration and seeding passed; 11 tests and 59 assertions passed.

# Stock Manager Application Flows

This document is the implementation reference for what the application is trying to build. Update it whenever a business flow changes.

## Scope

Stock Manager is a single-user system for:

- Products and current inventory
- Suppliers and supplier balances
- Customers and customer balances
- Purchases
- Sales
- Stock adjustments and stock history
- Supplier and customer payments
- Operational and financial reports

The system does not include approvals, roles, quotations, requisitions, multiple warehouses, branches, or companies.

## Core Inventory Flow

```text
Product created
    ↓
Opening stock or purchase recorded
    ↓
Stock increases
    ↓
Sale recorded
    ↓
Stock decreases
    ↓
Adjustment or cancellation, when required
    ↓
Stock ledger and reports remain synchronized
```

Rules:

- `products.current_stock` is a cached balance.
- `stock_movements` is the inventory audit ledger.
- Users cannot directly edit current stock.
- Stock cannot become negative.
- Every stock-changing operation runs in a database transaction.
- Product rows are locked before stock is changed.

## Authentication Flow

```text
Seed default administrator
    ↓
User opens login page
    ↓
Email and password are validated
    ↓
Sanctum token is issued
    ↓
Token protects all business pages and API requests
    ↓
Logout deletes the current token
```

There is no public registration, role management, or permission hierarchy.

## Product Flow

```text
Create product
    ↓
Validate unique SKU and optional barcode
    ↓
Save prices, unit, minimum stock, and active status
    ↓
If opening stock exists, create an opening-stock adjustment
    ↓
Create a stock movement
```

After creation:

- Descriptive and pricing fields can be edited.
- Current stock cannot be edited from the product form.
- Deactivation prevents the product from being used in new transactions.
- Product history shows all related stock movements.

## Purchase Flow

```text
Select supplier and purchase date
    ↓
Add one or more active products
    ↓
Enter quantities, prices, and discounts
    ↓
Calculate subtotal, grand total, paid, and due
    ↓
Start database transaction
    ↓
Lock product rows
    ↓
Create purchase and item records
    ↓
Calculate new weighted-average cost
    ↓
Increase product stock
    ↓
Create purchase stock movements
    ↓
Record initial supplier payment, when applicable
    ↓
Commit transaction
```

Calculations:

```text
Line total = quantity × unit purchase price - item discount
Subtotal = sum of line totals
Grand total = subtotal - header discount + additional cost
Due = grand total - paid amount

New average cost =
((old stock × old average cost) + (purchased quantity × purchase price))
÷ (old stock + purchased quantity)
```

The payment status is unpaid, partial, or paid. A paid amount greater than the grand total is rejected.

## Purchase Cancellation Flow

```text
Request purchase cancellation
    ↓
Lock purchase and product rows
    ↓
Reject if already cancelled
    ↓
Check enough stock remains to reverse every item
    ↓
Decrease stock
    ↓
Create purchase-cancellation movements
    ↓
Restore recorded pre-purchase cost
    ↓
Mark linked payments as reversed
    ↓
Mark purchase as cancelled
```

Completed purchases are never permanently deleted. Cancellation fails completely if any product does not have enough stock to reverse the purchase.

## Sales Flow

```text
Select customer and sale date
    ↓
Add one or more active products
    ↓
Display available stock
    ↓
Enter quantities, prices, discounts, and tax
    ↓
Start database transaction
    ↓
Lock product rows
    ↓
Validate requested stock
    ↓
Create sale and item records
    ↓
Save current average cost on every sale item
    ↓
Decrease product stock
    ↓
Create sale stock movements
    ↓
Record initial customer payment, when applicable
    ↓
Commit transaction
```

Calculations:

```text
Line total = quantity × unit sale price - item discount
Subtotal = sum of line totals
Grand total = subtotal - header discount + tax
Due = grand total - paid amount
Cost of goods sold = quantity × saved unit cost
Gross profit = sales revenue - cost of goods sold
```

The backend rejects insufficient stock even if frontend validation is bypassed.

## Sale Cancellation Flow

```text
Request sale cancellation
    ↓
Lock sale and product rows
    ↓
Reject if already cancelled
    ↓
Return sold quantities to stock
    ↓
Create sale-cancellation movements
    ↓
Mark linked payments as reversed
    ↓
Mark sale as cancelled
```

Completed sales are never permanently deleted.

## Stock Adjustment Flow

```text
Select product
    ↓
Choose increase or decrease
    ↓
Enter quantity, date, and reason
    ↓
Lock product row
    ↓
Reject a decrease that would create negative stock
    ↓
Create adjustment
    ↓
Update cached stock
    ↓
Create matching stock movement
```

## Supplier Payment Flow

```text
Select supplier
    ↓
Calculate outstanding balance
    ↓
Enter payment amount and method
    ↓
Reject amount above outstanding balance
    ↓
Create supplier payment
    ↓
Supplier ledger balance decreases
```

```text
Supplier outstanding =
opening balance + completed purchases - non-reversed payments
```

## Customer Payment Flow

```text
Select customer
    ↓
Calculate outstanding balance
    ↓
Enter payment amount and method
    ↓
Reject amount above outstanding balance
    ↓
Create customer payment
    ↓
Customer ledger balance decreases
```

```text
Customer outstanding =
opening balance + completed sales - non-reversed payments
```

## Reporting Flow

Reports read committed transaction and ledger data:

- Stock report: current stock, average cost, sale price, stock value, and stock status
- Low-stock report: products where current stock is at or below minimum stock
- Purchase report: completed and cancelled purchase records
- Sales report: completed and cancelled sale records
- Profit report: revenue, saved cost of goods sold, and gross profit
- Supplier ledger: opening balance, purchases, payments, and outstanding balance
- Customer ledger: opening balance, sales, payments, and outstanding balance

## Error and Rollback Flow

```text
Validate request
    ↓
Start transaction
    ↓
Lock affected records
    ↓
Perform calculations and writes
    ↓
An error occurs?
    ├── No: commit and return success
    └── Yes: roll back every write and return a safe validation/error response
```

Expected errors include duplicate SKU, invalid quantity, inactive product, insufficient stock, overpayment, missing party, and duplicate cancellation.

## Current Implementation Status

- [x] Laravel and Vue application setup
- [x] Sanctum authentication
- [x] Database schema and seeders
- [x] Product, supplier, and customer APIs
- [x] Purchase creation and cancellation
- [x] Sale creation and cancellation
- [x] Stock ledger and adjustments
- [x] Supplier and customer payments
- [x] Dashboard and reports
- [x] Responsive application shell and polished dashboard
- [x] Filtered reports with summaries, CSV export, and print layout
- [x] Purchase and sale detail windows with printable transaction documents
- [x] Printable supplier and customer payment receipts
- [x] Consistent SVG icon system for navigation and actions
- [x] Realistic transactionally consistent demonstration data
- [x] Automated core-flow tests
- [x] Production frontend build
- [ ] Verification against a configured MySQL server
- [ ] Full browser-based screen walkthrough on the target machine
- [ ] Manual mobile-device review
- [ ] Production deployment verification

## Verification Checklist

Before a release:

- [ ] Run `php artisan test`
- [ ] Run `npm run build`
- [ ] Run migrations against an empty MySQL database
- [ ] Run seeders and verify administrator login
- [ ] Create a product with opening stock
- [ ] Record a multi-item purchase
- [ ] Confirm weighted-average cost
- [ ] Record a valid sale
- [ ] Confirm an insufficient-stock sale is rejected
- [ ] Cancel a sale and confirm stock is returned
- [ ] Cancel a purchase and confirm stock is removed
- [ ] Test supplier and customer overpayment rejection
- [ ] Review stock, low-stock, sales, purchase, and profit reports
- [ ] Test desktop and mobile layouts

## Related Documents

- `TASKS.md` tracks implementation tasks and their completion.
- `USER_MANUAL.md` explains how a non-technical user operates the application.
- `DEVELOPMENT_GUIDE.md` explains architecture and extension points.
- `README.md` explains installation, testing, and deployment.

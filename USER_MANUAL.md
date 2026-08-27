# Stock Manager User Manual

## Working with multiple companies

Use the company selector in the top navigation before recording or reviewing activity. Products, stock, customers, suppliers, purchases, sales, payments, document numbers, company identity, and normal reports belong only to the selected company.

Group administrators create companies under **Group → Companies** and assign users under **User administration → Company access**. Authorized administrators can change **Reporting scope** on Reports from **Selected company** to **Consolidated group**; consolidated rows include their originating company.

## Getting started

1. Open the application and sign in with the administrator email and password configured during installation.
2. Use the left sidebar to open Dashboard, Products, Suppliers, Customers, Purchases, Sales, Stock, Payments, or Reports.
3. Use **Log out** when finished.

## Users, roles, and permissions

Only an Administrator can open **User Administration**. Select **New user**, enter the user's name, unique email, strong password, role, and active status, then save. Use the edit icon to change a user's details or role. Disabling a user revokes their API sessions and prevents future sign-in.

- **Administrator:** complete system access, including user administration.
- **Manager:** complete operational and reporting access, but cannot administer users.
- **Operator:** day-to-day purchasing, sales, payments, stock viewing, and reports; cannot edit master records, adjust stock, or cancel transactions.

The application prevents an administrator from disabling their own account and prevents removal of the final active Administrator. Menu items and server APIs both enforce permissions. After changing your own role permissions during deployment, sign out and sign back in to refresh the browser session.

## Company settings

An Administrator can open **Company Settings** under Administration and edit the company name, logo, tagline, address, phone, email, website, tax/registration number, and currency code. Upload a PNG or JPG logo up to 2 MB; a clear square or landscape image with a white or transparent background prints best. Select **Save company details** to apply text changes. The saved identity, logo, and contact details are automatically used on Purchase Vouchers, Sales Invoices, Receipt Vouchers, and Payment Vouchers.

## Daily setup

Create suppliers and customers before transactions. A seeded **Walk-in Customer** is available for anonymous retail sales. Create each product with a unique SKU, unit, purchase/sale prices, minimum stock, and optional opening stock. Current stock cannot be typed over later.

Administrators can open **Product Settings** under Administration to manage units of measurement, categories, and dependent subcategories. Each UOM has a name, short symbol, decimal precision, and active status. When creating or editing a product, select its UOM and category first, followed by an optional subcategory. Settings already assigned to products cannot be deleted; deactivate them when they should no longer be available for new assignments.

Record lists are paginated. Use **Previous** and **Next** below a table to move between pages. Search waits briefly while you type and then refreshes the results automatically.

## Record a purchase

1. Open Purchases and choose **New purchase**.
2. Select the date, supplier, and payment method.
3. Add products; enter quantity, purchase price, and any line discount.
4. Enter header discount, additional cost, and paid amount.
5. Check subtotal, total, and due, then save.

Saving immediately increases stock and records stock-ledger entries. A partly paid purchase leaves a supplier balance.

The transaction summary remains visible while you add products. Every product row displays its calculated line total, and duplicate products are blocked.

To review or print a purchase, return to the Purchases list and select the eye icon in its Actions column. The detail window includes supplier information, all product lines, totals, payment status, and notes. Select **Open PDF** to generate the official FPDF Purchase Voucher.

## Record a sale

1. Open Sales and choose **New sale**.
2. Select the customer (use Walk-in Customer when appropriate).
3. Add products and enter quantities and prices.
4. Check the available quantity shown beside each product.
5. Enter discount, tax, and paid amount, then save.

The form blocks saving when requested quantity exceeds stock. Saving reduces stock immediately.

Products with insufficient stock are highlighted before submission. The summary continuously shows subtotal, discount, tax, amount paid, and balance due.

To review or print a sales invoice, open the Sales list and select the eye icon. Select **Open PDF** to generate the official FPDF Sales Invoice.

## Stock and adjustments

The Stock page is the audit ledger. It shows a simple serial number, product name, SKU, movement date and type, transaction reference, quantity in/out, and resulting stock. Internal database IDs are never displayed. Opening stock, purchases, sales, cancellations, and adjustments each produce a movement. For a correction, submit an increase or decrease adjustment with a date and reason. A decrease cannot take stock below zero.

## Payments and balances

Document numbers use a compact type, month, year, and running sequence. For example, `PV_A25-001` is the first Payment Voucher created in January 2025. Month letters run from `A` for January through `L` for December. Prefixes are `PV` for Payment Voucher, `RV` for Receipt Voucher, `SI` for Sales Invoice, and `PO` for Purchase Order/Voucher. Each document type has its own monthly sequence.

The voucher direction follows normal accounting language:

- **Money in — Receipt Voucher:** money received from a customer.
- **Money out — Payment Voucher:** money paid to a supplier.

To record an installment:

1. Open Payments and select **New record**.
2. Choose the voucher direction.
3. Select the supplier or customer.
4. Select an outstanding purchase or sale from **Apply to transaction**.
5. Enter the installment amount, method, date, and optional reference.
6. Save the voucher.

The purchase or sale immediately updates its paid amount, due amount, and payment status. Repeat these steps for later installments. Open the transaction with its eye icon to see the complete chronological payment history.

Select **General / opening balance** only when the payment is not for a particular purchase or sale. The application rejects an amount above either the selected document balance or the party’s overall outstanding balance. Party ledgers show opening balance, transactions, vouchers, and remaining balance.

Use the eye icon beside a payment to open its details. Select **Open PDF** to generate the official FPDF document:

- Customer payments generate a **Receipt Voucher**.
- Supplier payments generate a **Payment Voucher**.

The PDF shows the party, amount in figures and words, payment method, reference, linked transaction, narration, voucher date, reversal status, and signature spaces. Use the browser PDF viewer to print or download it.

## Reports

Reports include Financial Position, Product Stock Ledger, Stock, Low Stock, Purchases, Sales, and Profit. Financial Position separates money received and paid by Cash, Bank Transfer, Card, and Other; it also shows net channel movement, customer receivables, supplier payables, and the largest outstanding party balances. Its date range controls payment movement, while receivables and payables are calculated as of the selected ending date.

Use **Product Stock Ledger** to audit when inventory entered or left the business. Select a product and date range to see opening stock, total quantity in, total quantity out, net change, closing stock, movement reason, source purchase/sale/adjustment number, stock before and after, unit cost, and notes. Leaving Product blank shows movements for all products; select one product when you need a meaningful single-unit opening and closing balance.

1. Open **Reports** from the sidebar.
2. Select a report tab.
3. Choose the date range and any supplier, customer, product, payment, or stock-status filters available for that report.
4. Select **Apply filters**.
5. Review the summary cards above the detailed records.
6. Select **Export CSV** for spreadsheet analysis or **Print** for a clean printable copy.

Stock value is current quantity multiplied by average cost. Profit uses the unit cost saved when each sale occurred. Gross margin is gross profit divided by revenue.

## Cancellations

Completed transactions are never deleted. Cancel a sale to return its quantities and reverse its linked payment. Cancel a purchase only when enough stock remains to remove the purchased quantities; otherwise sell-side activity must be resolved first. A transaction cannot be cancelled twice.

## Common messages

- **SKU has already been taken:** choose a unique SKU.
- **Only N units are available:** reduce the sale quantity.
- **Paid amount cannot exceed grand total:** lower the initial payment.
- **Payment cannot exceed outstanding amount:** enter no more than the displayed balance.
- **Validation failed:** review the highlighted required or invalid values.

## Backups

Back up the MySQL database daily and keep encrypted copies away from the application server. Test restoration regularly and back up `.env` securely because it contains deployment configuration.

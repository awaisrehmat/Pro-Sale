# Stock Manager User Manual

## Getting started

1. Open the application and sign in with the administrator email and password configured during installation.
2. Use the left sidebar to open Dashboard, Products, Suppliers, Customers, Purchases, Sales, Stock, Payments, or Reports.
3. Use **Log out** when finished.

## Daily setup

Create suppliers and customers before transactions. A seeded **Walk-in Customer** is available for anonymous retail sales. Create each product with a unique SKU, unit, purchase/sale prices, minimum stock, and optional opening stock. Current stock cannot be typed over later.

Record lists are paginated. Use **Previous** and **Next** below a table to move between pages. Search waits briefly while you type and then refreshes the results automatically.

## Record a purchase

1. Open Purchases and choose **New purchase**.
2. Select the date, supplier, and payment method.
3. Add products; enter quantity, purchase price, and any line discount.
4. Enter header discount, additional cost, and paid amount.
5. Check subtotal, total, and due, then save.

Saving immediately increases stock and records stock-ledger entries. A partly paid purchase leaves a supplier balance.

The transaction summary remains visible while you add products. Every product row displays its calculated line total, and duplicate products are blocked.

To review or print a purchase, return to the Purchases list and select the eye icon in its Actions column. The detail window includes supplier information, all product lines, totals, payment status, and notes. Select **Print** for a clean purchase document.

## Record a sale

1. Open Sales and choose **New sale**.
2. Select the customer (use Walk-in Customer when appropriate).
3. Add products and enter quantities and prices.
4. Check the available quantity shown beside each product.
5. Enter discount, tax, and paid amount, then save.

The form blocks saving when requested quantity exceeds stock. Saving reduces stock immediately.

Products with insufficient stock are highlighted before submission. The summary continuously shows subtotal, discount, tax, amount paid, and balance due.

To review or print a sales invoice, open the Sales list and select the eye icon. Select **Print** from the invoice detail window.

## Stock and adjustments

The Stock page is the audit ledger. It shows a simple serial number, product name, SKU, movement date and type, transaction reference, quantity in/out, and resulting stock. Internal database IDs are never displayed. Opening stock, purchases, sales, cancellations, and adjustments each produce a movement. For a correction, submit an increase or decrease adjustment with a date and reason. A decrease cannot take stock below zero.

## Payments and balances

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

Reports include Stock, Low Stock, Purchases, Sales, and Profit.

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

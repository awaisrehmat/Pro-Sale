# Multi-company tenancy

## Structure

The application uses one database with strict row-level company ownership. A main group contains multiple companies; each company owns its products, parties, transactions, stock, settings, and document sequences.

Every operational and configuration row carries `company_id`. The `BelongsToCompany` model concern applies an automatic global query scope and stamps new records with the active company. Without a company context, tenant models return no rows.

Financial years also belong to a company. After company resolution, `ResolveFinancialYear` selects that company's active session. Purchases, sales, expenses, payments, stock movements, and stock adjustments use `BelongsToFinancialYear`, which stamps `financial_year_id` on creation. Their normal operational queries are restricted to the current session; historical access is explicit through Reports.

## Request context and access

Authenticated frontend requests send `X-Company-ID`. `ResolveCompany` verifies the signed-in user's membership before setting `CompanyContext`. Route-model binding runs after company resolution, so another company's record resolves as not found.

- A user belongs to one main group.
- A user can be assigned to multiple companies through `company_user`.
- The top navigation company switcher changes the active company.
- Group administrators can create companies and assign user access.
- Roles determine features; membership determines accessible data.

Products, product and expense categories, subcategories, UOMs, suppliers, customers, purchases and items, sales and items, expenses, stock movements and adjustments, payments, settings, logos, and document sequences are company-owned. SKU, barcode, document number, category, UOM, setting key, and sequence uniqueness is company-specific.

## Consolidated reports

Users with `reports.consolidated` and group-administrator status can select **Consolidated group** on Reports. The server executes each report independently in every active company context, labels rows with the company name, and combines numeric summaries. Operational screens never enter consolidated scope.

The report request sends a financial-year name such as `2026-2027`. Consolidation resolves that year separately inside every company context so identically named sessions can be combined without crossing tenant boundaries. Each company is closed independently and owns its own party, stock, and account opening snapshots.

## Adding a company

Group → Companies creates a company, assigns the creating administrator, and seeds its walk-in customer, settings, common UOMs, categories, and subcategories. Assign other users from User Administration.

## Security invariants

- Do not use raw unscoped tenant-table queries in request code.
- Validate tenant foreign IDs with `TenantRule::exists`.
- Consolidation requires group-admin status and `reports.consolidated`.
- Add isolation tests for every new tenant-owned feature.
- Never reuse a financial-year ID across companies; resolve it again after each company-context switch.

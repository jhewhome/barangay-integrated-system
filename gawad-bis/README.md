# Project Gawad

# Medicine Inventory Module Scope

This module extends the Barangay Information System and is scoped ONLY to medicine inventory operations. All features—stock, reports, and role-based access—are contained within the Medicine Inventory module context.

This module streamlines BHO operations by managing medicines, stock movements, and financial reports:

- Medicines: Stores name, category, unit, price, minimum stock, and notes. Stocks are tracked by quantity, unit price (cost), lot number, and expiry date.
- Stock Management: Supports stock-in, dispense/stock-out, adjustments, discards; maintains a complete `MedicineTransaction` history.
- Financial Reports: Provides Spending Log (purchases), Usage Value (dispensed), Balance Summary, and CSV export.
- Security (module-scoped): Role-based authorization for Medicine Inventory (e.g., Barangay Personnel, Admin); all writes are audited with created/modified user and timestamps.

## Impact for Barangay Health Office

The new system provides the Barangay Health Office with an efficient and convenient system for inventory of medical supplies. It generates accurate reports and facilitates transactions quickly. By improving efficiency and convenience, the system helps enhance the healthcare services of the Barangay Health Office.

## Objectives (Brgy. Balong Bato - BHO)

The system aims to implement an online inventory and monitoring system for the medical supplies of the Barangay Health Office (BHO) of Brgy. Balong Bato. Specifically, it provides the BHO with a system that allows users to perform supply‑related transactions easily and speedily, while ensuring accurate tracking of inventory records.

Reports and pages include low stock, expiring/expired items, stock status and usage reports, and financial reports (spending log, usage value, balance summary) with CSV export.

# Financial Reports (Module-Scoped)

Purpose: Provide barangay personnel with accurate, date‑range reports based on recorded stock and dispense transactions.

- Spending Log
  - Derived from stock intake (Add Stock): quantity × unit cost, with supplier/lot/expiry.
  - CSV export.

- Usage Value
  - Derived from dispensed transactions: quantity × unit price (or medicine price fallback).
  - CSV export.

- Balance Summary
  - Total Purchases − Usage Value for a given date range; net balance.
  - CSV export.

# Inventory Subsystem (Module-Scoped)

Purpose: Keep track of medicines in stock, support stock intake, and proactively alert for expiring and low-stock medicines.

- Stock Intake (Add Stock)
  - Add new batches with quantity, unit cost (optional), lot/batch number, and expiry date.
  - Automatically records a StockIn `MedicineTransaction` per batch.

- Stock Monitoring
  - Low Stock: Flags items with total stock ≤ minimum stock level; dedicated Low Stock page.
  - Expiring Soon: Flags batches expiring within a configurable window (default 30 days).
  - Expired: Lists already expired batches for discard/adjustment.

- Pages/Reports
  - Medicine List, Stocks (batches) per medicine
  - Low Stock, Expiring Soon, Expired
  - Stock Status Report (current stock vs minimum, status), Usage Report (dispensed by range)

- Rules & Audit
  - All stock movements create `MedicineTransaction` records (StockIn/Dispensed/Adjustment/Discard).
  - Created/modified user and timestamps are captured for audit.

Note: POS cash sessions and shift management are intentionally excluded for the barangay module. Financials are reported from stock and dispense logs only.

# Tools
- Visual Studio Community / Visual Code
  -if VS Code install necessary extension
- MongoDb Community
- Github desktop

### Application - business rules, validations, process logic
- Profiles
  - Mapping
- Service - insert and update operation
- Providers - read operations
- Validations - business rule
### Client - main web app
- HostedServices
### Core - common classes or interface, abstract classes
### Data - database connector or db context
- GawadIdentityMongoDbContext - for usermanagement and identity
- GawadMongoDbContext
Domain - contains entities and value object
- Entities - representation of table/collection
- ValueObject - DTOs
### Infrastructure - external services (webapis, etc.) and database calls

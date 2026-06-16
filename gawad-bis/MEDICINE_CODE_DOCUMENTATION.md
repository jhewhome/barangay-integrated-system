# Medicine Module - Complete Code Documentation

This document provides a comprehensive list and description of all medicine-related code files in the Project Gawad application.

## Table of Contents
1. [Domain Layer](#domain-layer)
2. [Application Layer](#application-layer)
3. [Infrastructure Layer](#infrastructure-layer)
4. [Client Layer](#client-layer)
5. [Core Interfaces](#core-interfaces)

---

## Domain Layer

### Entities (`src/Project.Gawad.Domain/Entities/`)

#### `Medicine.cs`
**Purpose**: Core entity representing a medicine in the system.
**Key Properties**:
- `Name` - Medicine name (required)
- `Description` - Optional description
- `Category` - Medicine category (enum)
- `UnitOfMeasure` - Unit type (Box, Bottle, etc.)
- `UnitPrice` - Price per unit
- `MinimumStockLevel` - Threshold for low stock alerts
- `IsActive` - Active/inactive status
- `IsPrescriptionRequired` - Prescription requirement flag
- `IsLimitedSupply` - Limited supply flag for rationing
- `AllocationPeriod` - Period for allocation limits
- `MaxQuantityPerPeriod` - Maximum quantity per allocation period
- `BottleMeasurementType` - Measurement type for bottles (mg, ml)
- `BottleMeasurementValue` - Measurement value for bottles
- `BoxContentType` - Content type for boxes (tablet, capsule, etc.)
- `BoxContentValue` - Number of pieces per box

**Use**: Stored in MongoDB as the primary medicine record.

---

#### `MedicineStock.cs`
**Purpose**: Entity representing a stock batch/lot of a medicine.
**Key Properties**:
- `MedicineId` - Reference to Medicine entity
- `Quantity` - Stock quantity
- `ExpiryDate` - Expiration date
- `BatchNumber` - Batch identifier
- `LotNumber` - Lot identifier
- `CostPerUnit` - Cost per unit for this batch
- `Supplier` - Supplier name
- `ReceivedDate` - Date stock was received
- `ManufacturingDate` - Manufacturing date
- `Location` - Storage location
- `Notes` - Additional notes
- `NotificationDate` - Expiry notification tracking
- `NotifiedById` - User who was notified
- `ActionTaken` - Action taken for expired/expiring stock
- `ActionDate` - Date action was taken
- `ActionTakenById` - User who took action
- `ActionNotes` - Notes about the action

**Use**: Tracks individual stock batches with expiry dates, costs, and supplier information.

---

#### `MedicineTransaction.cs`
**Purpose**: Entity representing a transaction (stock-in, dispense, etc.) for medicines.
**Key Properties**:
- `MedicineId` - Reference to Medicine entity
- `MedicineStockId` - Reference to MedicineStock (for stock-in transactions)
- `TransactionType` - Type of transaction (StockIn, Dispensed, Expired, etc.)
- `Quantity` - Transaction quantity
- `TotalAmount` - Total cost/value of transaction
- `TransactionDate` - Date of transaction
- `Notes` - Transaction notes
- `CreatedById` - User who created the transaction
- `UpdatedById` - User who last updated the transaction

**Use**: Records all medicine movements (additions, dispenses, adjustments, etc.).

---

#### `MedicineAuditLog.cs`
**Purpose**: Entity for auditing medicine-related actions.
**Key Properties**:
- `Action` - Action performed (Create, Update, Delete, etc.)
- `EntityType` - Type of entity (Medicine, MedicineStock, etc.)
- `EntityId` - ID of the entity
- `OldValues` - Previous values (JSON)
- `NewValues` - New values (JSON)
- `UserId` - User who performed the action
- `IpAddress` - IP address of the user
- `UserAgent` - User agent string
- `Timestamp` - When the action occurred

**Use**: Tracks all changes to medicine data for audit purposes.

---

### Domain Objects (`src/Project.Gawad.Domain/Objects/Medicine/`)

#### `CreateMedicineObject.cs`
**Purpose**: DTO for creating a new medicine.
**Use**: Used in create medicine form/view model.
**Properties**: All medicine properties with validation attributes.

---

#### `UpdateMedicineObject.cs`
**Purpose**: DTO for updating an existing medicine.
**Use**: Used in edit medicine form/view model.
**Properties**: Medicine ID and updatable properties.

---

#### `MedicineListObject.cs`
**Purpose**: DTO for displaying medicines in list view.
**Key Properties**:
- `Id` - Medicine ID
- `Name` - Medicine name
- `Category` - Category name (string)
- `UnitOfMeasure` - Unit type (string)
- `CurrentStock` - Current stock quantity
- `MinimumStockLevel` - Minimum stock threshold
- `IsLowStock` - Low stock flag
- `IsActive` - Active status
- `Supplier` - Supplier name
- `TotalDispensed` - Total quantity dispensed
- `UnitPrice` - Unit price
- `ExpiringSoonCount` - Count of batches expiring within 30 days
- `IsOutOfStock` - Computed property (CurrentStock <= 0)

**Use**: Used in medicine list/index page.

---

#### `MedicineDetailObject.cs`
**Purpose**: DTO for displaying detailed medicine information.
**Use**: Used in medicine details page.
**Properties**: All medicine properties plus computed values like `TotalStock`, `ExpiringSoonCount`, `ExpiredCount`, `IsLowStock`.

---

#### `CreateMedicineStockObject.cs`
**Purpose**: DTO for adding stock to a medicine.
**Key Properties**:
- `MedicineId` - Medicine to add stock to
- `Quantity` - Quantity to add
- `ExpiryDate` - Expiration date
- `BatchNumber` - Batch number
- `LotNumber` - Lot number
- `CostPerUnit` - Cost per unit
- `Supplier` - Supplier name
- `ReceivedDate` - Date received
- `ManufacturingDate` - Manufacturing date
- `Location` - Storage location
- `Notes` - Additional notes

**Use**: Used in add stock form/view model.

---

#### `MedicineStockListObject.cs`
**Purpose**: DTO for displaying stock batches in list view.
**Use**: Used in stock list/management pages.
**Properties**: Stock properties plus related medicine information.

---

#### `CreateMedicineTransactionObject.cs`
**Purpose**: DTO for creating a medicine transaction (dispense, stock-in, etc.).
**Key Properties**:
- `MedicineId` - Medicine ID
- `MedicineStockId` - Stock batch ID (for stock-in)
- `TransactionType` - Type of transaction
- `Quantity` - Transaction quantity
- `TotalAmount` - Total cost/value
- `TransactionDate` - Transaction date
- `Notes` - Transaction notes

**Use**: Used in dispense and transaction forms.

---

#### `UpdateMedicineTransactionObject.cs`
**Purpose**: DTO for updating a medicine transaction.
**Use**: Used in edit transaction form.

---

#### `MedicineTransactionListObject.cs`
**Purpose**: DTO for displaying transactions in list view.
**Use**: Used in transaction history pages.
**Properties**: Transaction properties plus related medicine and user information.

---

#### `MedicineSpendingLogObject.cs`
**Purpose**: DTO for spending log report.
**Key Properties**:
- `StockId` - Stock batch ID
- `MedicineName` - Medicine name
- `Supplier` - Supplier name
- `BatchNumber` - Batch number
- `LotNumber` - Lot number
- `Quantity` - Quantity received
- `UnitCost` - Cost per unit
- `TotalCost` - Total cost
- `ReceivedDate` - Date received
- `ExpiryDate` - Expiration date

**Use**: Used in spending log report page.

---

#### `MedicineBalanceSummaryObject.cs`
**Purpose**: DTO for medicine balance summary statistics.
**Key Properties**:
- `StartDate` - Report start date
- `EndDate` - Report end date
- `TotalPurchases` - Total cost of purchases (stock-in)
- `TotalUsageValue` - Total cost of dispensed medicines
- `TotalDispensedCount` - Total quantity dispensed (pieces)
- `TotalRemainingStock` - Total remaining stock (pieces)
- `TotalStockReceivedCount` - Total quantity received (pieces)
- `Net` - Computed property (TotalPurchases - TotalUsageValue)

**Use**: Used in balance summary reports and dashboard statistics.

---

#### `MedicineUsageReportObject.cs`
**Purpose**: DTO for medicine usage report.
**Use**: Used in usage report generation.

---

#### `MedicineStockStatusReportObject.cs`
**Purpose**: DTO for stock status report.
**Use**: Used in stock status report generation.

---

#### `ReportFilterObject.cs`
**Purpose**: DTO for filtering reports by date range.
**Key Properties**:
- `StartDate` - Report start date
- `EndDate` - Report end date

**Use**: Used in all report pages (spending log, usage report, etc.).

---

#### `AuditLogFilterObject.cs`
**Purpose**: DTO for filtering audit logs.
**Use**: Used in audit log page.

---

#### `AuditLogListObject.cs`
**Purpose**: DTO for displaying audit log entries.
**Use**: Used in audit log list view.

---

#### `RecordStockActionObject.cs`
**Purpose**: DTO for recording actions on stock (expired, damaged, etc.).
**Use**: Used in stock action forms.

---

### Enums (`src/Project.Gawad.Domain/Enums/Medicine/`)

#### `MedicineCategory.cs`
**Purpose**: Enumeration of medicine categories.
**Values**:
- `Antibacterial = 1`
- `Maintenance = 2`
- `Antacid = 3`
- `AnalgesicsAntipyretics = 4`
- `AntihistamineAntiasthmaticsAntiCough = 5`
- `Micronutrients = 6`
- `Others = 99`

**Use**: Categorizes medicines for organization and filtering.

---

#### `MedicineTransactionType.cs`
**Purpose**: Enumeration of transaction types.
**Values**:
- `StockIn = 1` - Stock added
- `StockOut = 2` - Stock removed
- `Dispensed = 3` - Medicine dispensed to patient
- `Expired = 4` - Stock expired
- `Damaged = 5` - Stock damaged
- `Returned = 6` - Stock returned
- `Adjusted = 7` - Stock adjusted

**Use**: Classifies different types of medicine transactions.

---

#### `UnitOfMeasure.cs` (referenced)
**Purpose**: Enumeration of unit types.
**Values**: Box, Bottle, Piece, etc.

**Use**: Defines how medicines are measured.

---

#### `DosageType.cs` (referenced)
**Purpose**: Enumeration of dosage types.
**Use**: Defines dosage administration types.

---

#### `AllocationPeriod.cs` (referenced)
**Purpose**: Enumeration of allocation periods for limited supply medicines.
**Use**: Defines time periods for allocation limits (Daily, Weekly, Monthly).

---

## Application Layer

### Services (`src/Project.Gawad.Application/Services/`)

#### `MedicineService.cs`
**Purpose**: Business logic service for medicine operations.
**Key Methods**:
- `CreateMedicine()` - Creates a new medicine
- `UpdateMedicine()` - Updates an existing medicine
- `RemoveMedicine()` - Soft deletes a medicine
- `AddStock()` - Adds stock to a medicine
- `CreateTransaction()` - Creates a medicine transaction
- `UpdateTransaction()` - Updates a transaction
- `DeleteTransaction()` - Deletes a transaction
- `DeleteStock()` - Deletes a stock batch

**Use**: Handles all medicine business logic and validation.

---

#### `MedicineAuditLogService.cs`
**Purpose**: Service for managing medicine audit logs.
**Key Methods**:
- `LogActionAsync()` - Logs an action to audit log
- `GetAuditLogsAsync()` - Retrieves audit log entries

**Use**: Tracks all medicine-related actions for auditing.

---

### Providers (`src/Project.Gawad.Application/Providers/`)

#### `MedicineProvider.cs`
**Purpose**: Data provider for medicine-related queries and operations.
**Key Methods**:
- `GetMedicinesListAsync()` - Gets paginated medicine list with filtering
- `GetMedicineDetailObjectAsync()` - Gets detailed medicine information
- `GetCreateUpdateMedicineObjectAsync()` - Gets medicine for editing
- `GetSpendingLogAsync()` - Gets spending log report data
- `GetBalanceSummaryAsync()` - Gets balance summary statistics
- `GetUsageReportAsync()` - Gets usage report data
- `GetStockStatusReportAsync()` - Gets stock status report data
- `GetExpiringStocksAsync()` - Gets stocks expiring soon
- `GetExpiredStocksAsync()` - Gets expired stocks
- `GetLowStockMedicinesAsync()` - Gets medicines with low stock
- `GetMedicineTransactionsAsync()` - Gets medicine transactions
- `GetMedicineStocksAsync()` - Gets medicine stock batches

**Use**: Provides data access and business logic for medicine operations.

---

### Profiles/Mappings (`src/Project.Gawad.Application/Profiles/Medicines/`)

#### `CreateMedicineObjectToMedicineMapping.cs`
**Purpose**: AutoMapper profile for mapping CreateMedicineObject to Medicine entity.
**Use**: Converts DTO to entity when creating medicines.

---

#### `MedicineToUpdateMedicineObjectMapping.cs`
**Purpose**: AutoMapper profile for mapping Medicine entity to UpdateMedicineObject.
**Use**: Converts entity to DTO when editing medicines.

---

#### `MedicineToMedicineListObjectMapping.cs`
**Purpose**: AutoMapper profile for mapping Medicine entity to MedicineListObject.
**Use**: Converts entity to list DTO for display.

---

#### `MedicineToMedicineDetailObjectMapping.cs`
**Purpose**: AutoMapper profile for mapping Medicine entity to MedicineDetailObject.
**Use**: Converts entity to detail DTO for display.

---

#### `CreateMedicineStockObjectToMedicineStockMapping.cs`
**Purpose**: AutoMapper profile for mapping CreateMedicineStockObject to MedicineStock entity.
**Use**: Converts DTO to entity when adding stock.

---

#### `MedicineStockToMedicineStockListObjectMapping.cs`
**Purpose**: AutoMapper profile for mapping MedicineStock entity to MedicineStockListObject.
**Use**: Converts entity to list DTO for display.

---

#### `CreateMedicineTransactionObjectToMedicineTransactionMapping.cs`
**Purpose**: AutoMapper profile for mapping CreateMedicineTransactionObject to MedicineTransaction entity.
**Use**: Converts DTO to entity when creating transactions.

---

#### `MedicineTransactionToMedicineTransactionListObjectMapping.cs`
**Purpose**: AutoMapper profile for mapping MedicineTransaction entity to MedicineTransactionListObject.
**Use**: Converts entity to list DTO for display.

---

#### `MedicineAuditLogToAuditLogListObjectMapping.cs`
**Purpose**: AutoMapper profile for mapping MedicineAuditLog entity to AuditLogListObject.
**Use**: Converts entity to list DTO for display.

---

### Validations (`src/Project.Gawad.Application/Validations/Medicine/`)

#### `CreateMedicineValidation.cs`
**Purpose**: FluentValidation rules for CreateMedicineObject.
**Use**: Validates medicine creation data.

---

#### `CreateMedicineStockValidation.cs`
**Purpose**: FluentValidation rules for CreateMedicineStockObject.
**Use**: Validates stock addition data.

---

#### `CreateMedicineTransactionValidation.cs`
**Purpose**: FluentValidation rules for CreateMedicineTransactionObject.
**Use**: Validates transaction creation data.

---

## Infrastructure Layer

### Repositories (`src/Project.Gawad.Infrastructure/Repositories/`)

#### `MedicineRepository.cs`
**Purpose**: MongoDB repository for Medicine entity.
**Key Methods**:
- `GetAllAsync()` - Gets all medicines
- `GetByIdAsync()` - Gets medicine by ID
- `CreateAsync()` - Creates a new medicine
- `UpdateAsync()` - Updates a medicine
- `DeleteAsync()` - Soft deletes a medicine
- `GetMedicinesByCategoryAsync()` - Gets medicines by category
- `GetActiveMedicinesAsync()` - Gets active medicines

**Use**: Data access layer for Medicine entity.

---

#### `MedicineStockRepository.cs`
**Purpose**: MongoDB repository for MedicineStock entity.
**Key Methods**:
- `GetAllAsync()` - Gets all stock batches
- `GetByIdAsync()` - Gets stock by ID
- `GetByMedicineIdAsync()` - Gets stocks for a medicine
- `GetTotalStockByMedicineIdAsync()` - Gets total stock quantity for a medicine
- `CreateAsync()` - Creates a new stock batch
- `UpdateAsync()` - Updates a stock batch
- `DeleteAsync()` - Soft deletes a stock batch
- `GetExpiringStocksAsync()` - Gets stocks expiring within date range
- `GetExpiredStocksAsync()` - Gets expired stocks

**Use**: Data access layer for MedicineStock entity.

---

#### `MedicineTransactionRepository.cs`
**Purpose**: MongoDB repository for MedicineTransaction entity.
**Key Methods**:
- `GetAllAsync()` - Gets all transactions
- `GetByIdAsync()` - Gets transaction by ID
- `GetByMedicineIdAsync()` - Gets transactions for a medicine
- `GetTransactionsByDateRangeAsync()` - Gets transactions within date range
- `GetTransactionsByTypeAsync()` - Gets transactions by type
- `CreateAsync()` - Creates a new transaction
- `UpdateAsync()` - Updates a transaction
- `DeleteAsync()` - Soft deletes a transaction

**Use**: Data access layer for MedicineTransaction entity.

---

#### `MedicineAuditLogRepository.cs`
**Purpose**: MongoDB repository for MedicineAuditLog entity.
**Key Methods**:
- `GetAllAsync()` - Gets all audit logs
- `GetByEntityIdAsync()` - Gets audit logs for an entity
- `CreateAsync()` - Creates a new audit log entry
- `GetAuditLogsByFilterAsync()` - Gets audit logs with filtering

**Use**: Data access layer for MedicineAuditLog entity.

---

## Client Layer

### Controllers (`src/Project.Gawad.Client/Controllers/`)

#### `MedicinesController.cs`
**Purpose**: MVC controller for medicine-related actions.
**Key Actions**:
- `Index()` - Medicine list page
- `Create()` - Create medicine page (GET/POST)
- `Edit()` - Edit medicine page (GET/POST)
- `Details()` - Medicine details page
- `Delete()` - Delete medicine (AJAX)
- `AddStock()` - Add stock page (GET/POST)
- `Stocks()` - Stock list page
- `DeleteStock()` - Delete stock (AJAX)
- `Dispense()` - Dispense medicine page (GET/POST)
- `Transactions()` - Transaction list page
- `EditTransaction()` - Edit transaction page (GET/POST)
- `DeleteTransaction()` - Delete transaction (AJAX)
- `SpendingLog()` - Spending log report page
- `ExportSpendingLog()` - Export spending log to CSV
- `UsageReport()` - Usage report page
- `ExportUsageReport()` - Export usage report to CSV
- `BalanceSummary()` - Balance summary page
- `StockStatusReport()` - Stock status report page
- `ExportStockStatusReport()` - Export stock status report to CSV
- `LowStock()` - Low stock medicines page
- `ExpiringStocks()` - Expiring stocks page
- `ExpiredStocks()` - Expired stocks page
- `AuditLog()` - Audit log page
- `Receipt()` - Dispense receipt page
- `ReceiptPdf()` - Dispense receipt PDF page
- `GetMedicinesList()` - AJAX endpoint for medicine list (Select2)
- `GetMedicineDetailsJson()` - AJAX endpoint for medicine details

**Use**: Handles all HTTP requests for medicine-related pages and actions.

---

### Views (`src/Project.Gawad.Client/Views/Medicines/`)

#### `Index.cshtml`
**Purpose**: Medicine list/index page.
**Features**:
- Displays paginated list of medicines
- Search by medicine name
- Filter by category, unit type, status
- Shows current stock, minimum stock level, total dispensed
- Low stock and expiring soon indicators
- Actions: View Details, Edit, Add Stock, Dispense, Transactions, Delete

**Use**: Main medicine management page.

---

#### `Create.cshtml`
**Purpose**: Create new medicine form.
**Use**: Form for creating a new medicine.

---

#### `Edit.cshtml`
**Purpose**: Edit medicine form.
**Use**: Form for editing an existing medicine.

---

#### `Details.cshtml`
**Purpose**: Medicine details page.
**Features**:
- Displays all medicine information
- Shows current stock, expiring soon count, expired count
- Lists stock batches
- Lists transaction history
- Actions: Edit, Add Stock, Dispense, Delete

**Use**: Detailed view of a medicine.

---

#### `AddStock.cshtml`
**Purpose**: Add stock form.
**Use**: Form for adding stock to a medicine.

---

#### `Stocks.cshtml`
**Purpose**: Stock list page.
**Use**: Displays all stock batches for a medicine.

---

#### `Dispense.cshtml`
**Purpose**: Dispense medicine form.
**Use**: Form for dispensing medicine to patients.

---

#### `Transactions.cshtml`
**Purpose**: Transaction list page.
**Use**: Displays transaction history for a medicine.

---

#### `EditTransaction.cshtml`
**Purpose**: Edit transaction form.
**Use**: Form for editing a transaction.

---

#### `SpendingLog.cshtml`
**Purpose**: Spending log report page.
**Features**:
- Date range filter
- Displays stock received with costs
- Export to CSV functionality
- Total row (currently hidden)

**Use**: Report showing stock purchases and costs.

---

#### `UsageReport.cshtml`
**Purpose**: Medicine usage report page.
**Use**: Report showing medicine usage statistics.

---

#### `BalanceSummary.cshtml`
**Purpose**: Balance summary report page.
**Use**: Report showing medicine balance statistics.

---

#### `StockStatusReport.cshtml`
**Purpose**: Stock status report page.
**Use**: Report showing stock status information.

---

#### `LowStock.cshtml`
**Purpose**: Low stock medicines page.
**Use**: Lists medicines with low stock levels.

---

#### `ExpiringStocks.cshtml`
**Purpose**: Expiring stocks page.
**Use**: Lists stocks expiring within 30 days.

---

#### `ExpiredStocks.cshtml`
**Purpose**: Expired stocks page.
**Use**: Lists expired stocks.

---

#### `AuditLog.cshtml`
**Purpose**: Audit log page.
**Use**: Displays audit log entries for medicines.

---

#### `Receipt.cshtml`
**Purpose**: Dispense receipt page (HTML).
**Use**: Displays receipt after dispensing medicine.

---

#### `ReceiptPdf.cshtml`
**Purpose**: Dispense receipt PDF page.
**Use**: Generates PDF receipt for dispensed medicine.

---

#### `MedicineReport.cshtml`
**Purpose**: Medicine report template.
**Use**: Template for generating medicine reports.

---

## Core Interfaces

### Service Interfaces (`src/Project.Gawad.Core/Services/`)

#### `IMedicineService.cs`
**Purpose**: Interface for MedicineService.
**Use**: Defines contract for medicine business logic operations.

---

#### `IMedicineAuditLogService.cs`
**Purpose**: Interface for MedicineAuditLogService.
**Use**: Defines contract for audit log operations.

---

### Repository Interfaces (`src/Project.Gawad.Core/Repositories/`)

#### `IMedicineRepository.cs`
**Purpose**: Interface for MedicineRepository.
**Use**: Defines contract for medicine data access operations.

---

#### `IMedicineStockRepository.cs`
**Purpose**: Interface for MedicineStockRepository.
**Use**: Defines contract for medicine stock data access operations.

---

#### `IMedicineTransactionRepository.cs`
**Purpose**: Interface for MedicineTransactionRepository.
**Use**: Defines contract for medicine transaction data access operations.

---

#### `IMedicineAuditLogRepository.cs`
**Purpose**: Interface for MedicineAuditLogRepository.
**Use**: Defines contract for audit log data access operations.

---

### Provider Interfaces (`src/Project.Gawad.Core/Providers/`)

#### `IMedicineProvider.cs`
**Purpose**: Interface for MedicineProvider.
**Use**: Defines contract for medicine data provider operations.

---

## Summary

The medicine module consists of:

- **4 Entities**: Medicine, MedicineStock, MedicineTransaction, MedicineAuditLog
- **17 Domain Objects**: DTOs for various operations and reports
- **4 Enums**: MedicineCategory, MedicineTransactionType, UnitOfMeasure, DosageType, AllocationPeriod
- **2 Services**: MedicineService, MedicineAuditLogService
- **1 Provider**: MedicineProvider
- **9 AutoMapper Profiles**: For entity-to-DTO mappings
- **3 Validators**: For data validation
- **4 Repositories**: For data access
- **1 Controller**: MedicinesController with 20+ actions
- **20 Views**: Razor pages for UI
- **5 Core Interfaces**: Service, repository, and provider interfaces

**Total Files**: ~46 C# files + 20 Razor views = 66+ files

---

## Notes

- **No MedicineCode Property**: Medicines are identified by their MongoDB ObjectId, not a separate code field.
- **Soft Delete**: All entities use soft delete (IsDeleted flag) rather than hard deletion.
- **Audit Logging**: All medicine operations are logged for audit purposes.
- **Stock Tracking**: Stock is tracked at the batch level with expiry dates and costs.
- **Transaction Types**: Multiple transaction types support various medicine movements.
- **Reports**: Multiple report types available (spending log, usage report, balance summary, stock status).





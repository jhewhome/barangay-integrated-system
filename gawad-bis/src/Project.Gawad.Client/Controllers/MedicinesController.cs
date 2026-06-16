using System.IO;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.Mvc.ViewEngines;
using Microsoft.AspNetCore.Mvc.Rendering;
using Microsoft.AspNetCore.Mvc.ViewFeatures;
using DinkToPdf;
using DinkToPdf.Contracts;
using MongoDB.Bson;
using Project.Gawad.Core.Providers;
using Project.Gawad.Core.Repositories;
using Project.Gawad.Core.Services;
using Project.Gawad.Domain.Objects.Authentication;
using Project.Gawad.Domain.Objects.Medicine;

namespace Project.Gawad.Client.Controllers;

public class MedicinesController(
    IMedicineProvider medicineProvider,
    IMedicineService medicineService,
    IMedicineAuditLogService auditLogService,
    IMedicineAuditLogRepository auditLogRepository,
    IUsersProvider usersProvider,
    ICompositeViewEngine viewEngine,
    IConverter converter,
    ILogger<MedicinesController> logger) : Controller
{
    private readonly ILogger<MedicinesController> _logger =
        logger ?? throw new ArgumentNullException(nameof(logger));

    private readonly IMedicineProvider _medicineProvider =
        medicineProvider ?? throw new ArgumentNullException(nameof(medicineProvider));

    private readonly IMedicineService _medicineService =
        medicineService ?? throw new ArgumentNullException(nameof(medicineService));

    private readonly IMedicineAuditLogService _auditLogService =
        auditLogService ?? throw new ArgumentNullException(nameof(auditLogService));

    private readonly IMedicineAuditLogRepository _auditLogRepository =
        auditLogRepository ?? throw new ArgumentNullException(nameof(auditLogRepository));

    private readonly IUsersProvider _usersProvider =
        usersProvider ?? throw new ArgumentNullException(nameof(usersProvider));

    private readonly ICompositeViewEngine _viewEngine =
        viewEngine ?? throw new ArgumentNullException(nameof(viewEngine));

    private readonly IConverter _converter =
        converter ?? throw new ArgumentNullException(nameof(converter));

    private string? GetClientIpAddress()
    {
        return HttpContext.Connection.RemoteIpAddress?.ToString() ?? 
               HttpContext.Request.Headers["X-Forwarded-For"].FirstOrDefault() ??
               HttpContext.Request.Headers["X-Real-IP"].FirstOrDefault();
    }

    private string? GetUserAgent()
    {
        return HttpContext.Request.Headers["User-Agent"].FirstOrDefault();
    }

    [HttpGet]
    public async Task<IActionResult> Index(int page = 1, int itemsPerPage = 35, int sortColIndex = 0, string sortColDir = "asc", string? search = null, string? category = null, string? unitType = null, string? status = null)
    {
        // Parse category if provided
        Domain.Enums.Medicine.MedicineCategory? categoryFilter = null;
        if (!string.IsNullOrWhiteSpace(category) && Enum.TryParse<Domain.Enums.Medicine.MedicineCategory>(category, out var parsedCategory))
            categoryFilter = parsedCategory;

        // Parse unit type if provided
        Domain.Enums.Medicine.UnitOfMeasure? unitTypeFilter = null;
        if (!string.IsNullOrWhiteSpace(unitType) && Enum.TryParse<Domain.Enums.Medicine.UnitOfMeasure>(unitType, out var parsedUnitType))
            unitTypeFilter = parsedUnitType;

        var paginated = await _medicineProvider.GetMedicinesListAsync(page, itemsPerPage, sortColIndex, sortColDir, search, categoryFilter, unitTypeFilter, status);
        ViewBag.TotalMedicines = paginated.RecordsTotal;
        ViewBag.PageNumber = paginated.PageNumber;
        ViewBag.PageSize = paginated.PageSize;
        ViewBag.TotalPages = paginated.PageSize > 0 ? (int)Math.Ceiling((double)paginated.RecordsTotal / paginated.PageSize) : 1;
        
        // Get balance summary (all time up to now) - this uses the same calculation logic as Spending Log.
        // Temporarily wrapped in try/catch to avoid DateTime overflow issues from extreme date values.
        try
        {
            var balanceSummary = await _medicineProvider.GetBalanceSummaryAsync(new ReportFilterObject 
            { 
                StartDate = DateTime.MinValue, 
                EndDate = null 
            });
            
            ViewBag.TotalStockReceivedCount = balanceSummary.TotalStockReceivedCount; // Total count of pieces received (from active stocks)
            ViewBag.TotalStockReceivedCost = balanceSummary.TotalPurchases; // Total cost calculated using same logic as Spending Log
            ViewBag.TotalDispensedCount = balanceSummary.TotalDispensedCount; // Count of pieces dispensed
            ViewBag.TotalRemainingStock = balanceSummary.TotalRemainingStock; // Count of pieces remaining
        }
        catch
        {
            // If anything goes wrong calculating the balance summary, still render the Medicines list
            ViewBag.TotalStockReceivedCount = 0;
            ViewBag.TotalStockReceivedCost = 0m;
            ViewBag.TotalDispensedCount = 0;
            ViewBag.TotalRemainingStock = 0;
        }
        
        // Pass filter values to view for form persistence
        ViewBag.Search = search;
        ViewBag.Category = category;
        ViewBag.UnitType = unitType;
        ViewBag.Status = status;
        
        return View(paginated);
    }

    [HttpGet]
    public async Task<IActionResult> GetMedicinesList(int page = 1, int itemsPerPage = 0, int sortColIndex = 0,
        string sortColDir = "asc", string? search = null)
    {
        var paginatedData =
            await _medicineProvider.GetMedicinesListAsync(page, itemsPerPage, sortColIndex, sortColDir, search);
        return Ok(paginatedData);
    }

    [HttpGet]
    public async Task<IActionResult> GetMedicineDetailsJson(string id)
    {
        if (string.IsNullOrWhiteSpace(id))
            return Json(new { success = false, message = "Medicine ID is required" });

        try
        {
            var medicineDetail = await _medicineProvider.GetMedicineDetailObjectAsync(ObjectId.Parse(id));
            if (medicineDetail == null)
                return Json(new { success = false, message = "Medicine not found" });

            return Json(new
            {
                success = true,
                id = medicineDetail.Id.ToString(),
                name = medicineDetail.Name,
                unitPrice = medicineDetail.UnitPrice,
                unitOfMeasure = medicineDetail.UnitOfMeasure.ToString(),
                bottleMeasurementType = medicineDetail.BottleMeasurementType,
                bottleMeasurementValue = medicineDetail.BottleMeasurementValue
            });
        }
        catch (Exception e)
        {
            return Json(new { success = false, message = e.Message });
        }
    }

    [HttpGet]
    [Authorize(Roles = "Administrator,Barangay Secretary,Kagawad,Health Worker / Staff")]
    public IActionResult Create()
    {
        return View(new CreateMedicineObject());
    }

    [HttpPost]
    [Authorize(Roles = "Administrator,Barangay Secretary,Kagawad,Health Worker / Staff")]
    public async Task<IActionResult> Create(CreateMedicineObject createMedicineObject)
    {
        if (!ModelState.IsValid)
            return View(createMedicineObject);

        var createdBy = await _usersProvider.GetCurrentUserAsync(HttpContext.User);

        var result = await _medicineService.CreateMedicine(createMedicineObject, createdBy);

        if (!result.IsSuccess)
        {
            foreach (var error in result.ModelState)
                ModelState.AddModelError(error.Key, error.Value);

            return View(createMedicineObject);
        }

        // Log audit action
        await _auditLogService.LogActionAsync(
            "Create",
            "Medicine",
            result.Data.Id,
            null,
            System.Text.Json.JsonSerializer.Serialize(new { Name = createMedicineObject.Name, Category = createMedicineObject.Category }),
            createdBy,
            GetClientIpAddress(),
            GetUserAgent());

        return RedirectToAction("Details", new { id = result.Data.Id });
    }

    [HttpGet]
    public async Task<IActionResult> Details(string id)
    {
        if (string.IsNullOrWhiteSpace(id))
            return RedirectToAction(nameof(Index));

        var medicineDetail = await _medicineProvider.GetMedicineDetailObjectAsync(ObjectId.Parse(id));

        if (medicineDetail is null)
            return RedirectToAction(nameof(Index));

        // Get recent transactions for this medicine (last 10)
        var recentTransactions = await _medicineProvider.GetMedicineTransactionsListAsync(ObjectId.Parse(id), page: 1, itemsPerPage: 10);
        ViewBag.RecentTransactions = recentTransactions.Data?.Take(10).ToList() ?? new List<MedicineTransactionListObject>();
        ViewBag.TotalTransactions = recentTransactions.RecordsTotal;

        // Calculate transaction statistics
        var allTransactions = await _medicineProvider.GetMedicineTransactionsListAsync(ObjectId.Parse(id), page: 1, itemsPerPage: 10000);
        var allTx = allTransactions.Data ?? new List<MedicineTransactionListObject>();
        
        var totalDispensed = allTx.Where(t => t.TransactionType == Domain.Enums.Medicine.MedicineTransactionType.Dispensed).Sum(t => t.Quantity);
        var totalStockIn = allTx.Where(t => t.TransactionType == Domain.Enums.Medicine.MedicineTransactionType.StockIn).Sum(t => t.Quantity);
        var totalStockOut = allTx.Where(t => t.TransactionType == Domain.Enums.Medicine.MedicineTransactionType.StockOut).Sum(t => t.Quantity);
        var totalDispensedValue = allTx.Where(t => t.TransactionType == Domain.Enums.Medicine.MedicineTransactionType.Dispensed)
            .Sum(t => t.TotalAmount ?? (t.UnitPrice ?? 0) * t.Quantity);
        
        ViewBag.TotalDispensed = totalDispensed;
        ViewBag.TotalStockIn = totalStockIn;
        ViewBag.TotalStockOut = totalStockOut;
        ViewBag.TotalDispensedValue = totalDispensedValue;
        ViewBag.DispensedCount = allTx.Count(t => t.TransactionType == Domain.Enums.Medicine.MedicineTransactionType.Dispensed);

        return View(medicineDetail);
    }

    [HttpGet]
    public async Task<IActionResult> MedicineReport(string id, DateTime? startDate = null, DateTime? endDate = null)
    {
        if (string.IsNullOrWhiteSpace(id))
            return RedirectToAction(nameof(Index));

        var medicineId = ObjectId.Parse(id);
        var medicineDetail = await _medicineProvider.GetMedicineDetailObjectAsync(medicineId);

        if (medicineDetail is null)
            return RedirectToAction(nameof(Index));

        // If no dates provided, default to all time
        if (!startDate.HasValue)
            startDate = DateTime.MinValue;
        if (!endDate.HasValue)
            endDate = DateTime.Now;

        // Get all stocks for this medicine
        var stocks = await _medicineProvider.GetMedicineStocksListAsync(medicineId, page: 1, itemsPerPage: 10000);
        ViewBag.StockBatches = stocks.Data?.ToList() ?? new List<Domain.Objects.Medicine.MedicineStockListObject>();

        // Get all transactions for this medicine
        var allTransactions = await _medicineProvider.GetMedicineTransactionsListAsync(medicineId, page: 1, itemsPerPage: 10000);
        var allTx = allTransactions.Data ?? new List<Domain.Objects.Medicine.MedicineTransactionListObject>();
        
        // Filter transactions by date range if provided
        if (startDate.HasValue && startDate.Value != DateTime.MinValue)
            allTx = allTx.Where(t => t.TransactionDate >= startDate.Value.Date).ToList();
        if (endDate.HasValue)
            allTx = allTx.Where(t => t.TransactionDate <= endDate.Value.Date.AddDays(1).AddTicks(-1)).ToList();

        // Calculate statistics
        var totalDispensed = allTx.Where(t => t.TransactionType == Domain.Enums.Medicine.MedicineTransactionType.Dispensed).Sum(t => t.Quantity);
        var totalStockIn = allTx.Where(t => t.TransactionType == Domain.Enums.Medicine.MedicineTransactionType.StockIn).Sum(t => t.Quantity);
        var totalStockOut = allTx.Where(t => t.TransactionType == Domain.Enums.Medicine.MedicineTransactionType.StockOut).Sum(t => t.Quantity);
        var totalDispensedValue = allTx.Where(t => t.TransactionType == Domain.Enums.Medicine.MedicineTransactionType.Dispensed)
            .Sum(t => t.TotalAmount ?? (t.UnitPrice ?? 0) * t.Quantity);

        ViewBag.Transactions = allTx.OrderByDescending(t => t.TransactionDate).ToList();
        ViewBag.TotalTransactions = allTx.Count;
        ViewBag.TotalDispensed = totalDispensed;
        ViewBag.TotalStockIn = totalStockIn;
        ViewBag.TotalStockOut = totalStockOut;
        ViewBag.TotalDispensedValue = totalDispensedValue;
        ViewBag.DispensedCount = allTx.Count(t => t.TransactionType == Domain.Enums.Medicine.MedicineTransactionType.Dispensed);
        ViewBag.StartDate = startDate;
        ViewBag.EndDate = endDate;
        ViewBag.GeneratedDate = DateTime.Now;

        return View(medicineDetail);
    }

    [HttpGet]
    public async Task<IActionResult> ExportMedicineReport(string id, DateTime? startDate = null, DateTime? endDate = null)
    {
        if (string.IsNullOrWhiteSpace(id))
            return BadRequest("Medicine ID is required.");

        var medicineId = ObjectId.Parse(id);
        var medicineDetail = await _medicineProvider.GetMedicineDetailObjectAsync(medicineId);

        if (medicineDetail is null)
            return NotFound("Medicine not found.");

        // If no dates provided, default to all time
        if (!startDate.HasValue)
            startDate = DateTime.MinValue;
        if (!endDate.HasValue)
            endDate = DateTime.Now;

        // Get all stocks for this medicine
        var stocks = await _medicineProvider.GetMedicineStocksListAsync(medicineId, page: 1, itemsPerPage: 10000);
        var stockBatches = stocks.Data?.ToList() ?? new List<Domain.Objects.Medicine.MedicineStockListObject>();

        // Get all transactions for this medicine
        var allTransactions = await _medicineProvider.GetMedicineTransactionsListAsync(medicineId, page: 1, itemsPerPage: 10000);
        var allTx = allTransactions.Data ?? new List<Domain.Objects.Medicine.MedicineTransactionListObject>();
        
        // Filter transactions by date range if provided
        if (startDate.HasValue && startDate.Value != DateTime.MinValue)
            allTx = allTx.Where(t => t.TransactionDate >= startDate.Value.Date).ToList();
        if (endDate.HasValue)
            allTx = allTx.Where(t => t.TransactionDate <= endDate.Value.Date.AddDays(1).AddTicks(-1)).ToList();

        // Generate CSV
        Response.ContentType = "text/csv";
        var timestamp = DateTime.Now.ToString("yyyyMMdd_HHmmss");
        Response.Headers.Add("Content-Disposition", 
            $"attachment; filename=MedicineReport_{medicineDetail.Name.Replace(" ", "_")}_{timestamp}.csv");

        var csv = new System.Text.StringBuilder();
        
        // Header Section
        csv.AppendLine($"Medicine Report: {medicineDetail.Name}");
        csv.AppendLine($"Generated: {DateTime.Now:MM/dd/yyyy HH:mm:ss}");
        csv.AppendLine($"Date Range: {(startDate.HasValue && startDate.Value != DateTime.MinValue ? startDate.Value.ToString("MM/dd/yyyy") : "All Time")} - {(endDate.HasValue ? endDate.Value.ToString("MM/dd/yyyy") : "Present")}");
        csv.AppendLine();
        
        // Medicine Details
        csv.AppendLine("MEDICINE DETAILS");
        csv.AppendLine($"Medicine Name,{medicineDetail.Name}");
        csv.AppendLine($"Category,{medicineDetail.CategoryName}");
        csv.AppendLine($"Unit of Measure,{medicineDetail.UnitOfMeasure}");
        csv.AppendLine($"Current Stock,{medicineDetail.TotalStock}");
        csv.AppendLine($"Minimum Stock Level,{medicineDetail.MinimumStockLevel}");
        csv.AppendLine($"Unit Price,{medicineDetail.UnitPrice?.ToString("F2") ?? "N/A"}");
        csv.AppendLine($"Status,{(medicineDetail.IsActive ? "Active" : "Inactive")}");
        csv.AppendLine();
        
        // Statistics
        var totalDispensed = allTx.Where(t => t.TransactionType == Domain.Enums.Medicine.MedicineTransactionType.Dispensed).Sum(t => t.Quantity);
        var totalStockIn = allTx.Where(t => t.TransactionType == Domain.Enums.Medicine.MedicineTransactionType.StockIn).Sum(t => t.Quantity);
        var totalStockOut = allTx.Where(t => t.TransactionType == Domain.Enums.Medicine.MedicineTransactionType.StockOut).Sum(t => t.Quantity);
        var totalDispensedValue = allTx.Where(t => t.TransactionType == Domain.Enums.Medicine.MedicineTransactionType.Dispensed)
            .Sum(t => t.TotalAmount ?? (t.UnitPrice ?? 0) * t.Quantity);
        
        csv.AppendLine("STATISTICS SUMMARY");
        csv.AppendLine($"Total Dispensed,{totalDispensed}");
        csv.AppendLine($"Total Stock In,{totalStockIn}");
        csv.AppendLine($"Total Stock Out,{totalStockOut}");
        csv.AppendLine($"Total Value Dispensed,{totalDispensedValue:F2}");
        csv.AppendLine($"Number of Transactions,{allTx.Count}");
        csv.AppendLine();
        
        // Stock Batches
        csv.AppendLine("STOCK BATCHES");
        csv.AppendLine("Batch Number,Lot Number,Quantity,Expiry Date,Days Until Expiry,Status");
        foreach (var stock in stockBatches.OrderBy(s => s.ExpiryDate ?? DateTime.MaxValue))
        {
            var daysUntilExpiry = stock.ExpiryDate.HasValue ? (stock.ExpiryDate.Value.Date - DateTime.Now.Date).Days : (int?)null;
            var status = stock.IsExpired ? "Expired" : (stock.IsExpiringSoon ? "Expiring Soon" : "Active");
            csv.AppendLine($"{stock.BatchNumber ?? "N/A"}," +
                          $"{stock.LotNumber ?? "N/A"}," +
                          $"{stock.Quantity}," +
                          $"{(stock.ExpiryDate?.ToString("MM/dd/yyyy") ?? "N/A")}," +
                          $"{(daysUntilExpiry.HasValue ? daysUntilExpiry.Value.ToString() : "N/A")}," +
                          $"{status}");
        }
        csv.AppendLine();
        
        // Transactions
        csv.AppendLine("TRANSACTION HISTORY");
        csv.AppendLine("Date,Type,Quantity,Unit Price,Total Amount,Recipient,Reason");
        foreach (var t in allTx.OrderByDescending(x => x.TransactionDate))
        {
            csv.AppendLine($"{t.TransactionDate:MM/dd/yyyy HH:mm}," +
                          $"{t.TransactionTypeName}," +
                          $"{t.Quantity}," +
                          $"{(t.UnitPrice?.ToString("F2") ?? "N/A")}," +
                          $"{(t.TotalAmount?.ToString("F2") ?? "N/A")}," +
                          $"{(t.RecipientName ?? "N/A")}," +
                          $"{(t.Reason ?? "N/A")}");
        }

        return Content(csv.ToString(), "text/csv");
    }

    [HttpGet]
    [Authorize(Roles = "Administrator,Barangay Secretary,Kagawad,Health Worker / Staff")]
    public async Task<IActionResult> Edit(string id)
    {
        if (string.IsNullOrWhiteSpace(id))
            return RedirectToAction(nameof(Index));

        var updateMedicine = await _medicineProvider.GetCreateUpdateMedicineObjectAsync(ObjectId.Parse(id));

        if (updateMedicine is null)
            return RedirectToAction(nameof(Index));

        return View(updateMedicine);
    }

    [HttpPost]
    [Authorize(Roles = "Administrator,Barangay Secretary,Kagawad,Health Worker / Staff")]
    public async Task<IActionResult> Edit(UpdateMedicineObject updateMedicineObject)
    {
        if (!ModelState.IsValid)
            return View(updateMedicineObject);

        var updatedBy = await _usersProvider.GetCurrentUserAsync(HttpContext.User);

        var result = await _medicineService.UpdateMedicine(updateMedicineObject, updatedBy);

        if (!result.IsSuccess)
        {
            foreach (var error in result.ModelState)
                ModelState.AddModelError(error.Key, error.Value);

            return View(updateMedicineObject);
        }

        // Log audit action
        await _auditLogService.LogActionAsync(
            "Update",
            "Medicine",
            updateMedicineObject.MedicineId,
            null,
            System.Text.Json.JsonSerializer.Serialize(new { Name = updateMedicineObject.Name }),
            updatedBy,
            GetClientIpAddress(),
            GetUserAgent());

        return RedirectToAction("Details", new { id = updateMedicineObject.MedicineId });
    }

    [HttpPost]
    [ValidateAntiForgeryToken]
    [Authorize(Roles = "Administrator")]
    public async Task<IActionResult> Delete(string id)
    {
        if (string.IsNullOrWhiteSpace(id))
            return Json(new { success = false, message = "Medicine ID is required" });

        try
        {
            var deletedBy = await _usersProvider.GetCurrentUserAsync(HttpContext.User);

            var medicineId = ObjectId.Parse(id);
            var medicine = await _medicineProvider.GetMedicineDetailObjectAsync(medicineId);
            var medicineName = medicine?.Name ?? "Unknown";

            var result = await _medicineService.RemoveMedicine(id, deletedBy);
            
            if (!result)
            {
                return Json(new { success = false, message = "Failed to delete medicine. Medicine may not exist or already deleted." });
            }

            // Log audit action
            await _auditLogService.LogActionAsync(
                "Delete",
                "Medicine",
                medicineId,
                null,
                System.Text.Json.JsonSerializer.Serialize(new { Name = medicineName }),
                deletedBy,
                GetClientIpAddress(),
                GetUserAgent());

            return Json(new { success = true, message = "Medicine deleted successfully" });
        }
        catch (Exception e)
        {
            _logger.LogError(e, e.Message);
            return Json(new { success = false, message = e.Message });
        }
    }

    [HttpGet]
    [Authorize(Roles = "Administrator,Barangay Secretary,Kagawad,Health Worker / Staff")]
    public async Task<IActionResult> AddStock(string medicineId)
    {
        if (string.IsNullOrWhiteSpace(medicineId))
            return RedirectToAction(nameof(Index));

        var createStockObject = new CreateMedicineStockObject
        {
            MedicineId = ObjectId.Parse(medicineId),
            ReceivedDate = DateTime.Now
        };

        return View(createStockObject);
    }

    [HttpPost]
    [Authorize(Roles = "Administrator,Barangay Secretary,Kagawad,Health Worker / Staff")]
    public async Task<IActionResult> AddStock(CreateMedicineStockObject createStockObject)
    {
        // Handle unit type conversion for cost calculation
        var inputUnitType = Request.Form["inputUnitType"].ToString();
        var inputUnitCountStr = Request.Form["inputUnitCount"].ToString();
        
        // Get medicine to retrieve unit price if not provided in form
        // IMPORTANT: Unit Price is per box/bottle, NOT per piece
        var medicineDetail = await _medicineProvider.GetMedicineDetailObjectAsync(createStockObject.MedicineId);
        if (medicineDetail != null)
        {
            // Use form's CostPerUnit if provided, otherwise use medicine's default unit price
            if (!createStockObject.CostPerUnit.HasValue && medicineDetail.UnitPrice.HasValue)
            {
                createStockObject.CostPerUnit = medicineDetail.UnitPrice;
            }
        }
        
        if (!ModelState.IsValid)
            return View(createStockObject);

        var createdBy = await _usersProvider.GetCurrentUserAsync(HttpContext.User);

        // Store unit type info in Notes for cost calculation
        // Format: "INPUT_UNIT:unitType:unitCount"
        // Example: "INPUT_UNIT:boxes:2" means 2 boxes
        // Example: "INPUT_UNIT:bottles:5" means 5 bottles
        // This allows backend to calculate: TotalAmount = UnitPrice × unitCount (NOT quantity)
        if (!string.IsNullOrWhiteSpace(inputUnitType) && !string.IsNullOrWhiteSpace(inputUnitCountStr))
        {
            // Validate and normalize unit type
            var normalizedUnitType = inputUnitType.Trim().ToLower();
            if (normalizedUnitType == "box") normalizedUnitType = "boxes";
            if (normalizedUnitType == "bottle") normalizedUnitType = "bottles";
            if (normalizedUnitType == "piece") normalizedUnitType = "pieces";
            
            // Parse and validate unit count
            if (decimal.TryParse(inputUnitCountStr.Trim(), out var unitCount) && unitCount > 0)
            {
                var notesPrefix = $"INPUT_UNIT:{normalizedUnitType}:{unitCount}";
                
                if (string.IsNullOrWhiteSpace(createStockObject.Notes))
                {
                    createStockObject.Notes = notesPrefix;
                }
                else
                {
                    createStockObject.Notes = $"{notesPrefix}; {createStockObject.Notes}";
                }
            }
            else
            {
                // Log warning if unit count is invalid
                // But continue with the request - backend will use fallback calculation
            }
        }

        var result = await _medicineService.AddStock(createStockObject, createdBy);

        if (!result.IsSuccess)
        {
            foreach (var error in result.ModelState)
                ModelState.AddModelError(error.Key, error.Value);

            return View(createStockObject);
        }

        // Log audit action
        await _auditLogService.LogActionAsync(
            "StockIn",
            "MedicineStock",
            result.Data?.Id,
            createStockObject.MedicineId,
            System.Text.Json.JsonSerializer.Serialize(new { Quantity = createStockObject.Quantity, CostPerUnit = createStockObject.CostPerUnit, Supplier = createStockObject.Supplier }),
            createdBy,
            GetClientIpAddress(),
            GetUserAgent());

        return RedirectToAction("Details", new { id = createStockObject.MedicineId });
    }

    [HttpGet]
    public async Task<IActionResult> Stocks(string medicineId, int page = 1)
    {
        if (string.IsNullOrWhiteSpace(medicineId))
            return RedirectToAction(nameof(Index));

        var stocks = await _medicineProvider.GetMedicineStocksListAsync(ObjectId.Parse(medicineId), page);
        return View(stocks);
    }

    [HttpGet]
    public async Task<IActionResult> Transactions(string? medicineId = null, int page = 1, string? search = null, string? transactionType = null, string? recipientName = null, string? startDate = null, string? endDate = null)
    {
        ObjectId? medId = null;
        if (!string.IsNullOrWhiteSpace(medicineId))
            medId = ObjectId.Parse(medicineId);

        // Parse dates if provided
        DateTime? start = null;
        DateTime? end = null;
        if (!string.IsNullOrWhiteSpace(startDate) && DateTime.TryParse(startDate, out var parsedStart))
            start = parsedStart;
        if (!string.IsNullOrWhiteSpace(endDate) && DateTime.TryParse(endDate, out var parsedEnd))
            end = parsedEnd;

        // Parse transaction type if provided
        Domain.Enums.Medicine.MedicineTransactionType? typeFilter = null;
        if (!string.IsNullOrWhiteSpace(transactionType) && Enum.TryParse<Domain.Enums.Medicine.MedicineTransactionType>(transactionType, out var parsedType))
            typeFilter = parsedType;

        // For Staff users, only show their own transactions
        ObjectId? createdByUserId = null;
        if (User.IsInRole("Health Worker / Staff"))
        {
            var currentUser = await _usersProvider.GetCurrentUserAsync(User);
            if (currentUser != null)
            {
                createdByUserId = currentUser.Id;
            }
        }

        var transactions = await _medicineProvider.GetMedicineTransactionsListAsync(
            medId, 
            page, 
            search: search, 
            transactionType: typeFilter, 
            recipientName: recipientName, 
            startDate: start, 
            endDate: end,
            createdByUserId: createdByUserId);
        
        // Pass filter values to view for form persistence
        ViewBag.Search = search;
        ViewBag.TransactionType = transactionType;
        ViewBag.RecipientName = recipientName;
        ViewBag.StartDate = startDate;
        ViewBag.EndDate = endDate;
        ViewBag.MedicineId = medicineId;
        ViewBag.IsStaffView = User.IsInRole("Health Worker / Staff");
        
        return View(transactions);
    }

    [HttpGet]
    public async Task<IActionResult> EditTransaction(string id)
    {
        if (string.IsNullOrWhiteSpace(id))
            return RedirectToAction(nameof(Transactions));

        var transaction = await _medicineProvider.GetTransactionListObjectByIdAsync(ObjectId.Parse(id));
        if (transaction == null)
        {
            TempData["ErrorMessage"] = "Transaction not found.";
            return RedirectToAction(nameof(Transactions));
        }

        // Only allow editing StockIn transactions
        if (transaction.TransactionType != Domain.Enums.Medicine.MedicineTransactionType.StockIn)
        {
            TempData["ErrorMessage"] = "Only StockIn transactions can be edited.";
            return RedirectToAction(nameof(Transactions));
        }

        var updateTransactionObject = new Domain.Objects.Medicine.UpdateMedicineTransactionObject
        {
            TransactionId = ObjectId.Parse(id),
            Quantity = transaction.Quantity,
            TransactionDate = transaction.TransactionDate,
            UnitPrice = transaction.UnitPrice,
            Reason = transaction.Reason,
            Notes = transaction.Notes // Get Notes from transaction list object (now includes Notes)
        };

        ViewBag.MedicineName = transaction.MedicineName;
        return View(updateTransactionObject);
    }

    [HttpPost]
    public async Task<IActionResult> EditTransaction(Domain.Objects.Medicine.UpdateMedicineTransactionObject updateTransactionObject)
    {
        if (!ModelState.IsValid)
        {
            var transaction = await _medicineProvider.GetTransactionListObjectByIdAsync(updateTransactionObject.TransactionId);
            if (transaction != null)
            {
                ViewBag.MedicineName = transaction.MedicineName;
            }
            return View(updateTransactionObject);
        }

        var updatedBy = await _usersProvider.GetCurrentUserAsync(HttpContext.User);
        var result = await _medicineService.UpdateTransaction(updateTransactionObject, updatedBy);

        if (!result.IsSuccess)
        {
            foreach (var error in result.ModelState)
                ModelState.AddModelError(error.Key, error.Value);

            var transaction = await _medicineProvider.GetTransactionListObjectByIdAsync(updateTransactionObject.TransactionId);
            if (transaction != null)
            {
                ViewBag.MedicineName = transaction.MedicineName;
            }
            return View(updateTransactionObject);
        }

        TempData["SuccessMessage"] = "Stock-in transaction updated successfully. Stock quantity has been adjusted.";
        return RedirectToAction(nameof(Transactions));
    }

    [HttpPost]
    [Authorize(Roles = "Administrator")]
    public async Task<IActionResult> DeleteTransaction(string id)
    {
        if (string.IsNullOrWhiteSpace(id))
        {
            return Json(new { success = false, message = "Transaction ID is required." });
        }

        var deletedBy = await _usersProvider.GetCurrentUserAsync(HttpContext.User);
        if (deletedBy == null)
        {
            return Json(new { success = false, message = "User not found." });
        }

        // Check if user is admin
        if (deletedBy.Role != "Administrator")
        {
            return Json(new { success = false, message = "Only administrators can delete transactions." });
        }

        try
        {
            var transactionId = ObjectId.Parse(id);
            var transaction = await _medicineProvider.GetTransactionListObjectByIdAsync(transactionId);
            var transactionInfo = transaction != null 
                ? $"Medicine: {transaction.MedicineName}, Quantity: {transaction.Quantity}, Type: {transaction.TransactionTypeName}"
                : "Unknown";

            var result = await _medicineService.DeleteTransaction(id, deletedBy);
            
            if (result)
            {
                // Log audit action
                await _auditLogService.LogActionAsync(
                    "Delete",
                    "MedicineTransaction",
                    transactionId,
                    null,
                    System.Text.Json.JsonSerializer.Serialize(new { TransactionInfo = transactionInfo }),
                    deletedBy,
                    GetClientIpAddress(),
                    GetUserAgent());

                return Json(new { success = true, message = "Transaction deleted successfully. Stock has been adjusted." });
            }
            else
            {
                return Json(new { success = false, message = "Failed to delete transaction. It may not exist or may have already been deleted." });
            }
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, $"Error deleting transaction {id}");
            return Json(new { success = false, message = $"An error occurred while deleting the transaction: {ex.Message}" });
        }
    }

    [HttpGet]
    public async Task<IActionResult> Dispense(string? medicineId = null)
    {
        var createTransactionObject = new CreateMedicineTransactionObject
        {
            TransactionType = Domain.Enums.Medicine.MedicineTransactionType.Dispensed,
            TransactionDate = DateTime.Now
        };

        MedicineDetailObject? medicine = null;
        if (!string.IsNullOrWhiteSpace(medicineId))
        {
            var medId = ObjectId.Parse(medicineId);
            createTransactionObject.MedicineId = medId;
            
            // Check if medicine exists and has stock
            medicine = await _medicineProvider.GetMedicineDetailObjectAsync(medId);
            if (medicine == null)
            {
                TempData["ErrorMessage"] = "Medicine not found.";
                return RedirectToAction(nameof(Index));
            }
            
            // Check if stock is zero - block dispense
            if (medicine.TotalStock <= 0)
            {
                TempData["ErrorMessage"] = $"Cannot dispense '{medicine.Name}': Stock is zero. Please add stock first.";
                return RedirectToAction(nameof(Index));
            }
            
            // Check if stock is low (at or below minimum level) - block dispense
            if (medicine.TotalStock <= medicine.MinimumStockLevel)
            {
                TempData["ErrorMessage"] = $"Cannot dispense '{medicine.Name}': Stock is low (Current: {medicine.TotalStock}, Minimum: {medicine.MinimumStockLevel}). Please add stock first.";
                return RedirectToAction(nameof(Index));
            }
        }

        ViewBag.Medicine = medicine; // Pass medicine details to view
        return View(createTransactionObject);
    }

    [HttpPost]
    public async Task<IActionResult> Dispense(CreateMedicineTransactionObject createTransactionObject)
    {
        createTransactionObject.TransactionType = Domain.Enums.Medicine.MedicineTransactionType.Dispensed;

        // Build notes with prescription information if provided (for non-residents)
        var notesParts = new List<string>();
        
        // Store per-piece dispense information in Notes
        // Format: "PIECES:quantity" for clarity (per-piece dispense)
        if (createTransactionObject.Quantity > 0)
        {
            notesParts.Add($"PIECES:{createTransactionObject.Quantity}");
        }

        // Store prescription information if provided (for non-residents)
        if (!string.IsNullOrWhiteSpace(createTransactionObject.Prescription))
        {
            notesParts.Add($"PRESCRIPTION:{createTransactionObject.Prescription}");
        }

        // Combine with existing notes if any
        if (notesParts.Count > 0)
        {
            var combinedNotes = string.Join("; ", notesParts);
            if (string.IsNullOrWhiteSpace(createTransactionObject.Notes))
            {
                createTransactionObject.Notes = combinedNotes;
            }
            else
            {
                createTransactionObject.Notes = $"{combinedNotes}; {createTransactionObject.Notes}";
            }
        }

        if (!ModelState.IsValid)
            return View(createTransactionObject);

        var createdBy = await _usersProvider.GetCurrentUserAsync(HttpContext.User);

        var result = await _medicineService.CreateTransaction(createTransactionObject, createdBy);

        if (!result.IsSuccess)
        {
            foreach (var error in result.ModelState)
                ModelState.AddModelError(error.Key, error.Value);

            return View(createTransactionObject);
        }

        // Log audit action
        await _auditLogService.LogActionAsync(
            "Dispensed",
            "MedicineTransaction",
            result.Data?.Id,
            createTransactionObject.MedicineId,
            System.Text.Json.JsonSerializer.Serialize(new { Quantity = createTransactionObject.Quantity, RecipientName = createTransactionObject.RecipientName, Reason = createTransactionObject.Reason }),
            createdBy,
            GetClientIpAddress(),
            GetUserAgent());

        return RedirectToAction("DispensedLog");
    }

    [HttpGet]
    public async Task<IActionResult> LowStock()
    {
        var lowStockMedicines = await _medicineProvider.GetLowStockMedicinesAsync();
        return View(lowStockMedicines);
    }

    [HttpGet]
    public async Task<IActionResult> ExpiringStocks(int days = 30)
    {
        var expiringStocks = await _medicineProvider.GetExpiringStocksAsync(days);
        return View(expiringStocks);
    }

    [HttpGet]
    public async Task<IActionResult> ExpiredStocks()
    {
        var expiredStocks = await _medicineProvider.GetExpiredStocksAsync();
        return View(expiredStocks);
    }

    [HttpGet]
    public IActionResult StockStatusReport()
    {
        var filter = new Domain.Objects.Medicine.ReportFilterObject
        {
            StartDate = DateTime.Now.AddDays(-30),
            EndDate = DateTime.Now,
            IncludeInactive = false
        };
        return View(filter);
    }

    [HttpPost]
    public async Task<IActionResult> StockStatusReport(Domain.Objects.Medicine.ReportFilterObject filter)
    {
        var reportData = await _medicineProvider.GetStockStatusReportAsync(filter);
        ViewBag.ReportData = reportData;
        ViewBag.ReportTitle = "Medicine Stock Status Report";
        ViewBag.GeneratedDate = DateTime.Now;
        return View(filter);
    }

    [HttpGet]
    public IActionResult UsageReport()
    {
        var filter = new Domain.Objects.Medicine.ReportFilterObject
        {
            StartDate = DateTime.Now.AddDays(-30),
            EndDate = DateTime.Now
        };
        return View(filter);
    }

    [HttpPost]
    public async Task<IActionResult> UsageReport(Domain.Objects.Medicine.ReportFilterObject filter)
    {
        if (!filter.StartDate.HasValue || !filter.EndDate.HasValue)
        {
            ModelState.AddModelError("", "Start date and end date are required.");
            return View(filter);
        }

        if (filter.StartDate.Value > filter.EndDate.Value)
        {
            ModelState.AddModelError("", "Start date must be before or equal to end date.");
            return View(filter);
        }

        var reportData = await _medicineProvider.GetUsageReportAsync(filter);
        ViewBag.ReportData = reportData;
        ViewBag.ReportTitle = $"Medicine Usage Report ({filter.StartDate.Value:MM/dd/yyyy} - {filter.EndDate.Value:MM/dd/yyyy})";
        ViewBag.GeneratedDate = DateTime.Now;
        return View(filter);
    }

    [HttpGet]
    public async Task<IActionResult> ExportStockStatusReport(Domain.Objects.Medicine.ReportFilterObject? filter)
    {
        var reportData = await _medicineProvider.GetStockStatusReportAsync(filter);
        
        // Generate CSV or JSON response
        Response.ContentType = "text/csv";
        var timestamp = DateTime.Now.ToString("yyyyMMdd_HHmmss");
        Response.Headers.Add("Content-Disposition", $"attachment; filename=StockStatusReport_{timestamp}.csv");

        var csv = new System.Text.StringBuilder();
        csv.AppendLine("Medicine Name,Category,Supplier,Current Stock,Minimum Stock Level,Status,Expiring Soon,Expired,Unit Price,Total Value");

        foreach (var item in reportData)
        {
            csv.AppendLine($"{item.MedicineName}," +
                          $"{item.Category}," +
                          $"{item.Supplier}," +
                          $"{item.CurrentStock}," +
                          $"{item.MinimumStockLevel}," +
                          $"{item.Status}," +
                          $"{item.ExpiringSoonCount}," +
                          $"{item.ExpiredCount}," +
                          $"{item.UnitPrice?.ToString("F2") ?? "N/A"}," +
                          $"{item.TotalValue?.ToString("F2") ?? "N/A"}");
        }

        return Content(csv.ToString(), "text/csv");
    }

    [HttpGet]
    public async Task<IActionResult> ExportUsageReport(Domain.Objects.Medicine.ReportFilterObject filter)
    {
        if (!filter.StartDate.HasValue || !filter.EndDate.HasValue)
        {
            return BadRequest("Start date and end date are required.");
        }

        var reportData = await _medicineProvider.GetUsageReportAsync(filter);
        
        Response.ContentType = "text/csv";
        var timestamp = DateTime.Now.ToString("yyyyMMdd_HHmmss");
        Response.Headers.Add("Content-Disposition", 
            $"attachment; filename=UsageReport_{filter.StartDate.Value:yyyyMMdd}_{filter.EndDate.Value:yyyyMMdd}_{timestamp}.csv");

        var csv = new System.Text.StringBuilder();
        csv.AppendLine("Medicine Name,Category,Total Quantity Dispensed,Number of Transactions,Total Value,First Dispensed Date,Last Dispensed Date,Most Common Recipient");

        foreach (var item in reportData)
        {
            csv.AppendLine($"{item.MedicineName}," +
                          $"{item.Category}," +
                          $"{item.TotalQuantityDispensed}," +
                          $"{item.NumberOfTransactions}," +
                          $"{item.TotalValue?.ToString("F2") ?? "N/A"}," +
                          $"{item.FirstDispensedDate?.ToString("MM/dd/yyyy") ?? "N/A"}," +
                          $"{item.LastDispensedDate?.ToString("MM/dd/yyyy") ?? "N/A"}," +
                          $"{item.MostCommonRecipient}");
        }

        return Content(csv.ToString(), "text/csv");
    }

    [HttpGet]
    public async Task<IActionResult> DispensedLog()
    {
        var filter = new Domain.Objects.Medicine.ReportFilterObject
        {
            StartDate = DateTime.Now.AddDays(-30),
            EndDate = DateTime.Now
        };
        
        // Only Administrators can see all dispenses; other users only see their own
        if (!User.IsInRole("Administrator"))
        {
            var currentUser = await _usersProvider.GetCurrentUserAsync(User);
            if (currentUser != null)
            {
                filter.CreatedByUserId = currentUser.Id.ToString();
            }
        }
        
        // Preload data so the page shows recent dispensed transactions without requiring Generate click
        var _ = await LoadDispensedLogDefaults(filter);
        return View(filter);
    }

    [HttpPost]
    public async Task<IActionResult> DispensedLog(Domain.Objects.Medicine.ReportFilterObject filter)
    {
        // Normalize to date-only and inclusive end-of-day
        if (filter.StartDate.HasValue) filter.StartDate = filter.StartDate.Value.Date;
        if (filter.EndDate.HasValue) filter.EndDate = filter.EndDate.Value.Date.AddDays(1).AddTicks(-1);

        // Only Administrators can see all dispenses; other users only see their own
        if (!User.IsInRole("Administrator"))
        {
            var currentUser = await _usersProvider.GetCurrentUserAsync(User);
            if (currentUser != null)
            {
                filter.CreatedByUserId = currentUser.Id.ToString();
            }
        }

        var data = await _medicineProvider.GetDispensedLogAsync(filter);
        ViewBag.ReportData = data;
        ViewBag.ReportTitle = $"Dispensed Medicines ({filter.StartDate:MM/dd/yyyy} - {filter.EndDate:MM/dd/yyyy})";
        ViewBag.GeneratedDate = DateTime.Now;
        return View(filter);
    }

    private async Task<bool> LoadDispensedLogDefaults(Domain.Objects.Medicine.ReportFilterObject filter)
    {
        var data = await _medicineProvider.GetDispensedLogAsync(filter);
        ViewBag.ReportData = data;
        ViewBag.ReportTitle = $"Dispensed Medicines ({filter.StartDate:MM/dd/yyyy} - {filter.EndDate:MM/dd/yyyy})";
        ViewBag.GeneratedDate = DateTime.Now;
        return true;
    }

    [HttpGet]
    public async Task<IActionResult> ExportDispensedLog(Domain.Objects.Medicine.ReportFilterObject filter)
    {
        // Only Administrators can export all dispenses; other users only export their own
        if (!User.IsInRole("Administrator"))
        {
            var currentUser = await _usersProvider.GetCurrentUserAsync(User);
            if (currentUser != null)
            {
                filter.CreatedByUserId = currentUser.Id.ToString();
            }
        }

        var data = await _medicineProvider.GetDispensedLogAsync(filter);
        var csv = new System.Text.StringBuilder();
        csv.AppendLine("Date,Medicine,Quantity,Recipient,Reason");
        foreach (var d in data)
        {
            csv.AppendLine($"{d.TransactionDate:MM/dd/yyyy},{d.MedicineName},{d.Quantity},{d.RecipientName},{d.Reason}");
        }
        var timestamp = DateTime.Now.ToString("yyyyMMdd_HHmmss");
        Response.Headers.Append("Content-Disposition", $"attachment; filename=DispensedLog_{timestamp}.csv");
        return Content(csv.ToString(), "text/csv");
    }

    [HttpGet]
    public async Task<IActionResult> Receipt(string id)
    {
        if (string.IsNullOrWhiteSpace(id)) return RedirectToAction(nameof(Index));
        var t = await _medicineProvider.GetTransactionListObjectByIdAsync(MongoDB.Bson.ObjectId.Parse(id));
        if (t == null) return RedirectToAction(nameof(Index));
        return View(t);
    }

    [HttpGet]
    public async Task<IActionResult> ReceiptDocument(string id)
    {
        if (string.IsNullOrWhiteSpace(id))
            return RedirectToAction(nameof(Index));

        _logger.LogInformation("Requested receipt document for Medicine Transaction ID: {TransactionId}", id);

        try
        {
            var transaction = await _medicineProvider.GetTransactionListObjectByIdAsync(ObjectId.Parse(id));
            
            if (transaction == null)
            {
                _logger.LogWarning("Transaction not found for ID: {TransactionId}", id);
                return NotFound();
            }

            // Generate PDF bytes to enable Chrome's native PDF viewer (same as TransactionDocument)
            // This requires DinkToPdf with native libraries (wkhtmltopdf)
            // If PDF generation fails, fall back to HTML view
            try
            {
                var htmlContent = await RenderViewToStringAsync("ReceiptPdf", transaction);
                
                if (string.IsNullOrEmpty(htmlContent))
                {
                    _logger.LogWarning("Failed to render view for Transaction ID: {TransactionId}", id);
                    return View("ReceiptPdf", transaction);
                }

                var globalSettings = new GlobalSettings
                {
                    ColorMode = ColorMode.Color,
                    Orientation = Orientation.Portrait,
                    PaperSize = PaperKind.A4,
                    Margins = new MarginSettings { Top = 10, Bottom = 10, Left = 10, Right = 10 }
                };

                var objectSettings = new ObjectSettings
                {
                    PagesCount = true,
                    HtmlContent = htmlContent,
                    WebSettings = { DefaultEncoding = "utf-8" },
                    HeaderSettings = { FontSize = 9, Right = "Page [page] of [toPage]", Line = false },
                    FooterSettings = { FontSize = 9, Center = "Generated: " + DateTime.Now.ToString("MM/dd/yyyy HH:mm:ss"), Line = false }
                };

                var pdf = new HtmlToPdfDocument()
                {
                    GlobalSettings = globalSettings,
                    Objects = { objectSettings }
                };

                var pdfBytes = _converter.Convert(pdf);
                
                if (pdfBytes != null && pdfBytes.Length > 0)
                {
                    // Return PDF bytes - this enables Chrome's native PDF viewer with print/save controls
                    // Same approach as TransactionDocument
                    return File(pdfBytes, "application/pdf");
                }
                else
                {
                    _logger.LogWarning("PDF conversion returned empty for Transaction ID: {TransactionId}. Falling back to HTML.", id);
                    return View("ReceiptPdf", transaction);
                }
            }
            catch (Exception pdfEx)
            {
                _logger.LogError(pdfEx, "PDF generation failed for Transaction ID: {TransactionId}. Error: {ErrorMessage}. " +
                    "Note: DinkToPdf requires wkhtmltopdf native libraries. Install wkhtmltopdf to enable PDF generation. " +
                    "Falling back to HTML view.", id, pdfEx.Message);
                // Fall back to HTML view if PDF generation fails
                return View("ReceiptPdf", transaction);
            }
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error generating receipt document for Transaction ID: {TransactionId}", id);
            return StatusCode(500, "Failed to generate receipt document.");
        }
    }

    [HttpGet]
    public async Task<IActionResult> DownloadReceipt(string id)
    {
        if (string.IsNullOrWhiteSpace(id))
            return RedirectToAction(nameof(Index));

        _logger.LogInformation("Downloading receipt PDF for Medicine Transaction ID: {TransactionId}", id);

        try
        {
            var transaction = await _medicineProvider.GetTransactionListObjectByIdAsync(ObjectId.Parse(id));
            
            if (transaction == null)
            {
                _logger.LogWarning("Transaction not found for ID: {TransactionId}", id);
                return NotFound();
            }

            // Render the ReceiptPdf view to HTML string
            var htmlContent = await RenderViewToStringAsync("ReceiptPdf", transaction);
            
            if (string.IsNullOrEmpty(htmlContent))
            {
                _logger.LogWarning("Failed to render view for Transaction ID: {TransactionId}", id);
                return StatusCode(500, "Failed to generate receipt PDF.");
            }

            // Convert HTML to PDF using DinkToPdf
            var globalSettings = new GlobalSettings
            {
                ColorMode = ColorMode.Color,
                Orientation = Orientation.Portrait,
                PaperSize = PaperKind.A4,
                Margins = new MarginSettings { Top = 10, Bottom = 10, Left = 10, Right = 10 }
            };

            var objectSettings = new ObjectSettings
            {
                PagesCount = true,
                HtmlContent = htmlContent,
                WebSettings = { DefaultEncoding = "utf-8" },
                HeaderSettings = { FontSize = 9, Right = "Page [page] of [toPage]", Line = false },
                FooterSettings = { FontSize = 9, Center = "Generated: " + DateTime.Now.ToString("MM/dd/yyyy HH:mm:ss"), Line = false }
            };

            var pdf = new HtmlToPdfDocument()
            {
                GlobalSettings = globalSettings,
                Objects = { objectSettings }
            };

            byte[]? pdfBytes = null;
            try
            {
                pdfBytes = _converter.Convert(pdf);
            }
            catch (Exception pdfEx)
            {
                _logger.LogError(pdfEx, "DinkToPdf conversion failed for Transaction ID: {TransactionId}. Error: {ErrorMessage}. " +
                    "DinkToPdfIncludesDependencies package should include native libraries. " +
                    "Check if libwkhtmltox.dll is in runtimes/win-x64/native folder.", id, pdfEx.Message);
                // Return error message so user knows what's wrong
                return StatusCode(500, $"PDF generation failed: {pdfEx.Message}");
            }
            
            if (pdfBytes == null || pdfBytes.Length == 0)
            {
                _logger.LogWarning("PDF conversion returned empty result for Transaction ID: {TransactionId}", id);
                return StatusCode(500, "PDF conversion returned empty result");
            }

            // Return PDF with download headers - direct download
            var fileName = $"Receipt_{id}.pdf";
            Response.Headers.Append("Content-Disposition", $"attachment; filename=\"{fileName}\"");
            return File(pdfBytes, "application/pdf", fileName);
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error generating receipt PDF for Transaction ID: {TransactionId}. Exception: {ExceptionMessage}. StackTrace: {StackTrace}", 
                id, ex.Message, ex.StackTrace);
            
            // Do not return HTML view - always return error for download action
            // This ensures download button doesn't show HTML page
            return StatusCode(500, $"Failed to generate receipt PDF. Error: {ex.Message}");
        }
    }

    private async Task<string> RenderViewToStringAsync(string viewName, object model)
    {
        ViewData.Model = model;
        using (var sw = new StringWriter())
        {
            var viewResult = _viewEngine.FindView(ControllerContext, viewName, false);

            if (!viewResult.Success)
            {
                throw new InvalidOperationException($"View '{viewName}' not found");
            }

            var viewContext = new ViewContext(
                ControllerContext,
                viewResult.View,
                ViewData,
                TempData,
                sw,
                new HtmlHelperOptions()
            );

            await viewResult.View.RenderAsync(viewContext);
            return sw.ToString();
        }
    }

    [HttpGet]
    public IActionResult SpendingLog()
    {
        var filter = new Domain.Objects.Medicine.ReportFilterObject
        {
            StartDate = DateTime.Now.AddDays(-30),
            EndDate = DateTime.Now
        };
        return View(filter);
    }

    [HttpPost]
    public async Task<IActionResult> SpendingLog(Domain.Objects.Medicine.ReportFilterObject filter)
    {
        var logs = await _medicineProvider.GetSpendingLogAsync(filter);
        ViewBag.ReportData = logs;
        ViewBag.ReportTitle = $"Spending Log ({filter.StartDate:MM/dd/yyyy} - {filter.EndDate:MM/dd/yyyy})";
        ViewBag.GeneratedDate = DateTime.Now;
        return View(filter);
    }

    [HttpGet]
    public async Task<IActionResult> ExportSpendingLog(Domain.Objects.Medicine.ReportFilterObject filter)
    {
        var logs = await _medicineProvider.GetSpendingLogAsync(filter);
        var csv = new System.Text.StringBuilder();
        csv.AppendLine("Medicine,Supplier,Batch,Lot,Qty,Unit Cost,Total Cost,Received,Expiry");
        foreach (var i in logs)
        {
            csv.AppendLine($"{i.MedicineName},{i.Supplier},{i.BatchNumber},{i.LotNumber},{i.Quantity},{i.UnitCost},{i.TotalCost},{i.ReceivedDate:MM/dd/yyyy},{i.ExpiryDate:MM/dd/yyyy}");
        }
        var timestamp = DateTime.Now.ToString("yyyyMMdd_HHmmss");
        Response.Headers.Add("Content-Disposition", $"attachment; filename=SpendingLog_{timestamp}.csv");
        return Content(csv.ToString(), "text/csv");
    }

    [HttpPost]
    [Authorize(Roles = "Administrator")]
    public async Task<IActionResult> DeleteStock(string id)
    {
        if (string.IsNullOrWhiteSpace(id))
        {
            return Json(new { success = false, message = "Stock ID is required." });
        }

        var deletedBy = await _usersProvider.GetCurrentUserAsync(HttpContext.User);
        if (deletedBy == null)
        {
            return Json(new { success = false, message = "User not found." });
        }

        try
        {
            var stockId = ObjectId.Parse(id);
            var result = await _medicineService.RemoveStock(id, deletedBy);
            
            if (result)
            {
                // Log audit action
                await _auditLogService.LogActionAsync(
                    "Delete",
                    "MedicineStock",
                    stockId,
                    null,
                    System.Text.Json.JsonSerializer.Serialize(new { StockId = id }),
                    deletedBy,
                    GetClientIpAddress(),
                    GetUserAgent());

                return Json(new { success = true, message = "Stock record deleted successfully." });
            }
            else
            {
                return Json(new { success = false, message = "Failed to delete stock. It may not exist or may have already been deleted." });
            }
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, $"Error deleting stock {id}");
            return Json(new { success = false, message = $"Error: {ex.Message}" });
        }
    }

    [HttpGet]
    public IActionResult BalanceSummary()
    {
        var filter = new Domain.Objects.Medicine.ReportFilterObject
        {
            StartDate = DateTime.Now.AddDays(-30),
            EndDate = DateTime.Now
        };
        return View(filter);
    }

    [HttpPost]
    public async Task<IActionResult> BalanceSummary(Domain.Objects.Medicine.ReportFilterObject filter)
    {
        var summary = await _medicineProvider.GetBalanceSummaryAsync(filter);
        ViewBag.Summary = summary;
        ViewBag.ReportTitle = $"Balance Summary ({filter.StartDate:MM/dd/yyyy} - {filter.EndDate:MM/dd/yyyy})";
        ViewBag.GeneratedDate = DateTime.Now;
        return View(filter);
    }

    [HttpGet]
    public async Task<IActionResult> ExportBalanceSummary(Domain.Objects.Medicine.ReportFilterObject filter)
    {
        var s = await _medicineProvider.GetBalanceSummaryAsync(filter);
        var csv = new System.Text.StringBuilder();
        csv.AppendLine("Start Date,End Date,Total Purchases,Total Usage Value,Net");
        csv.AppendLine($"{s.StartDate:MM/dd/yyyy},{s.EndDate:MM/dd/yyyy},{s.TotalPurchases},{s.TotalUsageValue},{s.Net}");
        var timestamp = DateTime.Now.ToString("yyyyMMdd_HHmmss");
        Response.Headers.Add("Content-Disposition", $"attachment; filename=BalanceSummary_{timestamp}.csv");
        return Content(csv.ToString(), "text/csv");
    }

    [HttpGet]
    [Authorize(Roles = "Administrator")]
    public async Task<IActionResult> AuditLog(int page = 1, int itemsPerPage = 50, string? action = null, string? entity = null, string? entityId = null, string? userId = null, string? userName = null, string? startDate = null, string? endDate = null)
    {
        var filter = new Domain.Objects.Medicine.AuditLogFilterObject
        {
            Action = action,
            Entity = entity,
            EntityId = entityId,
            UserId = userId,
            UserName = userName
        };

        if (!string.IsNullOrWhiteSpace(startDate) && DateTime.TryParse(startDate, out var start))
        {
            filter.StartDate = start.Date;
        }
        // If not provided, don't filter by start date (show all)

        if (!string.IsNullOrWhiteSpace(endDate) && DateTime.TryParse(endDate, out var end))
        {
            filter.EndDate = end.Date.AddDays(1).AddTicks(-1);
        }
        // If not provided, don't filter by end date (show all)

        var paginated = await _medicineProvider.GetAuditLogsAsync(filter, page, itemsPerPage);

        ViewBag.Filter = filter;
        ViewBag.CurrentPage = page;
        ViewBag.ItemsPerPage = itemsPerPage;
        ViewBag.TotalRecords = paginated?.RecordsTotal ?? 0;

        return View(paginated);
    }

    [HttpGet]
    [Authorize(Roles = "Administrator")]
    public async Task<IActionResult> GetAuditLogUserNames(string search = "", int page = 1, int itemsPerPage = 50)
    {
        var uniqueUserNames = new HashSet<string>(StringComparer.OrdinalIgnoreCase);

        // Get usernames from audit logs
        var allLogs = await _auditLogRepository.GetAllAsync();
        var auditLogUserNames = allLogs
            .Where(x => !x.IsDeleted && !string.IsNullOrWhiteSpace(x.UserName))
            .Select(x => x.UserName!)
            .Distinct(StringComparer.OrdinalIgnoreCase)
            .ToList();

        foreach (var userName in auditLogUserNames)
        {
            uniqueUserNames.Add(userName);
        }

        // Also get usernames from users table to ensure all users are available
        // Get a large page to get all users (or use search if provided)
        var usersList = await _usersProvider.GetUsersListAsync(page: 1, itemsPerPage: 1000, search: search);
        if (usersList?.Data != null)
        {
            foreach (var user in usersList.Data)
            {
                if (!string.IsNullOrWhiteSpace(user.UserName))
                {
                    uniqueUserNames.Add(user.UserName);
                }
            }
        }

        // Convert to list and apply search filter if provided
        var userNamesList = uniqueUserNames.ToList();
        
        if (!string.IsNullOrWhiteSpace(search))
        {
            var searchLower = search.ToLower();
            userNamesList = userNamesList
                .Where(x => x.ToLower().Contains(searchLower))
                .ToList();
        }

        // Sort alphabetically
        userNamesList = userNamesList.OrderBy(x => x).ToList();

        // Paginate
        var totalRecords = userNamesList.Count;
        var skip = (page - 1) * itemsPerPage;
        var pagedUserNames = userNamesList.Skip(skip).Take(itemsPerPage).ToList();

        // Format for Select2
        var results = pagedUserNames.Select(x => new { id = x, text = x }).ToList();

        return Json(new
        {
            results = results,
            pagination = new
            {
                more = totalRecords > (page * itemsPerPage)
            }
        });
    }
}


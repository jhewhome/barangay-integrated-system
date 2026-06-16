using System.Linq.Expressions;
using AutoMapper;
using Microsoft.EntityFrameworkCore;
using Microsoft.Extensions.Logging;
using MongoDB.Bson;
using Project.Gawad.Core.Extensions;
using Project.Gawad.Core.Providers;
using Project.Gawad.Core.Repositories;
using Project.Gawad.Domain.Entities;
using Project.Gawad.Domain.Objects;
using Project.Gawad.Domain.Objects.Integration;
using Project.Gawad.Domain.Objects.Medicine;
using Project.Gawad.Domain.Enums.Medicine;

namespace Project.Gawad.Application.Providers;

public class MedicineProvider(
    IMedicineRepository medicineRepository,
    IMedicineStockRepository medicineStockRepository,
    IMedicineTransactionRepository medicineTransactionRepository,
    IMedicineAuditLogRepository auditLogRepository,
    IUsersProvider usersProvider,
    IMapper mapper,
    Microsoft.Extensions.Logging.ILogger<MedicineProvider> logger) : IMedicineProvider
{
    private readonly IMapper _mapper =
        mapper ?? throw new ArgumentNullException(nameof(mapper));

    private readonly IMedicineRepository _medicineRepository =
        medicineRepository ?? throw new ArgumentNullException(nameof(medicineRepository));

    private readonly IMedicineStockRepository _medicineStockRepository =
        medicineStockRepository ?? throw new ArgumentNullException(nameof(medicineStockRepository));

    private readonly IMedicineTransactionRepository _medicineTransactionRepository =
        medicineTransactionRepository ?? throw new ArgumentNullException(nameof(medicineTransactionRepository));

    private readonly IMedicineAuditLogRepository _auditLogRepository =
        auditLogRepository ?? throw new ArgumentNullException(nameof(auditLogRepository));

    private readonly IUsersProvider _usersProvider =
        usersProvider ?? throw new ArgumentNullException(nameof(usersProvider));

    private readonly Microsoft.Extensions.Logging.ILogger<MedicineProvider> _logger =
        logger ?? throw new ArgumentNullException(nameof(logger));

    public async Task<UpdateMedicineObject?> GetCreateUpdateMedicineObjectAsync(ObjectId medicineId)
    {
        var result = await _medicineRepository.GetByIdAsync(medicineId);

        if (result != null)
        {
            return _mapper.Map<Medicine, UpdateMedicineObject>(result);
        }

        return null;
    }

    public async Task<PaginatedRecords<MedicineListObject>> GetMedicinesListAsync(
        int page = 1, int itemsPerPage = 10,
        int sortColIndex = 0, string sortColDir = "asc", string? search = null,
        MedicineCategory? category = null, UnitOfMeasure? unitType = null, string? status = null)
    {
        var isAscending = sortColDir.Equals("asc", StringComparison.InvariantCultureIgnoreCase);

        // Load all non-deleted medicines, including inactive and those with zero stock
        var allMeds = (await _medicineRepository.GetAllAsync())
            .Where(m => !m.IsDeleted)
            .ToList();

        // Apply search filter
        if (!string.IsNullOrWhiteSpace(search))
        {
            var s = search.ToLower();
            allMeds = allMeds.Where(m =>
                (m.Name?.ToLower().Contains(s) ?? false) ||
                (m.Description?.ToLower().Contains(s) ?? false) ||
                (m.GenericName?.ToLower().Contains(s) ?? false)
            ).ToList();
        }

        // Apply category filter
        if (category.HasValue)
        {
            allMeds = allMeds.Where(m => m.Category == category.Value).ToList();
        }

        // Apply unit type filter
        if (unitType.HasValue)
        {
            allMeds = allMeds.Where(m => m.UnitOfMeasure == unitType.Value).ToList();
        }

        // Get all stocks expiring within 30 days for the expiring soon filter
        var expiringStocks = await _medicineStockRepository.GetExpiringStocksAsync(DateTime.Now.AddDays(30));

        // Map and compute stock for all medicines (needed for status filtering)
        var medicineList = new List<MedicineListObject>();
        foreach (var medicine in allMeds)
        {
            var totalStock = await _medicineStockRepository.GetTotalStockByMedicineIdAsync(medicine.Id!.Value);
            var medicineListObject = _mapper.Map<MedicineListObject>(medicine);
            medicineListObject.CurrentStock = totalStock;
            // IsLowStock: stock is at or below minimum, but not zero (zero is "out of stock")
            medicineListObject.IsLowStock = totalStock > 0 && totalStock <= medicine.MinimumStockLevel;
            // Determine latest supplier from most recent received stock
            var stocks = await _medicineStockRepository.GetStocksByMedicineIdAsync(medicine.Id.Value);
            var latest = stocks.OrderByDescending(s => s.ReceivedDate ?? DateTime.MinValue).FirstOrDefault();
            medicineListObject.Supplier = latest?.Supplier;
            // Compute total dispensed quantity (all time)
            var allTx = await _medicineTransactionRepository.GetTransactionsByMedicineIdAsync(medicine.Id.Value);
            var dispensedQty = allTx.Where(t => t.TransactionType == MedicineTransactionType.Dispensed)
                                    .Sum(t => t.Quantity);
            medicineListObject.TotalDispensed = dispensedQty;
            // Count expiring soon stock batches for this medicine
            medicineListObject.ExpiringSoonCount = expiringStocks.Count(s => s.MedicineId == medicine.Id.Value);
            medicineList.Add(medicineListObject);
        }

        // Apply status filter (after computing stock)
        if (!string.IsNullOrWhiteSpace(status))
        {
            medicineList = status.ToLower() switch
            {
                "active" => medicineList.Where(m => m.IsActive).ToList(),
                "inactive" => medicineList.Where(m => !m.IsActive).ToList(),
                "lowstock" => medicineList.Where(m => m.IsLowStock).ToList(),
                "outofstock" => medicineList.Where(m => m.CurrentStock == 0).ToList(),
                "instock" => medicineList.Where(m => !m.IsLowStock && m.CurrentStock > 0).ToList(),
                "expiringsoon" => medicineList.Where(m => m.ExpiringSoonCount > 0).ToList(),
                _ => medicineList
            };
        }

        // Sort
        medicineList = (sortColIndex switch
        {
            0 => isAscending ? medicineList.OrderBy(m => m.Name) : medicineList.OrderByDescending(m => m.Name),
            1 => isAscending ? medicineList.OrderBy(m => m.Category) : medicineList.OrderByDescending(m => m.Category),
            2 => isAscending ? medicineList.OrderBy(m => m.UnitOfMeasure) : medicineList.OrderByDescending(m => m.UnitOfMeasure),
            3 => isAscending ? medicineList.OrderBy(m => m.MinimumStockLevel) : medicineList.OrderByDescending(m => m.MinimumStockLevel),
            _ => isAscending ? medicineList.OrderBy(m => m.Name) : medicineList.OrderByDescending(m => m.Name)
        }).ToList();

        var recordsTotal = medicineList.Count;

        // Pagination
        var pageData = medicineList.Skip((page - 1) * itemsPerPage).Take(itemsPerPage).ToList();

        return new PaginatedRecords<MedicineListObject>
        {
            PageNumber = page,
            PageSize = itemsPerPage,
            RecordsTotal = recordsTotal,
            RecordsFiltered = recordsTotal,
            Data = pageData
        };
    }

    public async Task<MedicineDetailObject?> GetMedicineDetailObjectAsync(ObjectId medicineId)
    {
        var result = await _medicineRepository.GetByIdAsync(medicineId);

        if (result != null)
        {
            var detailObject = _mapper.Map<MedicineDetailObject>(result);

            var totalStock = await _medicineStockRepository.GetTotalStockByMedicineIdAsync(medicineId);
            detailObject.TotalStock = totalStock;
            detailObject.IsLowStock = totalStock <= result.MinimumStockLevel;

            var expiringStocks = await _medicineStockRepository.GetExpiringStocksAsync(DateTime.Now.AddDays(30));
            detailObject.ExpiringSoonCount = expiringStocks.Count(s => s.MedicineId == medicineId);

            var expiredStocks = await _medicineStockRepository.GetExpiredStocksAsync();
            detailObject.ExpiredCount = expiredStocks.Count(s => s.MedicineId == medicineId);

            return detailObject;
        }

        return null;
    }

    public async Task<PaginatedRecords<MedicineStockListObject>> GetMedicineStocksListAsync(
        ObjectId medicineId, int page = 1, int itemsPerPage = 10)
    {
        var stocks = await _medicineStockRepository.GetStocksByMedicineIdAsync(medicineId);
        var medicine = await _medicineRepository.GetByIdAsync(medicineId);

        var stockListObjects = stocks.Select(s =>
        {
            var stockObj = _mapper.Map<MedicineStockListObject>(s);
            stockObj.MedicineName = medicine?.Name ?? string.Empty;

            if (s.ExpiryDate.HasValue)
            {
                var daysUntilExpiry = (s.ExpiryDate.Value.Date - DateTime.Now.Date).Days;
                stockObj.IsExpiringSoon = daysUntilExpiry <= 30 && daysUntilExpiry > 0;
                stockObj.IsExpired = daysUntilExpiry < 0;
                
                if (daysUntilExpiry >= 0)
                {
                    stockObj.DaysUntilExpiry = daysUntilExpiry;
                }
                else
                {
                    stockObj.DaysSinceExpiry = Math.Abs(daysUntilExpiry);
                }
            }

            return stockObj;
        }).ToList();

        var totalRecords = stockListObjects.Count;
        var paginatedData = stockListObjects
            .Skip((page - 1) * itemsPerPage)
            .Take(itemsPerPage)
            .ToList();

        return new PaginatedRecords<MedicineStockListObject>
        {
            PageNumber = page,
            PageSize = itemsPerPage,
            RecordsTotal = totalRecords,
            RecordsFiltered = totalRecords,
            Data = paginatedData
        };
    }

    public async Task<PaginatedRecords<MedicineTransactionListObject>> GetMedicineTransactionsListAsync(
        ObjectId? medicineId = null, 
        int page = 1, 
        int itemsPerPage = 10,
        string? search = null,
        MedicineTransactionType? transactionType = null,
        string? recipientName = null,
        DateTime? startDate = null,
        DateTime? endDate = null,
        ObjectId? createdByUserId = null)
    {
        IEnumerable<MedicineTransaction> transactions;

        if (medicineId.HasValue)
        {
            transactions = await _medicineTransactionRepository.GetTransactionsByMedicineIdAsync(medicineId.Value);
        }
        else
        {
            var allTransactions = await _medicineTransactionRepository.GetAllAsync();
            // Filter out deleted transactions
            transactions = allTransactions.Where(t => !t.IsDeleted).OrderByDescending(t => t.TransactionDate);
        }

        // Filter by user who created the transaction (for Staff role)
        if (createdByUserId.HasValue)
        {
            transactions = transactions.Where(t => t.CreatedById.HasValue && t.CreatedById.Value == createdByUserId.Value);
        }

        var transactionListObjects = new List<MedicineTransactionListObject>();

        foreach (var transaction in transactions)
        {
            // Skip deleted transactions
            if (transaction.IsDeleted)
            {
                continue;
            }

            // Skip transactions for deleted medicines
            Medicine? medicine = null;
            if (transaction.Medicine != null)
            {
                medicine = transaction.Medicine;
            }
            else if (transaction.MedicineId != ObjectId.Empty)
            {
                medicine = await _medicineRepository.GetByIdAsync(transaction.MedicineId);
            }

            // Hide transactions for deleted medicines
            if (medicine == null || medicine.IsDeleted)
            {
                continue;
            }

            var transactionObj = _mapper.Map<MedicineTransactionListObject>(transaction);
            transactionObj.MedicineName = medicine.Name;
            transactionObj.TransactionTypeName = transaction.TransactionType.ToString();
            transactionObj.CreatedDate = transaction.CreatedDate;

            // Get user info who created this transaction
            if (transaction.CreatedById.HasValue)
            {
                try
                {
                    var user = await _usersProvider.GetApplicationUserObjectByIdAsync(transaction.CreatedById.Value);
                    if (user != null)
                    {
                        transactionObj.CreatedByName = !string.IsNullOrWhiteSpace(user.FullName) 
                            ? user.FullName 
                            : $"{user.FirstName} {user.LastName}".Trim();
                        if (string.IsNullOrWhiteSpace(transactionObj.CreatedByName))
                        {
                            transactionObj.CreatedByName = user.UserName;
                        }
                        transactionObj.CreatedByRole = user.Role;
                    }
                }
                catch
                {
                    // User might have been deleted
                    transactionObj.CreatedByName = "Unknown User";
                }
            }

            // Apply search filters
            bool matchesFilter = true;

            // Search filter (medicine name or recipient name)
            if (!string.IsNullOrWhiteSpace(search))
            {
                var searchLower = search.ToLower();
                matchesFilter = medicine.Name.ToLower().Contains(searchLower) ||
                               (transactionObj.RecipientName != null && transactionObj.RecipientName.ToLower().Contains(searchLower));
            }

            // Transaction type filter
            if (transactionType.HasValue && transaction.TransactionType != transactionType.Value)
            {
                matchesFilter = false;
            }

            // Recipient name filter
            if (!string.IsNullOrWhiteSpace(recipientName))
            {
                if (string.IsNullOrWhiteSpace(transactionObj.RecipientName) || 
                    !transactionObj.RecipientName.ToLower().Contains(recipientName.ToLower()))
                {
                    matchesFilter = false;
                }
            }

            // Date range filter
            if (startDate.HasValue && transaction.TransactionDate < startDate.Value.Date)
            {
                matchesFilter = false;
            }
            if (endDate.HasValue && transaction.TransactionDate > endDate.Value.Date.AddDays(1).AddSeconds(-1))
            {
                matchesFilter = false;
            }

            if (matchesFilter)
            {
                transactionListObjects.Add(transactionObj);
            }
        }

        var totalRecords = transactionListObjects.Count;
        var paginatedData = transactionListObjects
            .Skip((page - 1) * itemsPerPage)
            .Take(itemsPerPage)
            .ToList();

        return new PaginatedRecords<MedicineTransactionListObject>
        {
            PageNumber = page,
            PageSize = itemsPerPage,
            RecordsTotal = totalRecords,
            RecordsFiltered = totalRecords,
            Data = paginatedData
        };
    }

    public async Task<IEnumerable<MedicineListObject>> GetLowStockMedicinesAsync()
    {
        var lowStockMedicines = await _medicineRepository.GetLowStockMedicinesAsync();
        var medicineList = new List<MedicineListObject>();

        foreach (var medicine in lowStockMedicines)
        {
            var totalStock = await _medicineStockRepository.GetTotalStockByMedicineIdAsync(
                medicine.Id!.Value);

            var medicineListObject = _mapper.Map<MedicineListObject>(medicine);
            medicineListObject.CurrentStock = totalStock;
            medicineListObject.IsLowStock = true;
            medicineList.Add(medicineListObject);
        }

        return medicineList;
    }

    public async Task<IEnumerable<MedicineStockListObject>> GetExpiringStocksAsync(int days = 30)
    {
        var expiringStocks = await _medicineStockRepository.GetExpiringStocksAsync(
            DateTime.Now.AddDays(days));

        var stockListObjects = new List<MedicineStockListObject>();

        foreach (var stock in expiringStocks)
        {
            var medicine = await _medicineRepository.GetByIdAsync(stock.MedicineId);
            var stockObj = _mapper.Map<MedicineStockListObject>(stock);
            stockObj.MedicineName = medicine?.Name ?? string.Empty;
            stockObj.IsExpiringSoon = true;

            // Calculate days until expiry
            if (stock.ExpiryDate.HasValue)
            {
                var daysUntilExpiry = (stock.ExpiryDate.Value.Date - DateTime.Now.Date).Days;
                stockObj.DaysUntilExpiry = daysUntilExpiry;
            }

            stockListObjects.Add(stockObj);
        }

        return stockListObjects;
    }

    public async Task<IEnumerable<MedicineStockListObject>> GetExpiredStocksAsync()
    {
        var expiredStocks = await _medicineStockRepository.GetExpiredStocksAsync();

        var stockListObjects = new List<MedicineStockListObject>();

        foreach (var stock in expiredStocks)
        {
            var medicine = await _medicineRepository.GetByIdAsync(stock.MedicineId);
            var stockObj = _mapper.Map<MedicineStockListObject>(stock);
            stockObj.MedicineName = medicine?.Name ?? string.Empty;
            stockObj.IsExpired = true;

            // Calculate days since expiry
            if (stock.ExpiryDate.HasValue)
            {
                var daysSinceExpiry = (DateTime.Now.Date - stock.ExpiryDate.Value.Date).Days;
                stockObj.DaysSinceExpiry = daysSinceExpiry;
            }

            stockListObjects.Add(stockObj);
        }

        return stockListObjects;
    }

    public async Task<IEnumerable<MedicineStockStatusReportObject>> GetStockStatusReportAsync(ReportFilterObject? filter = null)
    {
        var medicines = filter == null || string.IsNullOrEmpty(filter.MedicineId)
            ? await _medicineRepository.GetActiveMedicinesAsync()
            : new[] { await _medicineRepository.GetByIdAsync(MongoDB.Bson.ObjectId.Parse(filter.MedicineId)) }
                .Where(m => m != null && !m.IsDeleted);

        var reportData = new List<MedicineStockStatusReportObject>();

        foreach (var medicine in medicines)
        {
            if (medicine == null || medicine.IsDeleted) continue;
            if (!filter?.IncludeInactive == true && !medicine.IsActive) continue;

            var totalStock = await _medicineStockRepository.GetTotalStockByMedicineIdAsync(medicine.Id!.Value);
            var expiringStocks = await _medicineStockRepository.GetExpiringStocksAsync(DateTime.Now.AddDays(30));
            var expiredStocks = await _medicineStockRepository.GetExpiredStocksAsync();

            var expiringCount = expiringStocks.Count(s => s.MedicineId == medicine.Id!.Value);
            var expiredCount = expiredStocks.Count(s => s.MedicineId == medicine.Id!.Value);

            var reportItem = new MedicineStockStatusReportObject
            {
                MedicineName = medicine.Name,
                Category = medicine.Category.GetEnumDisplayName(),
                CurrentStock = totalStock,
                MinimumStockLevel = medicine.MinimumStockLevel,
                IsLowStock = totalStock <= medicine.MinimumStockLevel,
                ExpiringSoonCount = expiringCount,
                ExpiredCount = expiredCount,
                Status = !medicine.IsActive ? "Inactive" 
                    : expiredCount > 0 ? "Has Expired Items"
                    : totalStock <= medicine.MinimumStockLevel ? "Low Stock"
                    : expiringCount > 0 ? "Expiring Soon"
                    : "Normal",
                UnitPrice = medicine.UnitPrice,
                TotalValue = medicine.UnitPrice.HasValue ? medicine.UnitPrice.Value * totalStock : null,
                Supplier = (await _medicineStockRepository.GetStocksByMedicineIdAsync(medicine.Id.Value))
                    .OrderByDescending(s => s.ReceivedDate ?? DateTime.MinValue)
                    .FirstOrDefault()?.Supplier
            };

            // Apply category filter if specified
            if (filter != null && !string.IsNullOrEmpty(filter.Category))
            {
                if (reportItem.Category.Equals(filter.Category, StringComparison.OrdinalIgnoreCase))
                {
                    reportData.Add(reportItem);
                }
            }
            else
            {
                reportData.Add(reportItem);
            }
        }

        return reportData.OrderBy(r => r.MedicineName);
    }

    public async Task<IEnumerable<MedicineUsageReportObject>> GetUsageReportAsync(ReportFilterObject filter)
    {
        if (filter.StartDate == null || filter.EndDate == null)
        {
            return Enumerable.Empty<MedicineUsageReportObject>();
        }

        var transactions = await _medicineTransactionRepository.GetTransactionsByDateRangeAsync(
            filter.StartDate.Value, filter.EndDate.Value);

        // Filter by medicine if specified
        if (!string.IsNullOrEmpty(filter.MedicineId))
        {
            var medicineId = MongoDB.Bson.ObjectId.Parse(filter.MedicineId);
            transactions = transactions.Where(t => t.MedicineId == medicineId);
        }

        // Only get dispensed transactions for usage report (exclude deleted transactions)
        var dispensedTransactions = transactions
            .Where(t => !t.IsDeleted && t.TransactionType == Domain.Enums.Medicine.MedicineTransactionType.Dispensed)
            .ToList();

        var usageReport = dispensedTransactions
            .GroupBy(t => t.MedicineId)
            .Select(async group =>
            {
                var medicine = await _medicineRepository.GetByIdAsync(group.Key);
                if (medicine == null || medicine.IsDeleted) return null;

                var groupTransactions = group.ToList();
                var recipients = groupTransactions
                    .Where(t => !string.IsNullOrEmpty(t.RecipientName))
                    .GroupBy(t => t.RecipientName)
                    .OrderByDescending(g => g.Count())
                    .FirstOrDefault();

                return new MedicineUsageReportObject
                {
                    MedicineName = medicine.Name,
                    Category = medicine.Category.GetEnumDisplayName(),
                    TotalQuantityDispensed = groupTransactions.Sum(t => t.Quantity),
                    NumberOfTransactions = groupTransactions.Count,
                    TotalValue = groupTransactions
                        .Where(t => t.TotalAmount.HasValue)
                        .Sum(t => t.TotalAmount!.Value),
                    FirstDispensedDate = groupTransactions.Min(t => t.TransactionDate),
                    LastDispensedDate = groupTransactions.Max(t => t.TransactionDate),
                    MostCommonRecipient = recipients?.Key ?? "N/A"
                };
            })
            .ToList();

        var results = new List<MedicineUsageReportObject>();
        foreach (var task in usageReport)
        {
            var result = await task;
            if (result != null)
            {
                // Apply category filter if specified
                if (!string.IsNullOrEmpty(filter.Category))
                {
                    if (result.Category.Equals(filter.Category, StringComparison.OrdinalIgnoreCase))
                    {
                        results.Add(result);
                    }
                }
                else
                {
                    results.Add(result);
                }
            }
        }

        return results.OrderByDescending(r => r.TotalQuantityDispensed);
    }

    public async Task<IEnumerable<MedicineSpendingLogObject>> GetSpendingLogAsync(ReportFilterObject filter)
    {
        // Normalize dates: start at beginning of day, end at end of day to include full day range
        var start = filter.StartDate?.Date ?? DateTime.MinValue;
        DateTime end;
        if (filter.EndDate.HasValue)
        {
            var endDate = filter.EndDate.Value.Date;
            // If the end date is at or beyond max, clamp to DateTime.MaxValue instead of calling AddDays(1)
            end = endDate >= DateTime.MaxValue.Date
                ? DateTime.MaxValue
                : endDate.AddDays(1).AddTicks(-1);
        }
        else
        {
            // If no end date supplied, default to "up to now"
            end = DateTime.Now.Date.AddDays(1).AddTicks(-1);
        }

        var stocks = await _medicineStockRepository.GetAllAsync();
        var rangeStocks = stocks.Where(s => !s.IsDeleted && s.ReceivedDate.HasValue && s.ReceivedDate.Value >= start && s.ReceivedDate.Value <= end);

        // Get all StockIn transactions to retrieve TotalAmount
        var allTransactions = await _medicineTransactionRepository.GetAllAsync(0, 0);
        var stockInTransactions = allTransactions
            .Where(t => !t.IsDeleted && 
                       t.TransactionType == MedicineTransactionType.StockIn &&
                       t.MedicineStockId.HasValue)
            .ToDictionary(t => t.MedicineStockId!.Value, t => t);

        var results = new List<MedicineSpendingLogObject>();
        foreach (var stock in rangeStocks)
        {
            var med = await _medicineRepository.GetByIdAsync(stock.MedicineId);
            if (med == null || med.IsDeleted) continue;

            // Get TotalAmount from the StockIn transaction (this is the amount shown in Total Price Summary)
            decimal? totalCost = null;
            if (stock.Id.HasValue && stockInTransactions.TryGetValue(stock.Id.Value, out var transaction))
            {
                // Use the TotalAmount from the transaction - this matches what was shown in the Add Stock page
                totalCost = transaction.TotalAmount;
            }
            
            // Fallback: If no transaction found or TotalAmount is null, calculate it
            if (!totalCost.HasValue && stock.CostPerUnit.HasValue)
            {
                // Try to parse INPUT_UNIT from Notes field (format: "INPUT_UNIT:boxes:2" or "INPUT_UNIT:bottles:5")
                decimal unitCount = 0;
                bool foundUnitInfo = false;

                if (!string.IsNullOrWhiteSpace(stock.Notes))
                {
                    var notes = stock.Notes.Trim();
                    if (notes.StartsWith("INPUT_UNIT:", StringComparison.OrdinalIgnoreCase))
                    {
                        try
                        {
                            // Remove "INPUT_UNIT:" prefix (11 characters)
                            var unitInfo = notes.Substring(11);
                            
                            // Split by semicolon in case there's additional notes
                            var unitInfoPart = unitInfo.Split(';')[0].Trim();
                            
                            // Split by colon to get unitType:unitCount
                            var parts = unitInfoPart.Split(':');
                            
                            if (parts.Length >= 2)
                            {
                                var unitType = parts[0].Trim().ToLower();
                                var unitCountStr = parts[1].Trim();
                                
                                // Try to parse the unit count
                                if (decimal.TryParse(unitCountStr, out var parsedUnitCount) && parsedUnitCount > 0)
                                {
                                    if (unitType == "boxes" || unitType == "box" || 
                                        unitType == "bottles" || unitType == "bottle")
                                    {
                                        // Formula: TotalCost = UnitPrice × Number of Boxes/Bottles
                                        unitCount = parsedUnitCount;
                                        foundUnitInfo = true;
                                        totalCost = stock.CostPerUnit.Value * unitCount;
                                    }
                                    else if (unitType == "pieces" || unitType == "piece")
                                    {
                                        // For pieces, use the quantity directly
                                        totalCost = stock.CostPerUnit.Value * stock.Quantity;
                                        foundUnitInfo = true;
                                    }
                                }
                            }
                        }
                        catch (Exception ex)
                        {
                            logger.LogWarning(ex, $"Error parsing INPUT_UNIT from Notes for stock {stock.Id}: {stock.Notes}");
                        }
                    }
                }

                // Fallback: If no INPUT_UNIT info found, use quantity (for backward compatibility)
                if (!foundUnitInfo)
                {
                    // For medicines measured in Box or Bottle, try to estimate
                    if (med.UnitOfMeasure == Domain.Enums.Medicine.UnitOfMeasure.Box)
                    {
                        // Try to estimate number of boxes from quantity
                        decimal estimatedBoxes = 1;
                        var commonBoxSizes = new[] { 10m, 20m, 30m, 50m, 100m };
                        foreach (var boxSize in commonBoxSizes)
                        {
                            if (stock.Quantity >= boxSize && stock.Quantity % boxSize == 0)
                            {
                                estimatedBoxes = stock.Quantity / boxSize;
                                break;
                            }
                        }
                        totalCost = stock.CostPerUnit.Value * estimatedBoxes;
                    }
                    else if (med.UnitOfMeasure == Domain.Enums.Medicine.UnitOfMeasure.Bottle)
                    {
                        // Try to estimate number of bottles from quantity
                        decimal estimatedBottles = 1;
                        var commonBottleSizes = new[] { 10m, 20m, 30m, 50m, 100m };
                        foreach (var bottleSize in commonBottleSizes)
                        {
                            if (stock.Quantity >= bottleSize && stock.Quantity % bottleSize == 0)
                            {
                                estimatedBottles = stock.Quantity / bottleSize;
                                break;
                            }
                        }
                        totalCost = stock.CostPerUnit.Value * estimatedBottles;
                    }
                    else
                    {
                        // For other unit types (tablet, capsule, etc.), unit price is per piece
                        totalCost = stock.CostPerUnit.Value * stock.Quantity;
                    }
                }
            }

            var item = new MedicineSpendingLogObject
            {
                StockId = stock.Id,
                MedicineName = med.Name,
                Supplier = stock.Supplier,
                BatchNumber = stock.BatchNumber,
                LotNumber = stock.LotNumber,
                Quantity = stock.Quantity,
                UnitCost = stock.CostPerUnit,
                TotalCost = totalCost,
                ReceivedDate = stock.ReceivedDate,
                ExpiryDate = stock.ExpiryDate
            };
            results.Add(item);
        }

        return results.OrderByDescending(r => r.ReceivedDate);
    }

    public async Task<MedicineBalanceSummaryObject> GetBalanceSummaryAsync(ReportFilterObject filter)
    {
        // Normalize dates: start at beginning of day, end at end of day to include full day range (same as Spending Log)
        var start = filter.StartDate?.Date ?? DateTime.MinValue;
        DateTime end;
        if (filter.EndDate.HasValue)
        {
            var endDate = filter.EndDate.Value.Date;
            // If the end date is at or beyond max, clamp to DateTime.MaxValue instead of calling AddDays(1)
            end = endDate >= DateTime.MaxValue.Date
                ? DateTime.MaxValue
                : endDate.AddDays(1).AddTicks(-1);
        }
        else
        {
            // If no end date supplied, default to "up to now"
            end = DateTime.Now.Date.AddDays(1).AddTicks(-1);
        }

        // Get only non-deleted (existing) medicines
        var activeMedicines = (await _medicineRepository.GetAllAsync())
            .Where(m => !m.IsDeleted)
            .Select(m => m.Id!.Value)
            .ToHashSet();

        // Get all stocks from active medicines to calculate total received
        var allStocks = await _medicineStockRepository.GetAllAsync();
        // Filter stocks by ReceivedDate (same as Spending Log) - this ensures consistency
        var activeStocks = allStocks.Where(s => 
            !s.IsDeleted && 
            activeMedicines.Contains(s.MedicineId) &&
            s.ReceivedDate.HasValue &&
            s.ReceivedDate.Value >= start &&
            s.ReceivedDate.Value <= end);
        
        // Total stock received (pieces) - sum of all stock quantities added within date range
        var totalStockReceivedCount = activeStocks.Sum(s => s.Quantity);
        
        // Calculate total cost using the same logic as GetSpendingLogAsync to ensure consistency
        // Get all StockIn transactions to retrieve TotalAmount
        var allTransactions = await _medicineTransactionRepository.GetAllAsync(0, 0);
        var stockInTransactions = allTransactions
            .Where(t => !t.IsDeleted && 
                       t.TransactionType == MedicineTransactionType.StockIn &&
                       t.MedicineStockId.HasValue)
            .ToDictionary(t => t.MedicineStockId!.Value, t => t);
        
        // Calculate total cost the same way as Spending Log
        decimal totalStockReceivedCost = 0;
        foreach (var stock in activeStocks)
        {
            var med = await _medicineRepository.GetByIdAsync(stock.MedicineId);
            if (med == null || med.IsDeleted) continue;

            // Get TotalAmount from the StockIn transaction (same as Spending Log)
            decimal? totalCost = null;
            if (stock.Id.HasValue && stockInTransactions.TryGetValue(stock.Id.Value, out var transaction))
            {
                // Use the TotalAmount from the transaction - this matches what was shown in the Add Stock page
                totalCost = transaction.TotalAmount;
            }
            
            // Fallback: If no transaction found or TotalAmount is null, calculate it (same logic as Spending Log)
            if (!totalCost.HasValue && stock.CostPerUnit.HasValue)
            {
                // Try to parse INPUT_UNIT from Notes field (format: "INPUT_UNIT:boxes:2" or "INPUT_UNIT:bottles:5")
                decimal unitCount = 0;
                bool foundUnitInfo = false;

                if (!string.IsNullOrWhiteSpace(stock.Notes))
                {
                    var notes = stock.Notes.Trim();
                    if (notes.StartsWith("INPUT_UNIT:", StringComparison.OrdinalIgnoreCase))
                    {
                        try
                        {
                            // Remove "INPUT_UNIT:" prefix (11 characters)
                            var unitInfo = notes.Substring(11);
                            
                            // Split by semicolon in case there's additional notes
                            var unitInfoPart = unitInfo.Split(';')[0].Trim();
                            
                            // Split by colon to get unitType:unitCount
                            var parts = unitInfoPart.Split(':');
                            
                            if (parts.Length >= 2)
                            {
                                var unitType = parts[0].Trim().ToLower();
                                var unitCountStr = parts[1].Trim();
                                
                                // Try to parse the unit count
                                if (decimal.TryParse(unitCountStr, out var parsedUnitCount) && parsedUnitCount > 0)
                                {
                                    if (unitType == "boxes" || unitType == "box" || 
                                        unitType == "bottles" || unitType == "bottle")
                                    {
                                        // Formula: TotalCost = UnitPrice × Number of Boxes/Bottles
                                        unitCount = parsedUnitCount;
                                        foundUnitInfo = true;
                                        totalCost = stock.CostPerUnit.Value * unitCount;
                                    }
                                    else if (unitType == "pieces" || unitType == "piece")
                                    {
                                        // For pieces, use the quantity directly
                                        totalCost = stock.CostPerUnit.Value * stock.Quantity;
                                        foundUnitInfo = true;
                                    }
                                }
                            }
                        }
                        catch (Exception ex)
                        {
                            logger.LogWarning(ex, $"Error parsing INPUT_UNIT from Notes for stock {stock.Id}: {stock.Notes}");
                        }
                    }
                }

                // Fallback: If no INPUT_UNIT info found, use quantity (for backward compatibility)
                if (!foundUnitInfo)
                {
                    // For medicines measured in Box or Bottle, try to estimate
                    if (med.UnitOfMeasure == Domain.Enums.Medicine.UnitOfMeasure.Box)
                    {
                        // Try to estimate number of boxes from quantity
                        decimal estimatedBoxes = 1;
                        var commonBoxSizes = new[] { 10m, 20m, 30m, 50m, 100m };
                        foreach (var boxSize in commonBoxSizes)
                        {
                            if (stock.Quantity >= boxSize && stock.Quantity % boxSize == 0)
                            {
                                estimatedBoxes = stock.Quantity / boxSize;
                                break;
                            }
                        }
                        totalCost = stock.CostPerUnit.Value * estimatedBoxes;
                    }
                    else if (med.UnitOfMeasure == Domain.Enums.Medicine.UnitOfMeasure.Bottle)
                    {
                        // Try to estimate number of bottles from quantity
                        decimal estimatedBottles = 1;
                        var commonBottleSizes = new[] { 10m, 20m, 30m, 50m, 100m };
                        foreach (var bottleSize in commonBottleSizes)
                        {
                            if (stock.Quantity >= bottleSize && stock.Quantity % bottleSize == 0)
                            {
                                estimatedBottles = stock.Quantity / bottleSize;
                                break;
                            }
                        }
                        totalCost = stock.CostPerUnit.Value * estimatedBottles;
                    }
                    else
                    {
                        // For other unit types (tablet, capsule, etc.), unit price is per piece
                        totalCost = stock.CostPerUnit.Value * stock.Quantity;
                    }
                }
            }
            
            if (totalCost.HasValue)
            {
                totalStockReceivedCost += totalCost.Value;
            }
        }

        // Dispensed transactions - filter by date range and only from existing (non-deleted) medicines
        var dispensedTransactions = await _medicineTransactionRepository.GetTransactionsByDateRangeAsync(start, end);
        var dispensed = dispensedTransactions.Where(t => 
            !t.IsDeleted &&
            t.TransactionType == Domain.Enums.Medicine.MedicineTransactionType.Dispensed &&
            activeMedicines.Contains(t.MedicineId));
        
        // Total count of pieces dispensed
        var totalDispensedCount = dispensed.Sum(t => t.Quantity);
        
        // Total cost/value of dispensed medicines (from TotalAmount field in transactions)
        var totalDispensedCost = dispensed.Sum(t => t.TotalAmount ?? 0);

        // Calculate total remaining stock (sum of current stock for all active medicines)
        decimal totalRemainingStock = 0;
        foreach (var medicineId in activeMedicines)
        {
            var currentStock = await _medicineStockRepository.GetTotalStockByMedicineIdAsync(medicineId);
            totalRemainingStock += currentStock;
        }

        return new MedicineBalanceSummaryObject
        {
            StartDate = start,
            EndDate = end,
            TotalStockReceivedCount = totalStockReceivedCount, // Total count of pieces received (from active stocks)
            TotalPurchases = totalStockReceivedCost, // Total cost of purchases (calculated using same logic as Spending Log)
            TotalUsageValue = totalDispensedCost, // Total cost of dispensed medicines (from Dispensed transactions)
            TotalDispensedCount = totalDispensedCount,
            TotalRemainingStock = totalRemainingStock
        };
    }

    public async Task<IEnumerable<MedicineTransactionListObject>> GetDispensedLogAsync(ReportFilterObject filter)
    {
        // Normalize dates from date-only inputs: include the full end day
        var start = (filter.StartDate?.Date) ?? DateTime.MinValue.Date;
        DateTime end;
        if (filter.EndDate.HasValue)
        {
            var endDate = filter.EndDate.Value.Date;
            // If the end date is at or beyond max, clamp to DateTime.MaxValue instead of calling AddDays(1)
            end = endDate >= DateTime.MaxValue.Date
                ? DateTime.MaxValue
                : endDate.AddDays(1).AddTicks(-1);
        }
        else
        {
            // If no end date supplied, default to "up to now"
            end = DateTime.Now.Date.AddDays(1).AddTicks(-1);
        }
        var tx = await _medicineTransactionRepository.GetTransactionsByDateRangeAsync(start, end);
        var dispensed = tx.Where(t => t.TransactionType == Domain.Enums.Medicine.MedicineTransactionType.Dispensed);

        if (!string.IsNullOrEmpty(filter.MedicineId))
        {
            var mid = ObjectId.Parse(filter.MedicineId);
            dispensed = dispensed.Where(t => t.MedicineId == mid);
        }

        // Filter by user who created the transaction (for Staff role)
        if (!string.IsNullOrEmpty(filter.CreatedByUserId) && ObjectId.TryParse(filter.CreatedByUserId, out var userId))
        {
            dispensed = dispensed.Where(t => t.CreatedById.HasValue && t.CreatedById.Value == userId);
        }

        var list = new List<MedicineTransactionListObject>();
        foreach (var t in dispensed.OrderByDescending(d => d.TransactionDate))
        {
            // Skip deleted transactions
            if (t.IsDeleted)
            {
                continue;
            }

            // Skip transactions for deleted medicines
            var med = await _medicineRepository.GetByIdAsync(t.MedicineId);
            if (med == null || med.IsDeleted)
            {
                continue;
            }

            var item = _mapper.Map<MedicineTransactionListObject>(t);
            item.MedicineName = med.Name;
            item.TransactionTypeName = t.TransactionType.ToString();
            list.Add(item);
        }
        return list;
    }

    public async Task<MedicineTransactionListObject?> GetTransactionListObjectByIdAsync(ObjectId id)
    {
        var t = await _medicineTransactionRepository.GetByIdAsync(id);
        if (t == null || t.IsDeleted) return null;
        var item = _mapper.Map<MedicineTransactionListObject>(t);
        if (string.IsNullOrEmpty(item.MedicineName))
        {
            var med = await _medicineRepository.GetByIdAsync(t.MedicineId);
            item.MedicineName = med?.Name ?? "Unknown";
        }
        item.TransactionTypeName = t.TransactionType.ToString();
        return item;
    }

    public async Task<PaginatedRecords<AuditLogListObject>> GetAuditLogsAsync(AuditLogFilterObject filter, int page = 1, int itemsPerPage = 50)
    {
        try
        {
            // Get all audit logs - use GetAllAsync for simplicity
            var allLogs = await _auditLogRepository.GetAllAsync();
            var nonDeletedLogs = allLogs.Where(x => !x.IsDeleted).ToList();

            // Log for debugging
            logger.LogInformation("GetAuditLogsAsync: Found {Count} total audit logs in database (non-deleted)", nonDeletedLogs.Count);

            // Apply filters
            var filteredLogs = nonDeletedLogs.AsEnumerable();

            // Simple date filtering - compare dates directly without timezone conversion for now
            if (filter.StartDate.HasValue)
            {
                var startDate = filter.StartDate.Value.Date;
                filteredLogs = filteredLogs.Where(x => x.CreatedDate.Date >= startDate);
            }

            if (filter.EndDate.HasValue)
            {
                var endDate = filter.EndDate.Value.Date;
                filteredLogs = filteredLogs.Where(x => x.CreatedDate.Date <= endDate);
            }

            if (!string.IsNullOrWhiteSpace(filter.Action))
            {
                filteredLogs = filteredLogs.Where(x => x.Action == filter.Action);
            }

            if (!string.IsNullOrWhiteSpace(filter.Entity))
            {
                filteredLogs = filteredLogs.Where(x => x.Entity == filter.Entity);
            }

            if (!string.IsNullOrWhiteSpace(filter.EntityId) && ObjectId.TryParse(filter.EntityId, out var entityId))
            {
                filteredLogs = filteredLogs.Where(x => x.EntityId == entityId);
            }

            if (!string.IsNullOrWhiteSpace(filter.UserId) && ObjectId.TryParse(filter.UserId, out var userId))
            {
                filteredLogs = filteredLogs.Where(x => x.CreatedById == userId);
            }

            if (!string.IsNullOrWhiteSpace(filter.UserName))
            {
                var userNameLower = filter.UserName.ToLower();
                filteredLogs = filteredLogs.Where(x => !string.IsNullOrWhiteSpace(x.UserName) && x.UserName.ToLower().Contains(userNameLower));
            }

            // Convert to list for counting and ordering
            var filteredList = filteredLogs.ToList();
            
            // Log for debugging
            logger.LogInformation("GetAuditLogsAsync: After filtering, found {Count} audit logs. Filters: Action={Action}, Entity={Entity}, UserName={UserName}", 
                filteredList.Count, filter.Action ?? "null", filter.Entity ?? "null", filter.UserName ?? "null");
            
            // Get total count
            var totalRecords = filteredList.Count;
            
            // Order, skip, and take
            var skip = (page - 1) * itemsPerPage;
            var logs = filteredList
                .OrderByDescending(x => x.CreatedDate)
                .Skip(skip)
                .Take(itemsPerPage)
                .ToList();

            var result = new List<AuditLogListObject>();
            foreach (var log in logs)
            {
                var item = _mapper.Map<AuditLogListObject>(log);
                
                // Get user role if available
                if (log.CreatedById.HasValue)
                {
                    try
                    {
                        var user = await _usersProvider.GetApplicationUserObjectByIdAsync(log.CreatedById.Value);
                        if (user != null)
                        {
                            item.UserRole = user.Role ?? "N/A";
                        }
                    }
                    catch (Exception ex)
                    {
                        logger.LogWarning(ex, "Failed to get user role for user ID: {UserId}", log.CreatedById.Value);
                        item.UserRole = "N/A";
                    }
                }

                result.Add(item);
            }

            return new PaginatedRecords<AuditLogListObject>
            {
                Data = result,
                RecordsTotal = totalRecords,
                RecordsFiltered = totalRecords
            };
        }
        catch (Exception ex)
        {
            // Log error
            logger.LogError(ex, "Error in GetAuditLogsAsync: {Message}. StackTrace: {StackTrace}", ex.Message, ex.StackTrace);
            return new PaginatedRecords<AuditLogListObject>
            {
                Data = new List<AuditLogListObject>(),
                RecordsTotal = 0,
                RecordsFiltered = 0
            };
        }
    }

    public async Task<IEnumerable<GawadMedicineIntegrationDto>> GetMedicinesIntegrationExportAsync()
    {
        var allMeds = (await _medicineRepository.GetAllAsync())
            .Where(m => !m.IsDeleted && m.IsActive)
            .OrderBy(m => m.Name, StringComparer.OrdinalIgnoreCase)
            .ToList();

        var result = new List<GawadMedicineIntegrationDto>();
        foreach (var medicine in allMeds)
        {
            if (medicine.Id is null)
            {
                continue;
            }

            var totalStock = await _medicineStockRepository.GetTotalStockByMedicineIdAsync(medicine.Id.Value);
            result.Add(new GawadMedicineIntegrationDto
            {
                Id = medicine.Id.ToString()!,
                Name = medicine.Name,
                GenericName = medicine.GenericName,
                UnitOfMeasure = medicine.UnitOfMeasure.ToString(),
                CurrentStock = totalStock,
                MinimumStockLevel = medicine.MinimumStockLevel,
                IsLowStock = totalStock > 0 && totalStock <= medicine.MinimumStockLevel,
                IsOutOfStock = totalStock <= 0,
                IsActive = medicine.IsActive,
            });
        }

        return result;
    }
}


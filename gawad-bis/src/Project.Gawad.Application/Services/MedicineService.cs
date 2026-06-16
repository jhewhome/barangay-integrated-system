using AutoMapper;
using Microsoft.Extensions.Logging;
using MongoDB.Bson;
using Project.Gawad.Core.Extensions;
using Project.Gawad.Core.Repositories;
using Project.Gawad.Core.Services;
using Project.Gawad.Domain.Entities;
using Project.Gawad.Domain.Enums.Medicine;
using Project.Gawad.Domain.Objects;
using Project.Gawad.Domain.Objects.Authentication;
using Project.Gawad.Domain.Objects.Medicine;

namespace Project.Gawad.Application.Services;

public class MedicineService(
    IMedicineRepository medicineRepository,
    IMedicineStockRepository medicineStockRepository,
    IMedicineTransactionRepository medicineTransactionRepository,
    IMapper mapper,
    ILogger<MedicineService> logger) : IMedicineService
{
    private readonly IMedicineRepository _medicineRepository =
        medicineRepository ?? throw new ArgumentNullException(nameof(medicineRepository));

    private readonly IMedicineStockRepository _medicineStockRepository =
        medicineStockRepository ?? throw new ArgumentNullException(nameof(medicineStockRepository));

    private readonly IMedicineTransactionRepository _medicineTransactionRepository =
        medicineTransactionRepository ?? throw new ArgumentNullException(nameof(medicineTransactionRepository));

    private readonly IMapper _mapper =
        mapper ?? throw new ArgumentNullException(nameof(mapper));

    private readonly ILogger<MedicineService> _logger =
        logger ?? throw new ArgumentNullException(nameof(logger));

    public async Task<ServiceResponse<CreateMedicineObject>> CreateMedicine(
        CreateMedicineObject createMedicineObject,
        ApplicationUserObject createdBy)
    {
        // Trim and validate medicine name
        if (!string.IsNullOrWhiteSpace(createMedicineObject.Name))
        {
            createMedicineObject.Name = createMedicineObject.Name.Trim();
        }

        // Validate name is not empty
        if (string.IsNullOrWhiteSpace(createMedicineObject.Name))
        {
            var emptyNameResponse = new ServiceResponse<CreateMedicineObject>();
            emptyNameResponse.AddModelError("Name", "Medicine name is required.");
            return emptyNameResponse;
        }

        // Check if medicine with same name already exists (case-insensitive)
        var existingMedicine = await _medicineRepository.GetMedicineByNameAsync(createMedicineObject.Name);
        if (existingMedicine != null && !existingMedicine.IsDeleted)
        {
            var response = new ServiceResponse<CreateMedicineObject>();
            response.AddModelError("Name", "A medicine with this name already exists. Please choose a different name.");
            return response;
        }

        var medicine = _mapper.Map<CreateMedicineObject, Medicine>(createMedicineObject);
        medicine.CreatedDate = DateTime.Now;
        medicine.CreatedById = createdBy.Id;

        await _medicineRepository.AddAsync(medicine);
        await _medicineRepository.SaveChangesAsync();

        createMedicineObject.Id = medicine.Id;

        return new ServiceResponse<CreateMedicineObject>(createMedicineObject);
    }

    public async Task<ServiceResponse<UpdateMedicineObject>> UpdateMedicine(
        UpdateMedicineObject updateMedicineObject,
        ApplicationUserObject updatedBy)
    {
        var existingMedicine = await _medicineRepository.GetByIdAsync(updateMedicineObject.MedicineId);
        if (existingMedicine == null || existingMedicine.IsDeleted)
        {
            var response = new ServiceResponse<UpdateMedicineObject>();
            response.AddModelError("Medicine", "Medicine not found.");
            return response;
        }

        // Trim and validate medicine name
        if (!string.IsNullOrWhiteSpace(updateMedicineObject.Name))
        {
            updateMedicineObject.Name = updateMedicineObject.Name.Trim();
        }
        else
        {
            var response = new ServiceResponse<UpdateMedicineObject>();
            response.AddModelError("Name", "Medicine name is required.");
            return response;
        }

        // Check if name is changed and conflicts with another medicine (case-insensitive)
        if (!string.IsNullOrWhiteSpace(existingMedicine.Name) && 
            !string.IsNullOrWhiteSpace(updateMedicineObject.Name) &&
            existingMedicine.Name.ToLower() != updateMedicineObject.Name.ToLower())
        {
            var nameConflict = await _medicineRepository.GetMedicineByNameAsync(updateMedicineObject.Name);
            if (nameConflict != null && nameConflict.Id != updateMedicineObject.MedicineId && !nameConflict.IsDeleted)
            {
                var response = new ServiceResponse<UpdateMedicineObject>();
                response.AddModelError("Name", "A medicine with this name already exists. Please choose a different name.");
                return response;
            }
        }

        _mapper.Map(updateMedicineObject, existingMedicine);
        existingMedicine.LastModifiedDate = DateTime.Now;
        existingMedicine.LastModifiedById = updatedBy.Id;

        await _medicineRepository.UpdateAsync(existingMedicine);
        await _medicineRepository.SaveChangesAsync();

        return new ServiceResponse<UpdateMedicineObject>(updateMedicineObject);
    }

    public async Task<bool> RemoveMedicine(string id, ApplicationUserObject deletedBy)
    {
        if (!ObjectId.TryParse(id, out var medicineId))
            return false;

        var medicine = await _medicineRepository.GetByIdAsync(medicineId);
        if (medicine == null || medicine.IsDeleted)
            return false;

        // Soft-delete the medicine
        medicine.IsDeleted = true;
        medicine.LastModifiedDate = DateTime.Now;
        medicine.LastModifiedById = deletedBy.Id;

        await _medicineRepository.UpdateAsync(medicine);
        await _medicineRepository.SaveChangesAsync();

        // Also soft-delete all transactions related to this medicine
        await _medicineTransactionRepository.DeleteTransactionsByMedicineIdAsync(medicineId, deletedBy.Id);

        return true;
    }

    public async Task<ServiceResponse<CreateMedicineStockObject>> AddStock(
        CreateMedicineStockObject createStockObject,
        ApplicationUserObject createdBy)
    {
        var medicine = await _medicineRepository.GetByIdAsync(createStockObject.MedicineId);
        if (medicine == null || medicine.IsDeleted)
        {
            var response = new ServiceResponse<CreateMedicineStockObject>();
            response.AddModelError("MedicineId", "Medicine not found.");
            return response;
        }

        var stock = _mapper.Map<CreateMedicineStockObject, MedicineStock>(createStockObject);
        stock.CreatedDate = DateTime.Now;
        stock.CreatedById = createdBy.Id;
        stock.ReceivedDate = createStockObject.ReceivedDate ?? DateTime.Now;

        await _medicineStockRepository.AddAsync(stock);
        await _medicineStockRepository.SaveChangesAsync();

        // Get unit price from createStockObject (user-entered or from medicine)
        // IMPORTANT: This unit price is per box/bottle, NOT per piece
        decimal? unitPrice = createStockObject.CostPerUnit;
        if (!unitPrice.HasValue && medicine.UnitPrice.HasValue)
        {
            unitPrice = medicine.UnitPrice;
        }
        
        // Validate that unit price exists
        if (!unitPrice.HasValue || unitPrice.Value <= 0)
        {
            _logger.LogWarning($"No valid unit price found for medicine {medicine.Id}. CostPerUnit: {createStockObject.CostPerUnit}, Medicine.UnitPrice: {medicine.UnitPrice}");
        }
        
        // Parse unit type from Notes to calculate total amount correctly
        // Formula: TotalAmount = UnitPrice × Number of Units (boxes/bottles/pieces)
        // NOT: TotalAmount = UnitPrice × Total Quantity (pieces)
        decimal? totalAmount = null;
        
        if (unitPrice.HasValue && !string.IsNullOrWhiteSpace(createStockObject.Notes))
        {
            var notes = createStockObject.Notes.Trim();
            
            // Look for INPUT_UNIT format: "INPUT_UNIT:unitType:unitCount"
            // Example: "INPUT_UNIT:boxes:2" means 2 boxes
            // Example: "INPUT_UNIT:bottles:5" means 5 bottles
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
                        if (decimal.TryParse(unitCountStr, out var unitCount) && unitCount > 0)
                        {
                            // ============================================
                            // CRITICAL FORMULA: TotalAmount = UnitPrice × Number of Boxes/Bottles
                            // NOT: UnitPrice × Total Quantity (pieces)
                            // ============================================
                            if (unitType == "boxes" || unitType == "box")
                            {
                                // Formula: Unit Price (per box) × Number of Boxes
                                // Example: ₱100 per box × 2 boxes = ₱200
                                // NOT: ₱100 × 200 pieces = ₱20,000
                                totalAmount = unitPrice.Value * unitCount; // unitCount = number of boxes
                                _logger.LogInformation($"BOXES CALCULATION: Unit Price ₱{unitPrice.Value} per box × {unitCount} boxes = ₱{totalAmount} (NOT using quantity: {createStockObject.Quantity} pieces)");
                            }
                            else if (unitType == "bottles" || unitType == "bottle")
                            {
                                // Formula: Unit Price (per bottle) × Number of Bottles
                                // Example: ₱50 per bottle × 3 bottles = ₱150
                                // NOT: ₱50 × 30 pieces = ₱1,500
                                totalAmount = unitPrice.Value * unitCount; // unitCount = number of bottles
                                _logger.LogInformation($"BOTTLES CALCULATION: Unit Price ₱{unitPrice.Value} per bottle × {unitCount} bottles = ₱{totalAmount} (NOT using quantity: {createStockObject.Quantity} pieces)");
                            }
                            else if (unitType == "pieces" || unitType == "piece")
                            {
                                // For pieces, unitCount is the number of pieces
                                totalAmount = unitPrice.Value * unitCount;
                                _logger.LogInformation($"PIECES CALCULATION: Unit Price ₱{unitPrice.Value} per piece × {unitCount} pieces = ₱{totalAmount}");
                            }
                            else
                            {
                                _logger.LogWarning($"Unknown unit type '{unitType}' in Notes: {notes}");
                            }
                        }
                        else
                        {
                            _logger.LogWarning($"Could not parse unit count from Notes: {notes}");
                        }
                    }
                    else
                    {
                        _logger.LogWarning($"Invalid INPUT_UNIT format in Notes: {notes}. Expected format: INPUT_UNIT:unitType:unitCount");
                    }
                }
                catch (Exception ex)
                {
                    _logger.LogError(ex, $"Error parsing INPUT_UNIT from Notes: {notes}");
                }
            }
        }
        
        // Fallback: if no unit type info, calculate based on medicine's UnitOfMeasure
        // IMPORTANT: Unit Price is per box/bottle, NOT per piece
        // If UnitOfMeasure is Box, we need to estimate number of boxes from quantity
        // If UnitOfMeasure is Bottle, we need to estimate number of bottles from quantity
        // This fallback should rarely be used since INPUT_UNIT is always stored in Notes
        if (!totalAmount.HasValue && unitPrice.HasValue)
        {
            if (medicine.UnitOfMeasure == Domain.Enums.Medicine.UnitOfMeasure.Box)
            {
                // Unit Price is per box, but we only have total quantity in pieces
                // Try to estimate number of boxes (this is not ideal, but better than multiplying by pieces)
                // Common box sizes: 10, 20, 30, 50, 100, 200, 500, 1000
                decimal estimatedBoxes = 1;
                var commonBoxSizes = new[] { 10m, 20m, 30m, 50m, 100m, 200m, 500m, 1000m };
                foreach (var boxSize in commonBoxSizes)
                {
                    if (createStockObject.Quantity >= boxSize && createStockObject.Quantity % boxSize == 0)
                    {
                        estimatedBoxes = createStockObject.Quantity / boxSize;
                        break;
                    }
                }
                // If no match, assume 1 box (safer than multiplying by total pieces)
                totalAmount = unitPrice.Value * estimatedBoxes;
                _logger.LogWarning($"No INPUT_UNIT found in Notes for Box medicine, using fallback: estimated {estimatedBoxes} boxes × {unitPrice.Value} = {totalAmount} (Quantity: {createStockObject.Quantity} pieces)");
            }
            else if (medicine.UnitOfMeasure == Domain.Enums.Medicine.UnitOfMeasure.Bottle)
            {
                // Unit Price is per bottle, but we only have total quantity in pieces
                // Try to estimate number of bottles (this is not ideal, but better than multiplying by pieces)
                // Common bottle sizes: 10, 20, 30, 50, 100
                decimal estimatedBottles = 1;
                var commonBottleSizes = new[] { 10m, 20m, 30m, 50m, 100m };
                foreach (var bottleSize in commonBottleSizes)
                {
                    if (createStockObject.Quantity >= bottleSize && createStockObject.Quantity % bottleSize == 0)
                    {
                        estimatedBottles = createStockObject.Quantity / bottleSize;
                        break;
                    }
                }
                // If no match, assume 1 bottle (safer than multiplying by total pieces)
                totalAmount = unitPrice.Value * estimatedBottles;
                _logger.LogWarning($"No INPUT_UNIT found in Notes for Bottle medicine, using fallback: estimated {estimatedBottles} bottles × {unitPrice.Value} = {totalAmount} (Quantity: {createStockObject.Quantity} pieces)");
            }
            else
            {
                // For other unit types (tablet, capsule, etc.), unit price is per piece
                totalAmount = unitPrice.Value * createStockObject.Quantity;
                _logger.LogWarning($"No INPUT_UNIT found in Notes, using fallback calculation: {createStockObject.Quantity} pieces × {unitPrice.Value} = {totalAmount}");
            }
        }

        // Create stock-in transaction
        var transaction = new MedicineTransaction
        {
            Id = ObjectId.GenerateNewId(),
            MedicineId = createStockObject.MedicineId,
            MedicineStockId = stock.Id,
            TransactionType = MedicineTransactionType.StockIn,
            Quantity = createStockObject.Quantity,
            TransactionDate = DateTime.Now,
            UnitPrice = unitPrice,
            TotalAmount = totalAmount,
            Reason = "Stock added",
            Notes = createStockObject.Notes,
            CreatedDate = DateTime.Now,
            CreatedById = createdBy.Id
        };

        await _medicineTransactionRepository.AddAsync(transaction);
        await _medicineTransactionRepository.SaveChangesAsync();

        createStockObject.Id = stock.Id;

        return new ServiceResponse<CreateMedicineStockObject>(createStockObject);
    }

    public async Task<ServiceResponse<CreateMedicineTransactionObject>> CreateTransaction(
        CreateMedicineTransactionObject createTransactionObject,
        ApplicationUserObject createdBy)
    {
        var medicine = await _medicineRepository.GetByIdAsync(createTransactionObject.MedicineId);
        if (medicine == null || medicine.IsDeleted)
        {
            var response = new ServiceResponse<CreateMedicineTransactionObject>();
            response.AddModelError("MedicineId", "Medicine not found.");
            return response;
        }

        // For stock out transactions, verify sufficient stock
        if (createTransactionObject.TransactionType == MedicineTransactionType.StockOut ||
            createTransactionObject.TransactionType == MedicineTransactionType.Dispensed)
        {
            var totalStock = await _medicineStockRepository.GetTotalStockByMedicineIdAsync(
                createTransactionObject.MedicineId);

            if (totalStock < createTransactionObject.Quantity)
            {
                var response = new ServiceResponse<CreateMedicineTransactionObject>();
                response.AddModelError("Quantity", $"Insufficient stock. Available: {totalStock}");
                return response;
            }

            // Check allocation limits for limited supply medicines when dispensing to a resident
            if (createTransactionObject.TransactionType == MedicineTransactionType.Dispensed &&
                createTransactionObject.RecipientPersonId.HasValue &&
                medicine.IsLimitedSupply.HasValue &&
                medicine.IsLimitedSupply.Value &&
                medicine.AllocationPeriod.HasValue &&
                medicine.AllocationPeriod.Value != AllocationPeriod.None &&
                medicine.MaxQuantityPerPeriod.HasValue)
            {
                var periodStart = medicine.AllocationPeriod!.Value == AllocationPeriod.Weekly
                    ? DateTime.Now.StartOfWeek()
                    : DateTime.Now.StartOfMonth();

                var periodEnd = medicine.AllocationPeriod.Value == AllocationPeriod.Weekly
                    ? DateTime.Now.EndOfWeek()
                    : DateTime.Now.EndOfMonth();

                // Get all dispensed transactions for this medicine and resident in the current period
                var dispensedTransactions = await _medicineTransactionRepository.GetDispensedTransactionsAsync(
                    createTransactionObject.MedicineId,
                    createTransactionObject.RecipientPersonId.Value,
                    periodStart,
                    periodEnd);

                var totalDispensedInPeriod = dispensedTransactions
                    .Where(t => t.TransactionType == MedicineTransactionType.Dispensed)
                    .Sum(t => t.Quantity);

                var requestedQuantity = createTransactionObject.Quantity;
                var newTotal = totalDispensedInPeriod + requestedQuantity;

                if (newTotal > medicine.MaxQuantityPerPeriod.Value)
                {
                    var remaining = medicine.MaxQuantityPerPeriod.Value - totalDispensedInPeriod;
                    var periodText = medicine.AllocationPeriod!.Value == AllocationPeriod.Weekly ? "week" : "month";
                    
                    var response = new ServiceResponse<CreateMedicineTransactionObject>();
                    if (remaining > 0)
                    {
                        response.AddModelError("Quantity", 
                            $"Allocation limit exceeded. Maximum {medicine.MaxQuantityPerPeriod} per {periodText}. " +
                            $"Already dispensed: {totalDispensedInPeriod} this {periodText}. " +
                            $"Remaining allocation: {remaining}. Requested: {requestedQuantity}");
                    }
                    else
                    {
                        response.AddModelError("Quantity", 
                            $"Allocation limit reached for this {periodText}. " +
                            $"Maximum {medicine.MaxQuantityPerPeriod} per {periodText}. " +
                            $"Already dispensed: {totalDispensedInPeriod} this {periodText}.");
                    }
                    return response;
                }
            }
        }

        var transaction = _mapper.Map<CreateMedicineTransactionObject, MedicineTransaction>(createTransactionObject);
        transaction.Id = ObjectId.GenerateNewId();
        transaction.CreatedDate = DateTime.Now;
        transaction.CreatedById = createdBy.Id;

        // Calculate total amount if unit price is provided
        // IMPORTANT: Formula is Unit Price × Number of Boxes/Bottles (NOT quantity/pieces)
        if (transaction.UnitPrice.HasValue)
        {
            decimal unitCount = 0; // Number of boxes/bottles
            bool foundUnitInfo = false;
            
            // First, try to parse INPUT_UNIT from Notes (format: "INPUT_UNIT:boxes:2" or "INPUT_UNIT:bottles:5")
            if (!string.IsNullOrWhiteSpace(createTransactionObject.Notes))
            {
                var notes = createTransactionObject.Notes.Trim();
                
                if (notes.StartsWith("INPUT_UNIT:", StringComparison.OrdinalIgnoreCase))
                {
                    try
                    {
                        var unitInfo = notes.Substring(11); // Remove "INPUT_UNIT:" prefix
                        var unitInfoPart = unitInfo.Split(';')[0].Trim(); // Get first part if there are multiple sections
                        var parts = unitInfoPart.Split(':');
                        
                        if (parts.Length >= 2)
                        {
                            var unitType = parts[0].Trim().ToLower();
                            if (decimal.TryParse(parts[1].Trim(), out var parsedUnitCount) && parsedUnitCount > 0)
                            {
                                if (unitType == "boxes" || unitType == "box" || 
                                    unitType == "bottles" || unitType == "bottle" ||
                                    unitType == "pieces" || unitType == "piece")
                                {
                                    unitCount = parsedUnitCount;
                                    foundUnitInfo = true;
                                    _logger.LogInformation($"CreateTransaction: Found INPUT_UNIT in Notes: {unitType} = {unitCount}");
                                }
                            }
                        }
                    }
                    catch (Exception ex)
                    {
                        _logger.LogWarning(ex, $"Error parsing INPUT_UNIT from Notes: {notes}");
                    }
                }
                // Check for PIECES: format (used in per-piece dispensed transactions)
                else if (notes.StartsWith("PIECES:", StringComparison.OrdinalIgnoreCase))
                {
                    try
                    {
                        var piecesStr = notes.Substring(7).Trim(); // Remove "PIECES:" prefix
                        var piecesPart = piecesStr.Split(';')[0].Trim(); // Get first part if there are multiple sections
                        if (decimal.TryParse(piecesPart, out var piecesFromNotes))
                        {
                            // For per-piece dispense: TotalAmount = UnitPrice × Quantity (pieces)
                            // Note: This assumes UnitPrice is per piece for dispense purposes
                            // If UnitPrice is per box/bottle, a conversion would be needed, but for simplicity,
                            // we'll treat UnitPrice as per piece for dispense
                            transaction.TotalAmount = transaction.UnitPrice.Value * transaction.Quantity;
                            _logger.LogInformation($"CreateTransaction: Per-piece dispense - UnitPrice ₱{transaction.UnitPrice.Value} × Quantity {transaction.Quantity} pieces = ₱{transaction.TotalAmount}");
                            // Continue with the rest of the method (skip the rest of the calculation logic)
                            foundUnitInfo = true; // Mark as found to skip fallback calculations
                        }
                    }
                    catch (Exception ex)
                    {
                        _logger.LogWarning(ex, $"Error parsing PIECES from Notes: {notes}");
                    }
                }
                // Also check for BOXES: format (legacy support for box-based dispense)
                else if (notes.StartsWith("BOXES:", StringComparison.OrdinalIgnoreCase))
                {
                    try
                    {
                        var boxesStr = notes.Substring(6).Trim();
                        var boxInfoPart = boxesStr.Split(';')[0].Trim();
                        if (decimal.TryParse(boxInfoPart, out var boxesFromNotes))
                        {
                            unitCount = boxesFromNotes;
                            foundUnitInfo = true;
                            _logger.LogInformation($"CreateTransaction: Found BOXES in Notes: {unitCount}");
                        }
                    }
                    catch (Exception ex)
                    {
                        _logger.LogWarning(ex, $"Error parsing BOXES from Notes: {notes}");
                    }
                }
            }
            
            // If we found unit info (boxes/bottles), use it for calculation
            if (foundUnitInfo && unitCount > 0)
            {
                // Formula: Unit Price × Number of Boxes/Bottles
                transaction.TotalAmount = transaction.UnitPrice.Value * unitCount;
                _logger.LogInformation($"CreateTransaction: TotalAmount = {transaction.UnitPrice.Value} × {unitCount} = {transaction.TotalAmount}");
            }
            else
            {
                // Fallback: For dispensed transactions without unit info, calculate per piece
                // This assumes UnitPrice is per piece for dispense purposes
                if (createTransactionObject.TransactionType == MedicineTransactionType.Dispensed)
                {
                    transaction.TotalAmount = transaction.UnitPrice.Value * transaction.Quantity;
                    _logger.LogInformation($"CreateTransaction: Per-piece dispense (fallback) - UnitPrice ₱{transaction.UnitPrice.Value} × Quantity {transaction.Quantity} pieces = ₱{transaction.TotalAmount}");
                }
                else
                {
                    // For stock-in transactions, try to estimate based on medicine's UnitOfMeasure
                    if (medicine.UnitOfMeasure == Domain.Enums.Medicine.UnitOfMeasure.Box)
                    {
                        // Estimate boxes from quantity (not ideal, but better than using quantity directly)
                        decimal piecesPerBox = 100; // Default estimate
                        var commonBoxSizes = new[] { 10m, 20m, 30m, 50m, 100m, 200m, 500m, 1000m };
                        foreach (var boxSize in commonBoxSizes)
                        {
                            if (transaction.Quantity >= boxSize && transaction.Quantity % boxSize == 0)
                            {
                                piecesPerBox = boxSize;
                                break;
                            }
                        }
                        unitCount = transaction.Quantity / piecesPerBox;
                        if (unitCount < 1) unitCount = 1; // Minimum 1 box
                        transaction.TotalAmount = transaction.UnitPrice.Value * unitCount;
                        _logger.LogWarning($"CreateTransaction: No unit info in Notes, estimated {unitCount} boxes from quantity {transaction.Quantity} (piecesPerBox: {piecesPerBox})");
                    }
                    else if (medicine.UnitOfMeasure == Domain.Enums.Medicine.UnitOfMeasure.Bottle)
                    {
                        // Estimate bottles from quantity (not ideal, but better than using quantity directly)
                        decimal piecesPerBottle = 10; // Default estimate
                        var commonBottleSizes = new[] { 5m, 10m, 20m, 30m, 50m, 100m };
                        foreach (var bottleSize in commonBottleSizes)
                        {
                            if (transaction.Quantity >= bottleSize && transaction.Quantity % bottleSize == 0)
                            {
                                piecesPerBottle = bottleSize;
                                break;
                            }
                        }
                        unitCount = transaction.Quantity / piecesPerBottle;
                        if (unitCount < 1) unitCount = 1; // Minimum 1 bottle
                        transaction.TotalAmount = transaction.UnitPrice.Value * unitCount;
                        _logger.LogWarning($"CreateTransaction: No unit info in Notes, estimated {unitCount} bottles from quantity {transaction.Quantity} (piecesPerBottle: {piecesPerBottle})");
                    }
                    else
                    {
                        // For other unit types, assume per piece
                        transaction.TotalAmount = transaction.UnitPrice.Value * transaction.Quantity;
                        _logger.LogWarning($"CreateTransaction: No unit info in Notes, using quantity directly: {transaction.UnitPrice.Value} × {transaction.Quantity} = {transaction.TotalAmount}");
                    }
                }
            }
        }

        await _medicineTransactionRepository.AddAsync(transaction);
        await _medicineTransactionRepository.SaveChangesAsync();

        // Update stock if it's a stock out/dispensed transaction
        if (createTransactionObject.TransactionType == MedicineTransactionType.StockOut ||
            createTransactionObject.TransactionType == MedicineTransactionType.Dispensed)
        {
            await UpdateStockQuantity(createTransactionObject.MedicineId, 
                createTransactionObject.MedicineStockId, 
                -createTransactionObject.Quantity);
        }

        createTransactionObject.Id = transaction.Id;

        return new ServiceResponse<CreateMedicineTransactionObject>(createTransactionObject);
    }

    private async Task UpdateStockQuantity(ObjectId medicineId, ObjectId? stockId, decimal quantityChange)
    {
        if (stockId.HasValue)
        {
            // Update specific stock batch
            var stock = await _medicineStockRepository.GetByIdAsync(stockId.Value);
            if (stock != null && !stock.IsDeleted)
            {
                stock.Quantity += quantityChange;
                if (stock.Quantity < 0) stock.Quantity = 0;
                stock.LastModifiedDate = DateTime.Now;

                await _medicineStockRepository.UpdateAsync(stock);
                await _medicineStockRepository.SaveChangesAsync();
            }
        }
        else
        {
            // Update first available stock (FIFO - First In First Out)
            var stocks = await _medicineStockRepository.GetStocksByMedicineIdAsync(medicineId);
            var remainingChange = quantityChange;

            foreach (var stock in stocks.OrderBy(s => s.ExpiryDate ?? DateTime.MaxValue))
            {
                if (remainingChange == 0) break;

                if (stock.Quantity + remainingChange >= 0)
                {
                    stock.Quantity += remainingChange;
                    remainingChange = 0;
                }
                else
                {
                    remainingChange += stock.Quantity;
                    stock.Quantity = 0;
                }

                stock.LastModifiedDate = DateTime.Now;
                await _medicineStockRepository.UpdateAsync(stock);
            }

            await _medicineStockRepository.SaveChangesAsync();
        }
    }

    public async Task<ServiceResponse<bool>> MarkStockAsNotified(ObjectId stockId, ApplicationUserObject notifiedBy)
    {
        var stock = await _medicineStockRepository.GetByIdAsync(stockId);
        if (stock == null || stock.IsDeleted)
        {
            var response = new ServiceResponse<bool>();
            response.AddModelError("StockId", "Stock not found.");
            return response;
        }

        stock.NotificationDate = DateTime.Now;
        stock.NotifiedById = notifiedBy.Id;
        stock.LastModifiedDate = DateTime.Now;
        stock.LastModifiedById = notifiedBy.Id;

        await _medicineStockRepository.UpdateAsync(stock);
        await _medicineStockRepository.SaveChangesAsync();

        return new ServiceResponse<bool>(true);
    }

    public async Task<ServiceResponse<bool>> RecordStockAction(RecordStockActionObject actionObject, ApplicationUserObject actionBy)
    {
        var stock = await _medicineStockRepository.GetByIdAsync(actionObject.StockId);
        if (stock == null || stock.IsDeleted)
        {
            var response = new ServiceResponse<bool>();
            response.AddModelError("StockId", "Stock not found.");
            return response;
        }

        if (string.IsNullOrWhiteSpace(actionObject.ActionTaken))
        {
            var response = new ServiceResponse<bool>();
            response.AddModelError("ActionTaken", "Action taken is required.");
            return response;
        }

        stock.ActionTaken = actionObject.ActionTaken.Trim();
        stock.ActionNotes = actionObject.ActionNotes?.Trim();
        stock.ActionDate = DateTime.Now;
        stock.ActionTakenById = actionBy.Id;
        stock.LastModifiedDate = DateTime.Now;
        stock.LastModifiedById = actionBy.Id;

        await _medicineStockRepository.UpdateAsync(stock);
        await _medicineStockRepository.SaveChangesAsync();

        return new ServiceResponse<bool>(true);
    }

    public async Task<ServiceResponse<UpdateMedicineTransactionObject>> UpdateTransaction(
        UpdateMedicineTransactionObject updateTransactionObject, ApplicationUserObject updatedBy)
    {
        var transaction = await _medicineTransactionRepository.GetByIdAsync(updateTransactionObject.TransactionId);
        if (transaction == null || transaction.IsDeleted)
        {
            var response = new ServiceResponse<UpdateMedicineTransactionObject>();
            response.AddModelError("TransactionId", "Transaction not found.");
            return response;
        }

        // Only allow editing StockIn transactions
        if (transaction.TransactionType != MedicineTransactionType.StockIn)
        {
            var response = new ServiceResponse<UpdateMedicineTransactionObject>();
            response.AddModelError("TransactionType", "Only StockIn transactions can be edited.");
            return response;
        }

        // Get the medicine to check UnitOfMeasure
        var medicine = await _medicineRepository.GetByIdAsync(transaction.MedicineId);
        if (medicine == null || medicine.IsDeleted)
        {
            var response = new ServiceResponse<UpdateMedicineTransactionObject>();
            response.AddModelError("MedicineId", "Associated medicine not found or is deleted.");
            return response;
        }

        // Calculate the quantity difference
        var quantityDifference = updateTransactionObject.Quantity - transaction.Quantity;

        // Update transaction fields
        transaction.Quantity = updateTransactionObject.Quantity;
        transaction.TransactionDate = updateTransactionObject.TransactionDate;
        transaction.UnitPrice = updateTransactionObject.UnitPrice;
        transaction.Reason = updateTransactionObject.Reason;
        
        // Preserve existing Notes if new Notes is empty, otherwise update it
        if (!string.IsNullOrWhiteSpace(updateTransactionObject.Notes))
        {
            transaction.Notes = updateTransactionObject.Notes;
        }
        // If Notes is empty but we want to preserve unit type info, keep existing Notes
        
        // Recalculate total amount if unit price is provided
        if (transaction.UnitPrice.HasValue)
        {
            decimal? totalAmount = null;
            
            // Check Notes field for unit type information (format: "INPUT_UNIT:boxes:2" or "INPUT_UNIT:bottles:5")
            // Formula: TotalAmount = UnitPrice × Number of Units (boxes/bottles/pieces)
            // NOT: TotalAmount = UnitPrice × Total Quantity (pieces)
            if (!string.IsNullOrWhiteSpace(transaction.Notes))
            {
                var notes = transaction.Notes.Trim();
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
                            if (decimal.TryParse(unitCountStr, out var unitCount) && unitCount > 0)
                            {
                                // UnitPrice is per unit type (box/bottle/piece)
                                // TotalAmount = UnitPrice × Number of Units (NOT total pieces)
                                if (unitType == "boxes" || unitType == "box")
                                {
                                    // Cost is per box, multiply by number of boxes
                                    totalAmount = transaction.UnitPrice.Value * unitCount;
                                    _logger.LogInformation($"Transaction update calculation: {unitCount} boxes × {transaction.UnitPrice.Value} = {totalAmount}");
                                }
                                else if (unitType == "bottles" || unitType == "bottle")
                                {
                                    // Cost is per bottle, multiply by number of bottles
                                    totalAmount = transaction.UnitPrice.Value * unitCount;
                                    _logger.LogInformation($"Transaction update calculation: {unitCount} bottles × {transaction.UnitPrice.Value} = {totalAmount}");
                                }
                                else if (unitType == "pieces" || unitType == "piece")
                                {
                                    // Cost is per piece, multiply by unit count (number of pieces)
                                    totalAmount = transaction.UnitPrice.Value * unitCount;
                                    _logger.LogInformation($"Transaction update calculation: {unitCount} pieces × {transaction.UnitPrice.Value} = {totalAmount}");
                                }
                                else
                                {
                                    _logger.LogWarning($"Unknown unit type '{unitType}' in Notes: {notes}");
                                }
                            }
                            else
                            {
                                _logger.LogWarning($"Could not parse unit count from Notes: {notes}");
                            }
                        }
                        else
                        {
                            _logger.LogWarning($"Invalid INPUT_UNIT format in Notes: {notes}. Expected format: INPUT_UNIT:unitType:unitCount");
                        }
                    }
                    catch (Exception ex)
                    {
                        _logger.LogError(ex, $"Error parsing INPUT_UNIT from Notes: {notes}");
                    }
                }
            }
            
            // If we couldn't calculate from Notes, check medicine UnitOfMeasure
            if (!totalAmount.HasValue)
            {
                if (medicine.UnitOfMeasure == Domain.Enums.Medicine.UnitOfMeasure.Box)
                {
                    // For box type, try to estimate boxes from quantity
                    // Common box sizes: 10, 20, 30, 50, 100, 200, 500, 1000
                    decimal piecesPerBox = 100; // Default estimate
                    var commonBoxSizes = new[] { 10m, 20m, 30m, 50m, 100m, 200m, 500m, 1000m };
                    foreach (var boxSize in commonBoxSizes)
                    {
                        if (transaction.Quantity >= boxSize && transaction.Quantity % boxSize == 0)
                        {
                            piecesPerBox = boxSize;
                            break;
                        }
                    }
                    var estimatedBoxes = transaction.Quantity / piecesPerBox;
                    totalAmount = transaction.UnitPrice.Value * estimatedBoxes;
                }
                else if (medicine.UnitOfMeasure == Domain.Enums.Medicine.UnitOfMeasure.Bottle)
                {
                    // For bottle type, try to estimate bottles from quantity
                    decimal piecesPerBottle = 10; // Default estimate
                    var commonBottleSizes = new[] { 5m, 10m, 20m, 30m, 50m, 100m };
                    foreach (var bottleSize in commonBottleSizes)
                    {
                        if (transaction.Quantity >= bottleSize && transaction.Quantity % bottleSize == 0)
                        {
                            piecesPerBottle = bottleSize;
                            break;
                        }
                    }
                    var estimatedBottles = transaction.Quantity / piecesPerBottle;
                    totalAmount = transaction.UnitPrice.Value * estimatedBottles;
                }
                else
                {
                    // For other types, assume per piece
                    totalAmount = transaction.UnitPrice.Value * transaction.Quantity;
                }
            }
            
            transaction.TotalAmount = totalAmount;
        }
        else
        {
            transaction.TotalAmount = null;
        }

        transaction.LastModifiedDate = DateTime.Now;
        transaction.LastModifiedById = updatedBy.Id;

        await _medicineTransactionRepository.UpdateAsync(transaction);
        await _medicineTransactionRepository.SaveChangesAsync();

        // Adjust stock quantity based on the difference
        if (quantityDifference != 0)
        {
            await UpdateStockQuantity(transaction.MedicineId, transaction.MedicineStockId, quantityDifference);
        }

        return new ServiceResponse<UpdateMedicineTransactionObject>(updateTransactionObject);
    }

    public async Task<bool> DeleteTransaction(string transactionId, ApplicationUserObject deletedBy)
    {
        try
        {
            if (!ObjectId.TryParse(transactionId, out var transactionObjectId))
            {
                _logger.LogWarning($"Invalid transaction ID format: {transactionId}");
                return false;
            }

            // Get the transaction
            var transaction = await _medicineTransactionRepository.GetByIdAsync(transactionObjectId);
            if (transaction == null || transaction.IsDeleted)
            {
                _logger.LogWarning($"Transaction not found or already deleted: {transactionId}");
                return false;
            }

            // Reverse the stock change based on transaction type
            if (transaction.TransactionType == MedicineTransactionType.StockIn)
            {
                // StockIn adds stock, so deletion should subtract it
                await UpdateStockQuantity(transaction.MedicineId, transaction.MedicineStockId, -transaction.Quantity);
                _logger.LogInformation($"Deleted StockIn transaction {transactionId}, reversed {transaction.Quantity} pieces from stock");
            }
            else if (transaction.TransactionType == MedicineTransactionType.Dispensed || 
                     transaction.TransactionType == MedicineTransactionType.StockOut)
            {
                // Dispensed/StockOut removes stock, so deletion should add it back
                await UpdateStockQuantity(transaction.MedicineId, transaction.MedicineStockId, transaction.Quantity);
                _logger.LogInformation($"Deleted {transaction.TransactionType} transaction {transactionId}, restored {transaction.Quantity} pieces to stock");
            }

            // Soft delete the transaction
            transaction.IsDeleted = true;
            transaction.LastModifiedDate = DateTime.Now;
            transaction.LastModifiedById = deletedBy.Id;

            await _medicineTransactionRepository.UpdateAsync(transaction);
            await _medicineTransactionRepository.SaveChangesAsync();

            _logger.LogInformation($"Transaction {transactionId} deleted successfully by user {deletedBy.Id}");
            return true;
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, $"Error deleting transaction {transactionId}");
            return false;
        }
    }

    public async Task<bool> RemoveStock(string stockId, ApplicationUserObject deletedBy)
    {
        try
        {
            if (!ObjectId.TryParse(stockId, out var stockObjectId))
            {
                _logger.LogWarning($"Invalid stock ID format: {stockId}");
                return false;
            }

            // Get the stock
            var stock = await _medicineStockRepository.GetByIdAsync(stockObjectId);
            if (stock == null || stock.IsDeleted)
            {
                _logger.LogWarning($"Stock not found or already deleted: {stockId}");
                return false;
            }

            // Soft delete the stock
            stock.IsDeleted = true;
            stock.LastModifiedDate = DateTime.Now;
            stock.LastModifiedById = deletedBy.Id;

            await _medicineStockRepository.UpdateAsync(stock);
            await _medicineStockRepository.SaveChangesAsync();

            _logger.LogInformation($"Stock {stockId} deleted successfully by user {deletedBy.Id}");
            return true;
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, $"Error deleting stock {stockId}");
            return false;
        }
    }
}


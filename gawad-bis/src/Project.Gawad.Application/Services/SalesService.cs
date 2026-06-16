using AutoMapper;
using Microsoft.Extensions.Logging;
using MongoDB.Bson;
using Project.Gawad.Core.Repositories;
using Project.Gawad.Core.Services;
using Project.Gawad.Domain.Entities;
using Project.Gawad.Domain.Enums.Medicine;
using Project.Gawad.Domain.Enums.Sales;
using Project.Gawad.Domain.Objects;
using Project.Gawad.Domain.Objects.Authentication;
using Project.Gawad.Domain.Objects.Sales;

namespace Project.Gawad.Application.Services;

public class SalesService(
    ISaleRepository saleRepository,
    ISaleItemRepository saleItemRepository,
    IPaymentRepository paymentRepository,
    IMedicineRepository medicineRepository,
    IMedicineStockRepository medicineStockRepository,
    IMedicineTransactionRepository medicineTransactionRepository,
    ICashSessionRepository cashSessionRepository,
    ICashMovementRepository cashMovementRepository,
    IMapper mapper,
    ILogger<SalesService> logger) : ISalesService
{
    private readonly ISaleRepository _saleRepository = saleRepository ?? throw new ArgumentNullException(nameof(saleRepository));
    private readonly ISaleItemRepository _saleItemRepository = saleItemRepository ?? throw new ArgumentNullException(nameof(saleItemRepository));
    private readonly IPaymentRepository _paymentRepository = paymentRepository ?? throw new ArgumentNullException(nameof(paymentRepository));
    private readonly IMedicineRepository _medicineRepository = medicineRepository ?? throw new ArgumentNullException(nameof(medicineRepository));
    private readonly IMedicineStockRepository _medicineStockRepository = medicineStockRepository ?? throw new ArgumentNullException(nameof(medicineStockRepository));
    private readonly IMedicineTransactionRepository _medicineTransactionRepository = medicineTransactionRepository ?? throw new ArgumentNullException(nameof(medicineTransactionRepository));
    private readonly ICashSessionRepository _cashSessionRepository = cashSessionRepository ?? throw new ArgumentNullException(nameof(cashSessionRepository));
    private readonly ICashMovementRepository _cashMovementRepository = cashMovementRepository ?? throw new ArgumentNullException(nameof(cashMovementRepository));
    private readonly IMapper _mapper = mapper ?? throw new ArgumentNullException(nameof(mapper));
    private readonly ILogger<SalesService> _logger = logger ?? throw new ArgumentNullException(nameof(logger));

    public async Task<ServiceResponse<CreateSaleObject>> CreateSale(
        CreateSaleObject createSaleObject,
        ApplicationUserObject createdBy)
    {
        // Get open cash session
        var cashSession = await _cashSessionRepository.GetOpenSessionAsync();
        if (cashSession == null)
        {
            var response = new ServiceResponse<CreateSaleObject>();
            response.AddModelError("", "No open cash session. Please open a cash session first.");
            return response;
        }

        // Validate items
        if (createSaleObject.Items == null || !createSaleObject.Items.Any())
        {
            var response = new ServiceResponse<CreateSaleObject>();
            response.AddModelError("Items", "Sale must have at least one item.");
            return response;
        }

        var sale = new Sale
        {
            Id = ObjectId.GenerateNewId(),
            SaleDate = createSaleObject.SaleDate,
            Status = SaleStatus.Pending,
            CustomerPersonId = string.IsNullOrWhiteSpace(createSaleObject.CustomerPersonId) 
                ? null 
                : ObjectId.Parse(createSaleObject.CustomerPersonId),
            CustomerName = createSaleObject.CustomerName,
            Notes = createSaleObject.Notes,
            CashSessionId = cashSession.Id,
            CreatedDate = DateTime.Now,
            CreatedById = createdBy.Id
        };

        decimal subtotal = 0;

        // Process each item
        foreach (var itemDto in createSaleObject.Items)
        {
            var medicineId = ObjectId.Parse(itemDto.MedicineId);
            var medicine = await _medicineRepository.GetByIdAsync(medicineId);
            
            if (medicine == null || medicine.IsDeleted || !medicine.IsActive)
            {
                var response = new ServiceResponse<CreateSaleObject>();
                response.AddModelError("Items", $"Medicine {itemDto.MedicineId} not found or inactive.");
                return response;
            }

            // Check stock availability
            var totalStock = await _medicineStockRepository.GetTotalStockByMedicineIdAsync(medicineId);
            if (totalStock < itemDto.Quantity)
            {
                var response = new ServiceResponse<CreateSaleObject>();
                response.AddModelError("Items", $"Insufficient stock for {medicine.Name}. Available: {totalStock}");
                return response;
            }

            var saleItem = new SaleItem
            {
                Id = ObjectId.GenerateNewId(),
                SaleId = sale.Id!.Value,
                MedicineId = medicineId,
                MedicineStockId = string.IsNullOrWhiteSpace(itemDto.MedicineStockId) 
                    ? null 
                    : ObjectId.Parse(itemDto.MedicineStockId),
                Quantity = itemDto.Quantity,
                UnitPrice = itemDto.UnitPrice > 0 ? itemDto.UnitPrice : (medicine.UnitPrice ?? 0),
                DiscountAmount = itemDto.DiscountAmount,
                LineTotal = (itemDto.UnitPrice > 0 ? itemDto.UnitPrice : (medicine.UnitPrice ?? 0)) * itemDto.Quantity - itemDto.DiscountAmount,
                Notes = itemDto.Notes,
                CreatedDate = DateTime.Now,
                CreatedById = createdBy.Id
            };

            sale.SaleItems.Add(saleItem);
            subtotal += saleItem.LineTotal;
        }

        // Calculate totals
        sale.Subtotal = subtotal;
        sale.DiscountAmount = createSaleObject.DiscountAmount;
        sale.TaxAmount = createSaleObject.TaxAmount;
        sale.TotalAmount = subtotal - createSaleObject.DiscountAmount + createSaleObject.TaxAmount;

        await _saleRepository.AddAsync(sale);
        await _saleRepository.SaveChangesAsync();

        // Add sale items
        foreach (var item in sale.SaleItems)
        {
            await _saleItemRepository.AddAsync(item);
        }
        await _saleItemRepository.SaveChangesAsync();

        // Update stock (will be finalized when payment is processed)
        // Return sale ID for redirect
        return new ServiceResponse<CreateSaleObject>(createSaleObject) { Message = sale.Id!.Value.ToString() };
    }

    public async Task<ServiceResponse<CreatePaymentObject>> ProcessPayment(
        CreatePaymentObject createPaymentObject,
        ApplicationUserObject createdBy)
    {
        if (!ObjectId.TryParse(createPaymentObject.SaleId, out var saleId))
        {
            var response = new ServiceResponse<CreatePaymentObject>();
            response.AddModelError("SaleId", "Invalid sale ID.");
            return response;
        }

        var sale = await _saleRepository.GetSaleWithItemsAsync(saleId);
        if (sale == null || sale.IsDeleted)
        {
            var response = new ServiceResponse<CreatePaymentObject>();
            response.AddModelError("SaleId", "Sale not found.");
            return response;
        }

        if (sale.Status == SaleStatus.Completed)
        {
            var response = new ServiceResponse<CreatePaymentObject>();
            response.AddModelError("SaleId", "Sale is already completed.");
            return response;
        }

        // Get open cash session
        var cashSession = await _cashSessionRepository.GetOpenSessionAsync();
        if (cashSession == null)
        {
            var response = new ServiceResponse<CreatePaymentObject>();
            response.AddModelError("", "No open cash session.");
            return response;
        }

        var payment = new Payment
        {
            Id = ObjectId.GenerateNewId(),
            SaleId = saleId,
            PaymentMethod = createPaymentObject.PaymentMethod,
            Amount = createPaymentObject.Amount,
            Change = createPaymentObject.Change ?? 0,
            PaymentDate = DateTime.Now,
            ReferenceNumber = createPaymentObject.ReferenceNumber,
            Notes = createPaymentObject.Notes,
            CashSessionId = cashSession.Id,
            CreatedDate = DateTime.Now,
            CreatedById = createdBy.Id
        };

        await _paymentRepository.AddAsync(payment);
        await _paymentRepository.SaveChangesAsync();

        // Update sale
        sale.AmountPaid += createPaymentObject.Amount;
        sale.Change += (createPaymentObject.Change ?? 0);

        // If fully paid, mark as completed and deduct stock
        if (sale.AmountPaid >= sale.TotalAmount)
        {
            sale.Status = SaleStatus.Completed;
            sale.Change = sale.AmountPaid - sale.TotalAmount;

            // Deduct stock for each item
            foreach (var item in sale.SaleItems)
            {
                // Create medicine transaction for dispensed items
                var transaction = new MedicineTransaction
                {
                    Id = ObjectId.GenerateNewId(),
                    MedicineId = item.MedicineId,
                    MedicineStockId = item.MedicineStockId,
                    TransactionType = MedicineTransactionType.Dispensed,
                    Quantity = item.Quantity,
                    TransactionDate = DateTime.Now,
                    UnitPrice = item.UnitPrice,
                    TotalAmount = item.LineTotal,
                    Reason = "Sold",
                    CreatedDate = DateTime.Now,
                    CreatedById = createdBy.Id
                };

                await _medicineTransactionRepository.AddAsync(transaction);

                // Update stock
                if (item.MedicineStockId.HasValue)
                {
                    var stock = await _medicineStockRepository.GetByIdAsync(item.MedicineStockId.Value);
                    if (stock != null && !stock.IsDeleted)
                    {
                        stock.Quantity -= item.Quantity;
                        if (stock.Quantity < 0) stock.Quantity = 0;
                        stock.LastModifiedDate = DateTime.Now;
                        await _medicineStockRepository.UpdateAsync(stock);
                    }
                }
                else
                {
                    // FIFO stock deduction
                    var stocks = await _medicineStockRepository.GetStocksByMedicineIdAsync(item.MedicineId);
                    var remainingQty = item.Quantity;

                    foreach (var stock in stocks.OrderBy(s => s.ExpiryDate ?? DateTime.MaxValue))
                    {
                        if (remainingQty <= 0) break;

                        if (stock.Quantity >= remainingQty)
                        {
                            stock.Quantity -= remainingQty;
                            remainingQty = 0;
                        }
                        else
                        {
                            remainingQty -= stock.Quantity;
                            stock.Quantity = 0;
                        }

                        stock.LastModifiedDate = DateTime.Now;
                        await _medicineStockRepository.UpdateAsync(stock);
                    }
                }
            }

            await _medicineTransactionRepository.SaveChangesAsync();
            await _medicineStockRepository.SaveChangesAsync();
        }

        sale.LastModifiedDate = DateTime.Now;
        sale.LastModifiedById = createdBy.Id;
        await _saleRepository.UpdateAsync(sale);
        await _saleRepository.SaveChangesAsync();

        // Create cash movement for sale
        var cashMovement = new CashMovement
        {
            Id = ObjectId.GenerateNewId(),
            CashSessionId = cashSession.Id!.Value,
            MovementType = Domain.Enums.Cash.CashMovementType.Sale,
            Amount = createPaymentObject.Amount,
            ReferenceSaleId = saleId,
            ReferencePaymentId = payment.Id,
            MovementDate = DateTime.Now,
            Notes = $"Sale payment: {payment.Amount}",
            CreatedDate = DateTime.Now,
            CreatedById = createdBy.Id
        };

        await _cashMovementRepository.AddAsync(cashMovement);
        await _cashMovementRepository.SaveChangesAsync();

        return new ServiceResponse<CreatePaymentObject>(createPaymentObject);
    }

    public async Task<bool> CancelSale(string saleId, ApplicationUserObject cancelledBy)
    {
        if (!ObjectId.TryParse(saleId, out var saleIdObj))
            return false;

        var sale = await _saleRepository.GetSaleWithItemsAsync(saleIdObj);
        if (sale == null || sale.IsDeleted || sale.Status == SaleStatus.Completed)
            return false;

        sale.Status = SaleStatus.Cancelled;
        sale.LastModifiedDate = DateTime.Now;
        sale.LastModifiedById = cancelledBy.Id;

        await _saleRepository.UpdateAsync(sale);
        await _saleRepository.SaveChangesAsync();

        return true;
    }
}

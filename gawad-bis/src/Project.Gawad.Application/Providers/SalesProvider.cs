using AutoMapper;
using Microsoft.EntityFrameworkCore;
using MongoDB.Bson;
using Project.Gawad.Core.Providers;
using Project.Gawad.Core.Repositories;
using Project.Gawad.Domain.Entities;
using Project.Gawad.Domain.Objects;
using Project.Gawad.Domain.Objects.Sales;
using Project.Gawad.Domain.Enums.Sales;

namespace Project.Gawad.Application.Providers;

public class SalesProvider(
    ISaleRepository saleRepository,
    ISaleItemRepository saleItemRepository,
    IPaymentRepository paymentRepository,
    IMapper mapper) : ISalesProvider
{
    private readonly IMapper _mapper = mapper ?? throw new ArgumentNullException(nameof(mapper));
    private readonly ISaleRepository _saleRepository = saleRepository ?? throw new ArgumentNullException(nameof(saleRepository));
    private readonly ISaleItemRepository _saleItemRepository = saleItemRepository ?? throw new ArgumentNullException(nameof(saleItemRepository));
    private readonly IPaymentRepository _paymentRepository = paymentRepository ?? throw new ArgumentNullException(nameof(paymentRepository));

    public async Task<PaginatedRecords<SaleListObject>> GetSalesListAsync(
        int page = 1, int itemsPerPage = 10,
        DateTime? startDate = null, DateTime? endDate = null, string? search = null)
    {
        IEnumerable<Sale> sales;

        if (startDate.HasValue && endDate.HasValue)
        {
            sales = await _saleRepository.GetSalesByDateRangeAsync(startDate.Value, endDate.Value);
        }
        else
        {
            sales = await _saleRepository.GetAllAsync();
        }

        // Apply search filter if provided
        if (!string.IsNullOrWhiteSpace(search))
        {
            sales = sales.Where(s => 
                (s.CustomerName != null && s.CustomerName.Contains(search, StringComparison.OrdinalIgnoreCase)) ||
                (s.Notes != null && s.Notes.Contains(search, StringComparison.OrdinalIgnoreCase)));
        }

        var totalRecords = sales.Count();
        var paginatedData = sales
            .OrderByDescending(s => s.SaleDate)
            .Skip((page - 1) * itemsPerPage)
            .Take(itemsPerPage)
            .ToList();

        var saleListObjects = new List<SaleListObject>();

        foreach (var sale in paginatedData)
        {
            var saleItems = await _saleItemRepository.GetSaleItemsBySaleIdAsync(sale.Id!.Value);
            var saleObj = _mapper.Map<SaleListObject>(sale);
            saleObj.ItemCount = saleItems.Count();
            saleListObjects.Add(saleObj);
        }

        return new PaginatedRecords<SaleListObject>
        {
            PageNumber = page,
            PageSize = itemsPerPage,
            RecordsTotal = totalRecords,
            RecordsFiltered = totalRecords,
            Data = saleListObjects
        };
    }

    public async Task<SaleDetailObject?> GetSaleDetailObjectAsync(ObjectId saleId)
    {
        var sale = await _saleRepository.GetSaleWithItemsAsync(saleId);
        if (sale == null || sale.IsDeleted)
            return null;

        var saleItems = await _saleItemRepository.GetSaleItemsBySaleIdAsync(saleId);
        var payments = await _paymentRepository.GetPaymentsBySaleIdAsync(saleId);

        var detailObj = _mapper.Map<SaleDetailObject>(sale);
        
        detailObj.Items = saleItems.Select(item =>
        {
            var itemObj = _mapper.Map<SaleItemListObject>(item);
            itemObj.MedicineName = sale.SaleItems.FirstOrDefault(i => i.Id == item.Id)?.Medicine?.Name ?? "Unknown";
            return itemObj;
        }).ToList();

        detailObj.Payments = payments.Select(p => _mapper.Map<PaymentListObject>(p)).ToList();

        return detailObj;
    }
}




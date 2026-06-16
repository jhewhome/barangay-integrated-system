using MongoDB.Bson;
using Project.Gawad.Domain.Objects;
using Project.Gawad.Domain.Objects.Sales;

namespace Project.Gawad.Core.Providers;

public interface ISalesProvider
{
    Task<PaginatedRecords<SaleListObject>> GetSalesListAsync(int page = 1, int itemsPerPage = 10,
        DateTime? startDate = null, DateTime? endDate = null, string? search = null);

    Task<SaleDetailObject?> GetSaleDetailObjectAsync(ObjectId saleId);
}




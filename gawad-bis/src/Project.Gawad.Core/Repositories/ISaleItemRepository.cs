using MongoDB.Bson;
using Project.Gawad.Domain.Entities;

namespace Project.Gawad.Core.Repositories;

public interface ISaleItemRepository : IBaseRepository<SaleItem>
{
    Task<IEnumerable<SaleItem>> GetSaleItemsBySaleIdAsync(ObjectId saleId);

    Task<IEnumerable<SaleItem>> GetSaleItemsByMedicineIdAsync(ObjectId medicineId);
}




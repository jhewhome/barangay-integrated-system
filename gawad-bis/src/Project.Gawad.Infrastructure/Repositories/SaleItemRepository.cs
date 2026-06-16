using Microsoft.EntityFrameworkCore;
using MongoDB.Bson;
using Project.Gawad.Core.Repositories;
using Project.Gawad.Data.MongoDb;
using Project.Gawad.Domain.Entities;

namespace Project.Gawad.Infrastructure.Repositories;

public class SaleItemRepository(GawadMongoDbContext dbContext)
    : BaseRepository<SaleItem>(dbContext), ISaleItemRepository
{
    public async Task<IEnumerable<SaleItem>> GetSaleItemsBySaleIdAsync(ObjectId saleId)
    {
        return await _dbContext.Set<SaleItem>()
            .AsNoTracking()
            .Where(x => !x.IsDeleted && x.SaleId == saleId)
            .ToListAsync();
    }

    public async Task<IEnumerable<SaleItem>> GetSaleItemsByMedicineIdAsync(ObjectId medicineId)
    {
        return await _dbContext.Set<SaleItem>()
            .AsNoTracking()
            .Where(x => !x.IsDeleted && x.MedicineId == medicineId)
            .OrderByDescending(x => x.CreatedDate)
            .ToListAsync();
    }
}




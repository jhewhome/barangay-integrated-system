using Microsoft.EntityFrameworkCore;
using MongoDB.Bson;
using Project.Gawad.Core.Repositories;
using Project.Gawad.Data.MongoDb;
using Project.Gawad.Domain.Entities;

namespace Project.Gawad.Infrastructure.Repositories;

public class SaleRepository(GawadMongoDbContext dbContext)
    : BaseRepository<Sale>(dbContext), ISaleRepository
{
    public async Task<IEnumerable<Sale>> GetSalesByDateRangeAsync(DateTime startDate, DateTime endDate)
    {
        return await _dbContext.Set<Sale>()
            .AsNoTracking()
            .Where(x => !x.IsDeleted && 
                   x.SaleDate >= startDate && 
                   x.SaleDate <= endDate)
            .OrderByDescending(x => x.SaleDate)
            .ToListAsync();
    }

    public async Task<IEnumerable<Sale>> GetSalesByCashSessionIdAsync(ObjectId cashSessionId)
    {
        return await _dbContext.Set<Sale>()
            .AsNoTracking()
            .Where(x => !x.IsDeleted && x.CashSessionId == cashSessionId)
            .OrderByDescending(x => x.SaleDate)
            .ToListAsync();
    }

    public async Task<Sale?> GetSaleWithItemsAsync(ObjectId saleId)
    {
        return await _dbContext.Set<Sale>()
            .AsNoTracking()
            .FirstOrDefaultAsync(x => x.Id == saleId && !x.IsDeleted);
    }
}




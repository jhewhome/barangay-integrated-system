using Microsoft.EntityFrameworkCore;
using MongoDB.Bson;
using Project.Gawad.Core.Repositories;
using Project.Gawad.Data.MongoDb;
using Project.Gawad.Domain.Entities;

namespace Project.Gawad.Infrastructure.Repositories;

public class MedicineStockRepository(GawadMongoDbContext dbContext)
    : BaseRepository<MedicineStock>(dbContext), IMedicineStockRepository
{
    public async Task<IEnumerable<MedicineStock>> GetStocksByMedicineIdAsync(ObjectId medicineId)
    {
        return await _dbContext.Set<MedicineStock>()
            .AsNoTracking()
            .Where(x => x.MedicineId == medicineId && !x.IsDeleted)
            .OrderBy(x => x.ExpiryDate)
            .ToListAsync();
    }

    public async Task<decimal> GetTotalStockByMedicineIdAsync(ObjectId medicineId)
    {
        var stocks = await _dbContext.Set<MedicineStock>()
            .AsNoTracking()
            .Where(x => x.MedicineId == medicineId && !x.IsDeleted)
            .ToListAsync();
        
        return stocks.Sum(x => x.Quantity);
    }

    public async Task<IEnumerable<MedicineStock>> GetExpiringStocksAsync(DateTime? beforeDate = null)
    {
        var cutoffDate = beforeDate ?? DateTime.Now.AddDays(30); // Default: expiring within 30 days
        
        return await _dbContext.Set<MedicineStock>()
            .AsNoTracking()
            .Where(x => !x.IsDeleted && 
                       x.ExpiryDate.HasValue && 
                       x.ExpiryDate.Value <= cutoffDate &&
                       x.ExpiryDate.Value > DateTime.Now)
            .OrderBy(x => x.ExpiryDate)
            .ToListAsync();
    }

    public async Task<IEnumerable<MedicineStock>> GetExpiredStocksAsync()
    {
        return await _dbContext.Set<MedicineStock>()
            .AsNoTracking()
            .Where(x => !x.IsDeleted && 
                       x.ExpiryDate.HasValue && 
                       x.ExpiryDate.Value < DateTime.Now)
            .OrderBy(x => x.ExpiryDate)
            .ToListAsync();
    }

    public async Task<IEnumerable<MedicineStock>> GetLowStockItemsAsync()
    {
        var allStocks = await _dbContext.Set<MedicineStock>()
            .AsNoTracking()
            .Where(x => !x.IsDeleted)
            .ToListAsync();

        var lowStockItems = new List<MedicineStock>();
        
        // Group stocks by medicine to calculate totals efficiently
        var medicineStockGroups = allStocks.GroupBy(s => s.MedicineId);
        
        foreach (var group in medicineStockGroups)
        {
            var medicine = await _dbContext.Set<Medicine>()
                .AsNoTracking()
                .FirstOrDefaultAsync(m => m.Id == group.Key && !m.IsDeleted);
            
            if (medicine != null)
            {
                var totalStock = group.Sum(s => s.Quantity);
                if (totalStock <= medicine.MinimumStockLevel)
                {
                    lowStockItems.AddRange(group);
                }
            }
        }
        
        return lowStockItems;
    }
}


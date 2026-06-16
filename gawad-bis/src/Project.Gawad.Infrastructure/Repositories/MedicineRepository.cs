using Microsoft.EntityFrameworkCore;
using Project.Gawad.Core.Repositories;
using Project.Gawad.Data.MongoDb;
using Project.Gawad.Domain.Entities;

namespace Project.Gawad.Infrastructure.Repositories;

public class MedicineRepository(GawadMongoDbContext dbContext)
    : BaseRepository<Medicine>(dbContext), IMedicineRepository
{
    public async Task<Medicine?> GetMedicineByNameAsync(string name)
    {
        return await _dbContext.Set<Medicine>()
            .AsNoTracking()
            .FirstOrDefaultAsync(x => x.Name.ToLower() == name.ToLower() && !x.IsDeleted);
    }

    public async Task<IEnumerable<Medicine>> GetLowStockMedicinesAsync()
    {
        var medicines = await _dbContext.Set<Medicine>()
            .AsNoTracking()
            .Where(x => !x.IsDeleted && x.IsActive)
            .ToListAsync();

        var lowStockMedicines = new List<Medicine>();
        
        foreach (var medicine in medicines)
        {
            var stocks = await _dbContext.Set<MedicineStock>()
                .AsNoTracking()
                .Where(x => x.MedicineId == medicine.Id && !x.IsDeleted)
                .ToListAsync();
            
            var totalStock = stocks.Sum(x => x.Quantity);
            
            if (totalStock <= medicine.MinimumStockLevel)
            {
                lowStockMedicines.Add(medicine);
            }
        }
        
        return lowStockMedicines;
    }

    public async Task<IEnumerable<Medicine>> GetActiveMedicinesAsync()
    {
        return await _dbContext.Set<Medicine>()
            .AsNoTracking()
            .Where(x => !x.IsDeleted && x.IsActive)
            .ToListAsync();
    }
}


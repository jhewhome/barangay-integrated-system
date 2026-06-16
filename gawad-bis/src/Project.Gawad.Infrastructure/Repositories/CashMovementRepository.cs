using Microsoft.EntityFrameworkCore;
using MongoDB.Bson;
using Project.Gawad.Core.Repositories;
using Project.Gawad.Data.MongoDb;
using Project.Gawad.Domain.Entities;

namespace Project.Gawad.Infrastructure.Repositories;

public class CashMovementRepository(GawadMongoDbContext dbContext)
    : BaseRepository<CashMovement>(dbContext), ICashMovementRepository
{
    public async Task<IEnumerable<CashMovement>> GetMovementsBySessionIdAsync(ObjectId cashSessionId)
    {
        return await _dbContext.Set<CashMovement>()
            .AsNoTracking()
            .Where(x => !x.IsDeleted && x.CashSessionId == cashSessionId)
            .OrderBy(x => x.MovementDate)
            .ToListAsync();
    }

    public async Task<IEnumerable<CashMovement>> GetMovementsByDateRangeAsync(DateTime startDate, DateTime endDate)
    {
        return await _dbContext.Set<CashMovement>()
            .AsNoTracking()
            .Where(x => !x.IsDeleted && 
                   x.MovementDate >= startDate && 
                   x.MovementDate <= endDate)
            .OrderByDescending(x => x.MovementDate)
            .ToListAsync();
    }
}




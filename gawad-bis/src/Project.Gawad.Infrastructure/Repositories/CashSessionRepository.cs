using Microsoft.EntityFrameworkCore;
using MongoDB.Bson;
using Project.Gawad.Core.Repositories;
using Project.Gawad.Data.MongoDb;
using Project.Gawad.Domain.Entities;
using Project.Gawad.Domain.Enums.Cash;

namespace Project.Gawad.Infrastructure.Repositories;

public class CashSessionRepository(GawadMongoDbContext dbContext)
    : BaseRepository<CashSession>(dbContext), ICashSessionRepository
{
    public async Task<CashSession?> GetOpenSessionAsync()
    {
        return await _dbContext.Set<CashSession>()
            .AsNoTracking()
            .FirstOrDefaultAsync(x => !x.IsDeleted && x.Status == CashSessionStatus.Open);
    }

    public async Task<IEnumerable<CashSession>> GetSessionsByDateRangeAsync(DateTime startDate, DateTime endDate)
    {
        return await _dbContext.Set<CashSession>()
            .AsNoTracking()
            .Where(x => !x.IsDeleted && 
                   x.SessionDate >= startDate && 
                   x.SessionDate <= endDate)
            .OrderByDescending(x => x.SessionDate)
            .ToListAsync();
    }

    public async Task<IEnumerable<CashSession>> GetSessionsByUserIdAsync(string userId)
    {
        return await _dbContext.Set<CashSession>()
            .AsNoTracking()
            .Where(x => !x.IsDeleted && x.OpenedById == userId)
            .OrderByDescending(x => x.SessionDate)
            .ToListAsync();
    }

    public async Task<CashSession?> GetSessionWithMovementsAsync(ObjectId sessionId)
    {
        return await _dbContext.Set<CashSession>()
            .AsNoTracking()
            .FirstOrDefaultAsync(x => x.Id == sessionId && !x.IsDeleted);
    }
}




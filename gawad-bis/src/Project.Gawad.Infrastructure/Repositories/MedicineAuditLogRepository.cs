using Microsoft.EntityFrameworkCore;
using MongoDB.Bson;
using Project.Gawad.Core.Repositories;
using Project.Gawad.Data.MongoDb;
using Project.Gawad.Domain.Entities;

namespace Project.Gawad.Infrastructure.Repositories;

public class MedicineAuditLogRepository(GawadMongoDbContext dbContext)
    : BaseRepository<MedicineAuditLog>(dbContext), IMedicineAuditLogRepository
{
    public async Task<IEnumerable<MedicineAuditLog>> GetAuditLogsByEntityIdAsync(ObjectId entityId)
    {
        return await _dbContext.Set<MedicineAuditLog>()
            .AsNoTracking()
            .Where(x => x.EntityId == entityId && !x.IsDeleted)
            .OrderByDescending(x => x.CreatedDate)
            .ToListAsync();
    }

    public async Task<IEnumerable<MedicineAuditLog>> GetAuditLogsByActionAsync(string action)
    {
        return await _dbContext.Set<MedicineAuditLog>()
            .AsNoTracking()
            .Where(x => x.Action == action && !x.IsDeleted)
            .OrderByDescending(x => x.CreatedDate)
            .ToListAsync();
    }

    public async Task<IEnumerable<MedicineAuditLog>> GetAuditLogsByUserAsync(ObjectId userId)
    {
        return await _dbContext.Set<MedicineAuditLog>()
            .AsNoTracking()
            .Where(x => x.CreatedById == userId && !x.IsDeleted)
            .OrderByDescending(x => x.CreatedDate)
            .ToListAsync();
    }

    public async Task<IEnumerable<MedicineAuditLog>> GetAuditLogsByDateRangeAsync(DateTime startDate, DateTime endDate)
    {
        return await _dbContext.Set<MedicineAuditLog>()
            .AsNoTracking()
            .Where(x => x.CreatedDate >= startDate && x.CreatedDate <= endDate && !x.IsDeleted)
            .OrderByDescending(x => x.CreatedDate)
            .ToListAsync();
    }

    public IQueryable<MedicineAuditLog> GetQueryable()
    {
        return _dbContext.Set<MedicineAuditLog>().AsNoTracking();
    }
}


using Microsoft.EntityFrameworkCore;
using Project.Gawad.Core.Repositories;
using Project.Gawad.Data.MongoDb;
using Project.Gawad.Domain.Entities;

namespace Project.Gawad.Infrastructure.Repositories;

public class VisitorLogRepository(GawadMongoDbContext dbContext)
    : BaseRepository<VisitorLog>(dbContext), IVisitorLogRepository
{
    public Task<int> GetRecentVisitorsCountAsync(int? fromRecentDays = null)
    {
        if (fromRecentDays.HasValue)
        {
            var startDate = DateTime.Now.Date.AddDays(-fromRecentDays.Value);
            var endDate = DateTime.Now.Date.AddDays(1); // Include today
            
            return _dbContext.VisitorLogs
                .AsQueryable()
                .Where(t => t.CreatedDate >= startDate && t.CreatedDate < endDate)
                .CountAsync();
        }

        return _dbContext.VisitorLogs.AsQueryable().CountAsync();
    }
}
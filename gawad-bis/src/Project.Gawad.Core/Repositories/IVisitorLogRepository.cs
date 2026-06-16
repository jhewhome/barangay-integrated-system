using Project.Gawad.Domain.Entities;

namespace Project.Gawad.Core.Repositories;

public interface IVisitorLogRepository : IBaseRepository<VisitorLog>
{
    Task<int> GetRecentVisitorsCountAsync(int? fromRecentDays = null);
}
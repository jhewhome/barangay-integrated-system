using MongoDB.Bson;
using Project.Gawad.Domain.Entities;

namespace Project.Gawad.Core.Repositories;

public interface ICashSessionRepository : IBaseRepository<CashSession>
{
    Task<CashSession?> GetOpenSessionAsync();

    Task<IEnumerable<CashSession>> GetSessionsByDateRangeAsync(DateTime startDate, DateTime endDate);

    Task<IEnumerable<CashSession>> GetSessionsByUserIdAsync(string userId);

    Task<CashSession?> GetSessionWithMovementsAsync(ObjectId sessionId);
}




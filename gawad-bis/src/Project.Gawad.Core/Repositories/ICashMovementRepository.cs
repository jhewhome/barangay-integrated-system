using MongoDB.Bson;
using Project.Gawad.Domain.Entities;

namespace Project.Gawad.Core.Repositories;

public interface ICashMovementRepository : IBaseRepository<CashMovement>
{
    Task<IEnumerable<CashMovement>> GetMovementsBySessionIdAsync(ObjectId cashSessionId);

    Task<IEnumerable<CashMovement>> GetMovementsByDateRangeAsync(DateTime startDate, DateTime endDate);
}




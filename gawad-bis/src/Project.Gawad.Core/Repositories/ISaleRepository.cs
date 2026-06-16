using MongoDB.Bson;
using Project.Gawad.Domain.Entities;

namespace Project.Gawad.Core.Repositories;

public interface ISaleRepository : IBaseRepository<Sale>
{
    Task<IEnumerable<Sale>> GetSalesByDateRangeAsync(DateTime startDate, DateTime endDate);

    Task<IEnumerable<Sale>> GetSalesByCashSessionIdAsync(ObjectId cashSessionId);

    Task<Sale?> GetSaleWithItemsAsync(ObjectId saleId);
}




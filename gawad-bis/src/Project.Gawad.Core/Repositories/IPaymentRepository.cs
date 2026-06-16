using MongoDB.Bson;
using Project.Gawad.Domain.Entities;

namespace Project.Gawad.Core.Repositories;

public interface IPaymentRepository : IBaseRepository<Payment>
{
    Task<IEnumerable<Payment>> GetPaymentsBySaleIdAsync(ObjectId saleId);

    Task<IEnumerable<Payment>> GetPaymentsByCashSessionIdAsync(ObjectId cashSessionId);

    Task<IEnumerable<Payment>> GetPaymentsByDateRangeAsync(DateTime startDate, DateTime endDate);
}




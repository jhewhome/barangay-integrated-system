using Microsoft.EntityFrameworkCore;
using MongoDB.Bson;
using Project.Gawad.Core.Repositories;
using Project.Gawad.Data.MongoDb;
using Project.Gawad.Domain.Entities;

namespace Project.Gawad.Infrastructure.Repositories;

public class PaymentRepository(GawadMongoDbContext dbContext)
    : BaseRepository<Payment>(dbContext), IPaymentRepository
{
    public async Task<IEnumerable<Payment>> GetPaymentsBySaleIdAsync(ObjectId saleId)
    {
        return await _dbContext.Set<Payment>()
            .AsNoTracking()
            .Where(x => !x.IsDeleted && x.SaleId == saleId)
            .OrderByDescending(x => x.PaymentDate)
            .ToListAsync();
    }

    public async Task<IEnumerable<Payment>> GetPaymentsByCashSessionIdAsync(ObjectId cashSessionId)
    {
        return await _dbContext.Set<Payment>()
            .AsNoTracking()
            .Where(x => !x.IsDeleted && x.CashSessionId == cashSessionId)
            .OrderByDescending(x => x.PaymentDate)
            .ToListAsync();
    }

    public async Task<IEnumerable<Payment>> GetPaymentsByDateRangeAsync(DateTime startDate, DateTime endDate)
    {
        return await _dbContext.Set<Payment>()
            .AsNoTracking()
            .Where(x => !x.IsDeleted && 
                   x.PaymentDate >= startDate && 
                   x.PaymentDate <= endDate)
            .OrderByDescending(x => x.PaymentDate)
            .ToListAsync();
    }
}




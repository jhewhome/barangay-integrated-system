using Microsoft.EntityFrameworkCore;
using MongoDB.Bson;
using Project.Gawad.Core.Repositories;
using Project.Gawad.Data.MongoDb;
using Project.Gawad.Domain.Entities;

namespace Project.Gawad.Infrastructure.Repositories;

public class BusinessPermitRepository(GawadMongoDbContext dbContext)
    : BaseRepository<BusinessPermit>(dbContext), IBusinessPermitRepository
{
    public async Task<BusinessPermit?> GetBusinessPermitByBarangayTransactionIdAsync(ObjectId? barangayTransactionId)
    {
        return await _dbContext.BusinessPermits
            .FirstOrDefaultAsync(x => x.BarangayTrasactionId == barangayTransactionId
                                      && !x.IsDeleted);
    }
}
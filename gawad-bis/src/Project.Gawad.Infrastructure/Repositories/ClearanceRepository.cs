using Microsoft.EntityFrameworkCore;
using MongoDB.Bson;
using Project.Gawad.Core.Repositories;
using Project.Gawad.Data.MongoDb;
using Project.Gawad.Domain.Entities;

namespace Project.Gawad.Infrastructure.Repositories;

public class ClearanceRepository(GawadMongoDbContext dbContext)
    : BaseRepository<Clearance>(dbContext), IClearanceRepository
{
    public async Task<Clearance?> GetClearanceByBarangayTransactionIdAsync(ObjectId? barangayTransactionId)
    {
        return await _dbContext.Clearances
            .FirstOrDefaultAsync(x => x.BarangayTrasactionId == barangayTransactionId
                                      && !x.IsDeleted);
    }
}
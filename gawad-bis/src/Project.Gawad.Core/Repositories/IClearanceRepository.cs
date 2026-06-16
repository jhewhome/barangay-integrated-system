using MongoDB.Bson;
using Project.Gawad.Domain.Entities;

namespace Project.Gawad.Core.Repositories;

public interface IClearanceRepository : IBaseRepository<Clearance>
{
    Task<Clearance?> GetClearanceByBarangayTransactionIdAsync(ObjectId? barangayTransactionId);
}
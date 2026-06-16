using MongoDB.Bson;
using Project.Gawad.Domain.Entities;

namespace Project.Gawad.Core.Repositories;

public interface IBusinessPermitRepository : IBaseRepository<BusinessPermit>
{
    Task<BusinessPermit?> GetBusinessPermitByBarangayTransactionIdAsync(ObjectId? barangayTransactionId);
}
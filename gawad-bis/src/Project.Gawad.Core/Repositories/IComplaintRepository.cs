using MongoDB.Bson;
using Project.Gawad.Domain.Entities;
using Project.Gawad.Domain.Enums.Complaints;


namespace Project.Gawad.Core.Repositories;

public interface IComplaintRepository : IBaseRepository<Complaint>
{
    Task<IReadOnlyList<Complaint>> GetAllAsync();
    Task<Complaint?> GetClearanceByBarangayTransactionIdAsync(ObjectId personId);
    Task UpdateStatusAsync(ObjectId id, ComplaintStatus newStatus);
}